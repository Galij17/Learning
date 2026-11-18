<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $quizAttemptService,
    ) {}

    public function quizDetail(Request $request, Course $course, Quiz $quiz): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('view', $quiz);

        $quiz->loadCount('questions');
        $used = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->count();

        return response()->json(['data' => (new QuizResource($quiz, $used))->resolve()]);
    }

    public function startQuiz(Request $request, Course $course, Quiz $quiz): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('attempt', $quiz);

        $attempt = $this->quizAttemptService->startAttempt($quiz, $request->user());

        return response()->json(['data' => (new QuizAttemptResource($attempt, false))->resolve()]);
    }

    public function autosaveQuiz(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'essay_answer' => ['nullable', 'string', 'max:20000'],
        ]);

        $question = Question::query()->findOrFail($validated['question_id']);
        $answer = $this->quizAttemptService->saveAnswer(
            $attempt,
            $question,
            $validated['selected_option_id'] ?? null,
            $validated['essay_answer'] ?? null
        );

        return response()->json([
            'saved_at' => $answer->answered_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);
    }

    public function submitQuiz(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 403);

        $submitted = $this->quizAttemptService->submitAttempt($attempt, false);
        $showResults = (bool) $quiz->show_results;
        $showSolutions = $showResults && (bool) $quiz->show_explanations;

        return response()->json([
            'data' => (new QuizAttemptResource($submitted, $showSolutions, $showResults))->resolve(),
        ]);
    }

    public function quizResult(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 403);

        if ($attempt->status === 'in_progress') {
            if (now()->gt($attempt->expires_at)) {
                $attempt = $this->quizAttemptService->submitAttempt($attempt, true);
            } else {
                return response()->json([
                    'message' => 'Ujian masih berlangsung. Kirim jawaban terlebih dahulu sebelum melihat hasil.',
                ], 403);
            }
        }

        $showResults = (bool) $quiz->show_results;
        $showSolutions = $showResults && (bool) $quiz->show_explanations;

        return response()->json([
            'data' => (new QuizAttemptResource($attempt, $showSolutions, $showResults))->resolve(),
        ]);
    }
}
