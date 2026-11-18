<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuestionRequest;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Subject;
use App\Services\QuestionSpreadsheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $subjectParam = $request->input('subject_id');

        $questions = Question::query()
            ->with(['category:id,name', 'subject:id,name,code', 'options'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('question_text', 'like', "%{$s}%"))
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($subjectParam === 'unassigned', fn ($q) => $q->whereNull('subject_id'))
            ->when($subjectParam !== 'unassigned' && $request->integer('subject_id'), fn ($q, $id) => $q->where('subject_id', $id))
            ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $subjects = Subject::query()
            ->where('is_active', true)
            ->with('category:id,name')
            ->withCount(['questions' => fn ($q) => $q->when(! $user->isAdmin(), fn ($inner) => $inner->where('created_by', $user->id))])
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'category_id']);

        $unassignedCount = Question::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->whereNull('subject_id')
            ->count();

        $instructorQuizzes = Quiz::query()
            ->whereHas('course')
            ->with('course:id,title,code,instructor_id')
            ->when(
                ! $user->isAdmin(),
                fn ($q) => $q->whereHas('course', fn ($c) => $c->where('instructor_id', $user->id))
            )
            ->latest()
            ->limit(50)
            ->get(['id', 'course_id', 'title', 'type']);

        return Inertia::render('Instructor/QuestionBank/Index', [
            'questions' => $questions,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'subjects' => $subjects,
            'unassignedCount' => $unassignedCount,
            'instructorQuizzes' => $instructorQuizzes,
            'filters' => $request->only(['search', 'category_id', 'subject_id', 'type', 'view']),
        ]);
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $question = Question::query()->create([
                'category_id' => $request->input('category_id'),
                'subject_id' => $request->input('subject_id'),
                'course_id' => $request->input('course_id'),
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
        });

        return back()->with('success', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function update(StoreQuestionRequest $request, Question $question): RedirectResponse
    {
        if (! $request->user()->isAdmin() && (int) $question->created_by !== (int) $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($request, $question) {
            $question->update([
                'category_id' => $request->input('category_id'),
                'subject_id' => $request->input('subject_id'),
                'type' => $request->string('type')->toString(),
                'question_text' => $request->string('question_text')->toString(),
                'explanation' => $request->input('explanation'),
                'points' => $request->float('points'),
                'difficulty' => $request->string('difficulty')->toString(),
            ]);

            $question->options()->delete();
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
        });

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        if (! $request->user()->isAdmin() && (int) $question->created_by !== (int) $request->user()->id) {
            abort(403);
        }

        $question->delete();

        return back()->with('success', 'Soal dihapus dari bank soal.');
    }

    public function downloadTemplate(QuestionSpreadsheetService $spreadsheetService): StreamedResponse
    {
        return $spreadsheetService->downloadTemplate();
    }

    public function previewImport(Request $request, QuestionSpreadsheetService $spreadsheetService): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        return response()->json(
            $spreadsheetService->previewFromUploadedFile($request->file('file'))
        );
    }

    public function importExcel(Request $request, QuestionSpreadsheetService $spreadsheetService): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        $subjectId = $validated['subject_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;
        if ($subjectId && ! $categoryId) {
            $categoryId = Subject::query()->whereKey($subjectId)->value('category_id');
        }

        $result = $spreadsheetService->importFromUploadedFile(
            $request->file('file'),
            $request->user(),
            $categoryId ? (int) $categoryId : null,
            $subjectId ? (int) $subjectId : null,
        );

        return back()->with('success', "{$result['imported_count']} soal berhasil diimpor ke bank soal.");
    }

    public function duplicate(Request $request, Question $question): RedirectResponse
    {
        if (! $request->user()->isAdmin() && (int) $question->created_by !== (int) $request->user()->id) {
            abort(403);
        }

        $question->load('options');

        DB::transaction(function () use ($request, $question) {
            $copy = Question::query()->create([
                'category_id' => $question->category_id,
                'subject_id' => $question->subject_id,
                'course_id' => $question->course_id,
                'created_by' => $request->user()->id,
                'type' => $question->type,
                'question_text' => $question->question_text . ' (Salinan)',
                'explanation' => $question->explanation,
                'points' => $question->points,
                'difficulty' => $question->difficulty,
                'is_bank' => true,
            ]);

            foreach ($question->options as $opt) {
                QuestionOption::query()->create([
                    'question_id' => $copy->id,
                    'option_text' => $opt->option_text,
                    'is_correct' => $opt->is_correct,
                    'sort_order' => $opt->sort_order,
                ]);
            }
        });

        return back()->with('success', 'Soal berhasil diduplikasi.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:delete,move_subject,attach_to_quiz'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id,deleted_at,NULL'],
            'subject_id' => ['required_if:action,move_subject', 'nullable', 'integer', 'exists:subjects,id'],
            'quiz_id' => ['required_if:action,attach_to_quiz', 'nullable', 'integer', 'exists:quizzes,id'],
        ]);

        $user = $request->user();
        $questions = Question::query()
            ->whereIn('id', $validated['question_ids'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->get();

        abort_unless($questions->count() === count(array_unique($validated['question_ids'])), 403);

        $action = $validated['action'];

        if ($action === 'delete') {
            Question::query()->whereIn('id', $questions->pluck('id'))->delete();

            return back()->with('success', "{$questions->count()} soal berhasil dihapus dari bank soal.");
        }

        if ($action === 'move_subject') {
            $subject = Subject::query()->findOrFail($validated['subject_id']);
            Question::query()
                ->whereIn('id', $questions->pluck('id'))
                ->update([
                    'subject_id' => $subject->id,
                    'category_id' => $subject->category_id,
                ]);

            return back()->with('success', "{$questions->count()} soal dipindahkan ke {$subject->code} — {$subject->name}.");
        }

        /** @var Quiz $quiz */
        $quiz = Quiz::query()->findOrFail($validated['quiz_id']);
        Gate::authorize('manage', $quiz);

        DB::transaction(function () use ($quiz, $questions) {
            $currentCount = $quiz->questions()->count();
            $attachData = [];
            foreach ($questions->values() as $idx => $question) {
                $attachData[$question->id] = [
                    'sort_order' => $currentCount + $idx + 1,
                    'points' => $question->points,
                ];
            }
            $quiz->questions()->syncWithoutDetaching($attachData);
        });

        return back()->with('success', "{$questions->count()} soal dimasukkan ke kuis \"{$quiz->title}\".");
    }
}
