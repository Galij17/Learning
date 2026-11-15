<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuestionRequest;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $questions = Question::query()
            ->with(['category:id,name', 'subject:id,name,code', 'options'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('question_text', 'like', "%{$s}%"))
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->integer('subject_id'), fn ($q, $id) => $q->where('subject_id', $id))
            ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Instructor/QuestionBank/Index', [
            'questions' => $questions,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'category_id']),
            'filters' => $request->only(['search', 'category_id', 'subject_id', 'type']),
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
}
