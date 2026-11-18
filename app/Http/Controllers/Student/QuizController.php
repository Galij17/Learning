<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(private readonly QuizAttemptService $quizAttemptService) {}

    public function show(Request $request, Course $course, Quiz $quiz): Response
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('view', $quiz);

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->orderByDesc('attempt_number')
            ->get();

        $activeAttempt = $attempts->firstWhere('status', 'in_progress');
        $canStart = $attempts->count() < $quiz->max_attempts || $activeAttempt !== null;

        $now = now();
        $scheduleStatus = 'open';
        if ($quiz->starts_at && $now->lt($quiz->starts_at)) {
            $scheduleStatus = 'upcoming';
        } elseif ($quiz->ends_at && $now->gt($quiz->ends_at)) {
            $scheduleStatus = 'closed';
        }

        return Inertia::render('Student/Quizzes/Show', [
            'course' => $course->only(['id', 'title', 'code']),
            'quiz' => $quiz->loadCount('questions'),
            'attempts' => $attempts,
            'activeAttempt' => $activeAttempt,
            'canStart' => $canStart && $scheduleStatus === 'open',
            'scheduleStatus' => $scheduleStatus,
        ]);
    }

    public function start(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('attempt', $quiz);

        $attempt = $this->quizAttemptService->startAttempt($quiz, $request->user());

        return redirect()->route('student.quizzes.take', [$course->id, $quiz->id, $attempt->id]);
    }

    public function take(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): Response|RedirectResponse
    {
        abort_unless(
            (int) $quiz->course_id === (int) $course->id
            && (int) $attempt->quiz_id === (int) $quiz->id
            && (int) $attempt->student_id === (int) $request->user()->id,
            403
        );

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.quizzes.result', [$course->id, $quiz->id, $attempt->id]);
        }

        if (now()->gt($attempt->expires_at)) {
            $this->quizAttemptService->submitAttempt($attempt, true);
            return redirect()->route('student.quizzes.result', [$course->id, $quiz->id, $attempt->id])
                ->with('warning', 'Waktu ujian telah habis dan jawaban Anda dikirim otomatis.');
        }

        $questionsById = $quiz->questions()
            ->with(['options:id,question_id,option_text,sort_order'])
            ->get()
            ->keyBy('id');

        $orderedIds = $attempt->question_order ?: $questionsById->keys()->all();
        $optionOrders = $attempt->option_orders ?: [];

        $questionsPayload = collect($orderedIds)
            ->map(function ($qId) use ($questionsById, $optionOrders) {
                $q = $questionsById->get($qId);
                if (! $q) {
                    return null;
                }

                $opts = $q->options->keyBy('id');
                $optOrder = $optionOrders[$qId] ?? $opts->keys()->all();
                $orderedOpts = collect($optOrder)
                    ->map(fn ($id) => $opts->get($id))
                    ->filter()
                    ->values()
                    ->map(fn ($o) => [
                        'id' => $o->id,
                        'option_text' => $o->option_text,
                    ]);

                return [
                    'id' => $q->id,
                    'type' => $q->type,
                    'question_text' => $q->question_text,
                    'points' => (float) ($q->pivot?->points ?? $q->points),
                    'options' => $orderedOpts,
                ];
            })
            ->filter()
            ->values();

        $savedAnswers = $attempt->answers()
            ->get(['question_id', 'selected_option_id', 'essay_answer'])
            ->keyBy('question_id');

        return Inertia::render('Student/Quizzes/Take', [
            'course' => $course->only(['id', 'title', 'code']),
            'quiz' => $quiz->only(['id', 'title', 'type', 'duration_minutes']),
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'expires_at' => $attempt->expires_at->toIso8601String(),
                'remaining_seconds' => max(0, now()->diffInSeconds($attempt->expires_at, false)),
            ],
            'questions' => $questionsPayload,
            'savedAnswers' => $savedAnswers,
        ]);
    }

    public function autosave(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless(
            (int) $quiz->course_id === (int) $course->id
            && (int) $attempt->quiz_id === (int) $quiz->id
            && (int) $attempt->student_id === (int) $request->user()->id,
            403
        );

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'essay_answer' => ['nullable', 'string', 'max:20000'],
        ]);

        $question = Question::query()->findOrFail($validated['question_id']);

        $this->quizAttemptService->saveAnswer(
            $attempt,
            $question,
            $validated['selected_option_id'] ?? null,
            $validated['essay_answer'] ?? null
        );

        return response()->json([
            'saved_at' => now()->format('H:i:s'),
            'remaining_seconds' => max(0, now()->diffInSeconds($attempt->expires_at, false)),
        ]);
    }

    public function submit(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        abort_unless(
            (int) $quiz->course_id === (int) $course->id
            && (int) $attempt->quiz_id === (int) $quiz->id
            && (int) $attempt->student_id === (int) $request->user()->id,
            403
        );

        $isAuto = $request->boolean('auto_submitted') || now()->gt($attempt->expires_at);
        $this->quizAttemptService->submitAttempt($attempt, $isAuto);

        return redirect()->route('student.quizzes.result', [$course->id, $quiz->id, $attempt->id])
            ->with('success', $isAuto ? 'Waktu habis, ujian dikirim otomatis.' : 'Jawaban ujian berhasil dikirim.');
    }

    public function result(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): Response|RedirectResponse
    {
        abort_unless(
            (int) $quiz->course_id === (int) $course->id
            && (int) $attempt->quiz_id === (int) $quiz->id,
            404
        );

        $isOwner = (int) $attempt->student_id === (int) $request->user()->id;
        $canManage = Gate::allows('manage', $quiz);
        abort_unless($isOwner || $canManage, 403);

        if ($attempt->status === 'in_progress' && ! $canManage) {
            if (now()->gt($attempt->expires_at)) {
                $attempt = $this->quizAttemptService->submitAttempt($attempt, true);
            } else {
                return redirect()->route('student.quizzes.take', [$course->id, $quiz->id, $attempt->id])
                    ->with('warning', 'Selesaikan dan kirim ujian terlebih dahulu sebelum melihat hasil.');
            }
        }

        $quizPointsByQuestion = $quiz->questions()
            ->get(['questions.id'])
            ->mapWithKeys(fn (Question $q) => [$q->id => (float) ($q->pivot?->points ?? $q->points)]);

        $attempt->load(['answers.question.options', 'answers.selectedOption', 'student:id,name']);

        $showAnswers = $canManage || $quiz->show_results;
        $showExplanations = $canManage || $quiz->show_explanations;

        $reviewItems = $showAnswers ? $attempt->answers->map(function ($ans) use ($showExplanations, $quizPointsByQuestion) {
            $q = $ans->question;
            return [
                'question_text' => $q?->question_text,
                'type' => $q?->type,
                'points' => (float) ($q ? ($quizPointsByQuestion->get($q->id) ?? $q->points) : 0),
                'selected_option_text' => $ans->selectedOption?->option_text,
                'correct_option_text' => $q?->options?->firstWhere('is_correct', true)?->option_text,
                'essay_answer' => $ans->essay_answer,
                'is_correct' => $ans->is_correct,
                'score_awarded' => (float) ($ans->score_awarded ?? 0),
                'feedback' => $ans->feedback,
                'explanation' => $showExplanations ? $q?->explanation : null,
            ];
        }) : [];

        return Inertia::render('Student/Quizzes/Result', [
            'course' => $course->only(['id', 'title', 'code']),
            'quiz' => $quiz,
            'attempt' => $attempt->only([
                'id',
                'attempt_number',
                'status',
                'started_at',
                'submitted_at',
                'objective_score',
                'essay_score',
                'total_score',
                'percentage',
                'is_passed',
                'needs_manual_grading',
            ]),
            'showResults' => $showAnswers,
            'reviewItems' => $reviewItems,
        ]);
    }
}
