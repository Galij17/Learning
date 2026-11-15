<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\GradePublishedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(
        private readonly GradingService $gradingService,
        private readonly EnrollmentService $enrollmentService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function startAttempt(Quiz $quiz, User $student): QuizAttempt
    {
        if (! $quiz->is_published) {
            throw ValidationException::withMessages(['quiz' => 'Ujian ini belum dipublikasikan.']);
        }

        $now = now();
        if ($quiz->starts_at && $now->lt($quiz->starts_at)) {
            throw ValidationException::withMessages(['quiz' => 'Ujian belum dimulai sesuai jadwal.']);
        }
        if ($quiz->ends_at && $now->gt($quiz->ends_at)) {
            throw ValidationException::withMessages(['quiz' => 'Batas waktu pengerjaan ujian telah berakhir.']);
        }

        // Resume existing active attempt if not expired
        $inProgress = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            if ($now->gt($inProgress->expires_at)) {
                $this->submitAttempt($inProgress, true);
            } else {
                return $inProgress->fresh(['answers']);
            }
        }

        $completedCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->count();

        if ($completedCount >= $quiz->max_attempts) {
            throw ValidationException::withMessages([
                'quiz' => "Anda telah mencapai batas maksimal {$quiz->max_attempts} kali percobaan.",
            ]);
        }

        $questions = $quiz->questions()->with('options')->get();
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'quiz' => 'Belum ada soal yang ditambahkan pada kuis/ujian ini.',
            ]);
        }

        $questionIds = $questions->pluck('id')->all();
        if ($quiz->shuffle_questions) {
            shuffle($questionIds);
        }

        $optionOrders = [];
        foreach ($questions as $question) {
            $optIds = $question->options->pluck('id')->all();
            if ($quiz->shuffle_options && count($optIds) > 1) {
                shuffle($optIds);
            }
            $optionOrders[$question->id] = $optIds;
        }

        $expiresAt = $now->copy()->addMinutes($quiz->duration_minutes);
        if ($quiz->ends_at && $quiz->ends_at->lt($expiresAt)) {
            $expiresAt = $quiz->ends_at->copy();
        }

        return DB::transaction(function () use ($quiz, $student, $completedCount, $questionIds, $optionOrders, $now, $expiresAt) {
            $attempt = QuizAttempt::query()->create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'attempt_number' => $completedCount + 1,
                'status' => 'in_progress',
                'question_order' => $questionIds,
                'option_orders' => $optionOrders,
                'started_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            $this->activityLogService->log(
                'quiz.started',
                "{$student->name} memulai {$quiz->title} (percobaan #{$attempt->attempt_number}).",
                $quiz,
                ['attempt_id' => $attempt->id],
                $student
            );

            return $attempt;
        });
    }

    public function saveAnswer(
        QuizAttempt $attempt,
        Question $question,
        ?int $selectedOptionId,
        ?string $essayAnswer
    ): QuizAnswer {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Percobaan ujian ini sudah dikirim.']);
        }

        // 15-second network latency grace window; beyond that, auto-submit
        if (now()->gt($attempt->expires_at->copy()->addSeconds(15))) {
            $this->submitAttempt($attempt, true);
            throw ValidationException::withMessages(['attempt' => 'Waktu ujian telah habis dan jawaban Anda dikirim otomatis.']);
        }

        $validOptionId = null;
        if ($selectedOptionId !== null && $question->type !== 'essay') {
            $belongs = $question->options()->where('id', $selectedOptionId)->exists();
            if (! $belongs) {
                throw ValidationException::withMessages(['selected_option_id' => 'Pilihan jawaban tidak valid untuk soal ini.']);
            }
            $validOptionId = $selectedOptionId;
        }

        return QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
            [
                'selected_option_id' => $validOptionId,
                'essay_answer' => $question->type === 'essay' ? $essayAnswer : null,
                'answered_at' => now(),
            ]
        );
    }

    public function submitAttempt(QuizAttempt $attempt, bool $autoSubmitted = false): QuizAttempt
    {
        if ($attempt->status !== 'in_progress') {
            return $attempt->fresh(['answers']);
        }

        return DB::transaction(function () use ($attempt, $autoSubmitted) {
            $quiz = $attempt->quiz()->with(['course', 'questions.options'])->firstOrFail();
            $answersByQuestion = $attempt->answers()->get()->keyBy('question_id');

            $objectiveScore = 0.0;
            $maxPossibleScore = 0.0;
            $hasEssay = false;

            foreach ($quiz->questions as $question) {
                $points = (float) ($question->pivot?->points ?? $question->points);
                $maxPossibleScore += $points;

                /** @var QuizAnswer|null $answer */
                $answer = $answersByQuestion->get($question->id);

                if ($question->type === 'essay') {
                    $hasEssay = true;
                    continue;
                }

                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $answer && $correctOption && (int) $answer->selected_option_id === (int) $correctOption->id;
                $awarded = $isCorrect ? $points : 0.0;
                $objectiveScore += $awarded;

                QuizAnswer::query()->updateOrCreate(
                    ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
                    [
                        'is_correct' => $isCorrect,
                        'score_awarded' => $awarded,
                    ]
                );
            }

            $totalScore = round($objectiveScore, 2);
            $percentage = $maxPossibleScore > 0 ? round(($totalScore / $maxPossibleScore) * 100, 2) : 0.0;
            $isPassed = ! $hasEssay && $percentage >= (float) $quiz->passing_score;

            $attempt->update([
                'status' => $hasEssay
                    ? ($autoSubmitted ? 'auto_submitted' : 'submitted')
                    : 'graded',
                'submitted_at' => now(),
                'objective_score' => round($objectiveScore, 2),
                'essay_score' => 0,
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'is_passed' => $isPassed,
                'needs_manual_grading' => $hasEssay,
                'graded_at' => $hasEssay ? null : now(),
            ]);

            if (! $hasEssay) {
                $this->gradingService->recalculateStudentGrade($quiz->course, $attempt->student);
                $this->enrollmentService->issueCertificateIfEligible($quiz->course, $attempt->student);

                $attempt->student->notify(new GradePublishedNotification(
                    $quiz->course,
                    $quiz->title,
                    $percentage,
                    route('student.quizzes.result', [$quiz->course_id, $quiz->id, $attempt->id])
                ));
            }

            $this->activityLogService->log(
                $autoSubmitted ? 'quiz.auto_submitted' : 'quiz.submitted',
                "{$attempt->student->name} menyelesaikan {$quiz->title} dengan skor {$percentage}%.",
                $quiz,
                ['attempt_id' => $attempt->id, 'percentage' => $percentage],
                $attempt->student
            );

            return $attempt->fresh(['answers']);
        });
    }

    /**
     * Grade essay questions manually and finalize attempt score.
     *
     * @param array<int, array{answer_id: int, score: float, feedback?: string|null}> $essayGrades
     */
    public function gradeEssayAnswers(QuizAttempt $attempt, array $essayGrades, User $grader): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $essayGrades, $grader) {
            $quiz = $attempt->quiz()->with(['course', 'questions'])->firstOrFail();
            $questionPoints = $quiz->questions->mapWithKeys(
                fn (Question $q) => [$q->id => (float) ($q->pivot?->points ?? $q->points)]
            );

            foreach ($essayGrades as $item) {
                $answer = QuizAnswer::query()
                    ->where('quiz_attempt_id', $attempt->id)
                    ->where('id', $item['answer_id'])
                    ->first();

                if (! $answer) {
                    continue;
                }

                $maxPts = $questionPoints->get($answer->question_id, 100.0);
                $clampedScore = max(0.0, min((float) $item['score'], $maxPts));

                $answer->update([
                    'score_awarded' => $clampedScore,
                    'feedback' => $item['feedback'] ?? null,
                ]);
            }

            $essayTotal = (float) $attempt->answers()
                ->whereHas('question', fn ($q) => $q->where('type', 'essay'))
                ->sum('score_awarded');

            $maxPossibleScore = (float) $questionPoints->sum();
            $totalScore = round((float) $attempt->objective_score + $essayTotal, 2);
            $percentage = $maxPossibleScore > 0 ? round(($totalScore / $maxPossibleScore) * 100, 2) : 0.0;
            $isPassed = $percentage >= (float) $quiz->passing_score;

            $attempt->update([
                'essay_score' => round($essayTotal, 2),
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'is_passed' => $isPassed,
                'needs_manual_grading' => false,
                'status' => 'graded',
                'graded_by' => $grader->id,
                'graded_at' => now(),
            ]);

            $this->gradingService->recalculateStudentGrade($quiz->course, $attempt->student, $grader);
            $this->enrollmentService->issueCertificateIfEligible($quiz->course, $attempt->student);

            $attempt->student->notify(new GradePublishedNotification(
                $quiz->course,
                $quiz->title,
                $percentage,
                route('student.quizzes.result', [$quiz->course_id, $quiz->id, $attempt->id])
            ));

            return $attempt->fresh(['answers']);
        });
    }
}
