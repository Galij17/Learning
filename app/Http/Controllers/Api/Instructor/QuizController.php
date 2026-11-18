<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Question;
use App\Services\QuizManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizManagementService $quizService,
    ) {}

    public function storeQuiz(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:quiz,exam'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id,deleted_at,NULL'],
        ]);

        $questionIds = $validated['question_ids'] ?? [];
        if ($questionIds !== [] && ! $request->user()->isAdmin()) {
            $allowedCount = Question::query()
                ->whereIn('id', $questionIds)
                ->where(fn ($q) => $q->where('created_by', $request->user()->id)->orWhere('course_id', $course->id))
                ->count();
            if ($allowedCount !== count(array_unique($questionIds))) {
                return response()->json(['message' => 'Sebagian soal yang dipilih bukan milik Anda atau kelas ini.'], 403);
            }
        }

        $quiz = $this->quizService->createQuiz($course, $request->user(), array_merge($validated, [
            'is_published' => true,
        ]));
        $quiz->loadCount('questions');

        return response()->json(['data' => (new QuizResource($quiz))->resolve()], 201);
    }
}
