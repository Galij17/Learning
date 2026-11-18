<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\QuestionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    public function __construct(
        private readonly QuestionManagementService $questionService,
    ) {}

    public function questionBank(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->string('type')->toString();

        $items = Question::query()
            ->with('options')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest()
            ->get()
            ->map(fn (Question $q) => (new QuestionResource($q, true))->resolve())
            ->values();

        return response()->json(['data' => $items]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:multiple_choice,true_false,essay'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:1'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'explanation' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ]);

        $question = $this->questionService->createQuestion($request->user(), [
            'type' => $validated['type'],
            'question_text' => $validated['question_text'],
            'points' => $validated['points'],
            'difficulty' => $validated['difficulty'] ?? 'medium',
            'explanation' => $validated['explanation'] ?? null,
            'is_bank' => true,
            'options' => $validated['options'] ?? [],
        ]);

        return response()->json(['data' => (new QuestionResource($question->fresh('options'), true))->resolve()], 201);
    }
}
