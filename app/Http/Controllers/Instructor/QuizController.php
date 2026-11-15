<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuestionRequest;
use App\Http\Requests\Instructor\StoreQuizRequest;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Notifications\QuizPublishedNotification;
use App\Services\ActivityLogService;
use App\Services\QuizAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $quizAttemptService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function store(StoreQuizRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $quiz = DB::transaction(function () use ($request, $course) {
            $quiz = Quiz::query()->create([
                'course_id' => $course->id,
                'lesson_id' => $request->input('lesson_id'),
                'created_by' => $request->user()->id,
                'title' => $request->string('title')->toString(),
                'description' => $request->input('description'),
                'type' => $request->string('type')->toString(),
                'duration_minutes' => $request->integer('duration_minutes'),
                'starts_at' => $request->input('starts_at'),
                'ends_at' => $request->input('ends_at'),
                'max_attempts' => $request->integer('max_attempts'),
                'passing_score' => $request->float('passing_score'),
                'shuffle_questions' => $request->boolean('shuffle_questions'),
                'shuffle_options' => $request->boolean('shuffle_options'),
                'show_results' => $request->boolean('show_results', true),
                'show_explanations' => $request->boolean('show_explanations', true),
                'is_published' => $request->boolean('is_published'),
            ]);

            if ($request->filled('question_ids')) {
                $syncData = [];
                foreach ($request->input('question_ids', []) as $idx => $qId) {
                    $syncData[$qId] = ['sort_order' => $idx + 1];
                }
                $quiz->questions()->sync($syncData);
            }

            return $quiz;
        });

        if ($quiz->is_published) {
            Notification::send($course->students, new QuizPublishedNotification($quiz));
        }

        $this->activityLogService->log('instructor.quiz_created', "Membuat {$quiz->type} {$quiz->title}.", $quiz);

        return redirect()->route('instructor.quizzes.show', [$course->id, $quiz->id])
            ->with('success', 'Kuis/Ujian berhasil dibuat. Silakan kelola daftar soal.');
    }

    public function show(Request $request, Course $course, Quiz $quiz): Response
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $quiz->load([
            'questions.options',
            'questions.category:id,name',
            'questions.subject:id,name',
        ]);

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->with(['student:id,name,email,identity_number', 'answers.question:id,type,question_text,points'])
            ->latest('started_at')
            ->paginate(15);

        $bankQuestions = Question::query()
            ->with(['category:id,name', 'subject:id,name', 'options'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('created_by', $request->user()->id))
            ->whereNotIn('id', $quiz->questions->pluck('id'))
            ->latest()
            ->limit(50)
            ->get();

        $analytics = [
            'total_attempts' => QuizAttempt::where('quiz_id', $quiz->id)->count(),
            'passed_count' => QuizAttempt::where('quiz_id', $quiz->id)->where('is_passed', true)->count(),
            'average_score' => round((float) QuizAttempt::where('quiz_id', $quiz->id)->where('status', 'graded')->avg('percentage'), 2),
            'needs_grading_count' => QuizAttempt::where('quiz_id', $quiz->id)->where('needs_manual_grading', true)->count(),
        ];

        return Inertia::render('Instructor/Quizzes/Show', [
            'course' => $course->only(['id', 'title', 'code', 'category_id', 'subject_id']),
            'quiz' => $quiz,
            'attempts' => $attempts,
            'bankQuestions' => $bankQuestions,
            'analytics' => $analytics,
        ]);
    }

    public function update(StoreQuizRequest $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $wasPublished = $quiz->is_published;

        $quiz->update([
            'lesson_id' => $request->input('lesson_id'),
            'title' => $request->string('title')->toString(),
            'description' => $request->input('description'),
            'type' => $request->string('type')->toString(),
            'duration_minutes' => $request->integer('duration_minutes'),
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
            'max_attempts' => $request->integer('max_attempts'),
            'passing_score' => $request->float('passing_score'),
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_options' => $request->boolean('shuffle_options'),
            'show_results' => $request->boolean('show_results', true),
            'show_explanations' => $request->boolean('show_explanations', true),
            'is_published' => $request->boolean('is_published'),
        ]);

        if (! $wasPublished && $quiz->is_published) {
            Notification::send($course->students, new QuizPublishedNotification($quiz));
        }

        return back()->with('success', 'Pengaturan kuis/ujian berhasil diperbarui.');
    }

    public function attachQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $currentCount = $quiz->questions()->count();
        $attachData = [];
        foreach ($validated['question_ids'] as $idx => $qId) {
            $attachData[$qId] = ['sort_order' => $currentCount + $idx + 1];
        }

        $quiz->questions()->syncWithoutDetaching($attachData);

        return back()->with('success', 'Soal dari bank soal berhasil ditambahkan ke kuis/ujian.');
    }

    public function createInlineQuestion(StoreQuestionRequest $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        DB::transaction(function () use ($request, $course, $quiz) {
            $question = Question::query()->create([
                'category_id' => $request->input('category_id') ?? $course->category_id,
                'subject_id' => $request->input('subject_id') ?? $course->subject_id,
                'course_id' => $course->id,
                'created_by' => $request->user()->id,
                'type' => $request->string('type')->toString(),
                'question_text' => $request->string('question_text')->toString(),
                'explanation' => $request->input('explanation'),
                'points' => $request->float('points'),
                'difficulty' => $request->string('difficulty')->toString(),
                'is_bank' => $request->boolean('is_bank', true),
            ]);

            if ($question->type !== 'essay') {
                foreach ($request->input('options', []) as $idx => $opt) {
                    QuestionOption::query()->create([
                        'question_id' => $question->id,
                        'option_text' => $opt['option_text'],
                        'is_correct' => (bool) ($opt['is_correct'] ?? false),
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            $quiz->questions()->attach($question->id, [
                'sort_order' => $quiz->questions()->count() + 1,
                'points' => $question->points,
            ]);
        });

        return back()->with('success', 'Soal baru berhasil dibuat dan dimasukkan ke kuis/ujian.');
    }

    public function detachQuestion(Course $course, Quiz $quiz, Question $question): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $quiz->questions()->detach($question->id);

        return back()->with('success', 'Soal dilepas dari kuis/ujian.');
    }

    public function gradeEssays(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.answer_id' => ['required', 'integer', 'exists:quiz_answers,id'],
            'grades.*.score' => ['required', 'numeric', 'min:0'],
            'grades.*.feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->quizAttemptService->gradeEssayAnswers($attempt, $validated['grades'], $request->user());

        return back()->with('success', 'Penilaian essay berhasil disimpan dan nilai akhir diperbarui.');
    }

    public function destroy(Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $quiz->delete();

        return redirect()->route('instructor.courses.show', $course->id)->with('success', 'Kuis/ujian berhasil dihapus.');
    }
}
