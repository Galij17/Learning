<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuestionRequest;
use App\Http\Requests\Instructor\StoreQuizRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Services\QuestionManagementService;
use App\Services\QuestionSpreadsheetService;
use App\Services\QuizAttemptService;
use App\Services\QuizManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $quizAttemptService,
        private readonly QuestionSpreadsheetService $questionSpreadsheetService,
        private readonly QuestionManagementService $questionService,
        private readonly QuizManagementService $quizService,
    ) {}

    public function create(Request $request, Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load(['lessons' => fn ($q) => $q->orderBy('meeting_number')]);

        $bankQuestions = Question::query()
            ->with(['category:id,name', 'subject:id,name,code', 'options'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('created_by', $request->user()->id))
            ->when($course->subject_id, fn ($q, $subjectId) => $q->orderByRaw('CASE WHEN subject_id = ? THEN 0 ELSE 1 END', [(int) $subjectId]))
            ->latest()
            ->limit(300)
            ->get();

        return Inertia::render('Instructor/Quizzes/Create', [
            'course' => $course->only(['id', 'title', 'code', 'category_id', 'subject_id']),
            'lessons' => $course->lessons->map->only(['id', 'title', 'meeting_number']),
            'bankQuestions' => $bankQuestions,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'category_id']),
        ]);
    }

    public function store(StoreQuizRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $quiz = $this->quizService->createQuiz($course, $request->user(), $request->validated());

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

        $attachedIds = $quiz->questions->pluck('id');

        $bankQuestions = Question::query()
            ->with(['category:id,name', 'subject:id,name,code', 'options'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('created_by', $request->user()->id))
            ->whereNotIn('id', $attachedIds)
            ->when($course->subject_id, fn ($q, $subjectId) => $q->orderByRaw('CASE WHEN subject_id = ? THEN 0 ELSE 1 END', [(int) $subjectId]))
            ->latest()
            ->limit(300)
            ->get();

        $otherQuizzes = Quiz::query()
            ->whereKeyNot($quiz->id)
            ->whereHas('course')
            ->whereHas('questions')
            ->with('course:id,title,code,instructor_id')
            ->withCount('questions')
            ->when(
                ! $request->user()->isAdmin(),
                fn ($q) => $q->whereHas('course', fn ($c) => $c->where('instructor_id', $request->user()->id))
            )
            ->latest()
            ->limit(50)
            ->get(['id', 'course_id', 'title', 'type']);

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
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'category_id']),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'otherQuizzes' => $otherQuizzes,
            'analytics' => $analytics,
        ]);
    }

    public function update(StoreQuizRequest $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $this->quizService->updateQuiz($course, $quiz, $request->validated());

        return back()->with('success', 'Pengaturan kuis/ujian berhasil diperbarui.');
    }

    public function attachQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id,deleted_at,NULL'],
        ]);

        if (! $request->user()->isAdmin()) {
            $allowedCount = Question::query()
                ->whereIn('id', $validated['question_ids'])
                ->where(function ($q) use ($request, $course) {
                    $q->where('created_by', $request->user()->id)
                        ->orWhere('course_id', $course->id);
                })
                ->count();
            abort_unless($allowedCount === count(array_unique($validated['question_ids'])), 403, 'Sebagian soal tidak dapat diakses.');
        }

        $questions = Question::query()->whereIn('id', $validated['question_ids'])->get();
        $this->questionService->attachQuestionsToQuiz($quiz, $questions);

        return back()->with('success', count($validated['question_ids']) . ' soal dari bank soal berhasil ditambahkan ke kuis/ujian.');
    }

    public function autoPickQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'type' => ['nullable', 'in:multiple_choice,true_false,essay'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
        ]);

        $picked = $this->quizService->autoPickQuestions($quiz, $request->user(), $validated);

        if ($picked->isEmpty()) {
            return back()->with('error', 'Tidak ada soal yang cocok atau belum terpasang untuk kriteria tersebut.');
        }

        $added = $picked->count();
        $msg = $added < (int) $validated['count']
            ? "{$added} soal acak ditambahkan (stok bank soal yang cocok tersisa {$added})."
            : "{$added} soal acak berhasil ditambahkan ke kuis/ujian.";

        return back()->with('success', $msg);
    }

    public function cloneFromQuiz(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'source_quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
        ]);

        $sourceQuiz = Quiz::query()->with('questions')->findOrFail($validated['source_quiz_id']);
        Gate::authorize('manage', $sourceQuiz);

        $attached = $this->quizService->cloneFromQuiz($quiz, $sourceQuiz);

        if ($attached->isEmpty()) {
            return back()->with('error', 'Seluruh soal dari kuis sumber sudah ada di kuis ini atau kuis sumber kosong.');
        }

        return back()->with('success', "{$attached->count()} soal berhasil disalin dari \"{$sourceQuiz->title}\".");
    }

    public function importQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
            'save_to_bank' => ['boolean'],
        ]);

        $result = $this->questionSpreadsheetService->importFromUploadedFile(
            $request->file('file'),
            $request->user(),
            $course->category_id,
            $course->subject_id,
            $course,
            $quiz,
            (bool) ($validated['save_to_bank'] ?? true),
        );

        return back()->with('success', "{$result['imported_count']} soal dari file Excel berhasil ditambahkan ke kuis ini.");
    }

    public function createInlineQuestion(StoreQuestionRequest $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        DB::transaction(function () use ($request, $course, $quiz) {
            $question = $this->questionService->createQuestion($request->user(), [
                'category_id' => $request->input('category_id') ?? $course->category_id,
                'subject_id' => $request->input('subject_id') ?? $course->subject_id,
                'course_id' => $course->id,
                'type' => $request->string('type')->toString(),
                'question_text' => $request->string('question_text')->toString(),
                'explanation' => $request->input('explanation'),
                'points' => $request->float('points'),
                'difficulty' => $request->string('difficulty')->toString(),
                'is_bank' => $request->boolean('is_bank', true),
                'options' => $request->input('options', []),
            ]);

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

    public function updateQuestionPoints(Request $request, Course $course, Quiz $quiz, Question $question): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);
        abort_unless($quiz->questions()->where('questions.id', $question->id)->exists(), 404);

        $validated = $request->validate([
            'points' => ['required', 'numeric', 'min:1', 'max:500'],
        ]);

        $quiz->questions()->updateExistingPivot($question->id, [
            'points' => round((float) $validated['points'], 2),
        ]);

        return back()->with('success', 'Bobot poin soal pada kuis ini diperbarui.');
    }

    public function reorderQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $quiz);

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $this->quizService->reorderQuestions($quiz, $validated['question_ids']);

        return back()->with('success', 'Urutan soal diperbarui.');
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
