<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuizAttempt */
class QuizAttemptResource extends JsonResource
{
    public function __construct(
        QuizAttempt $resource,
        private readonly bool $includeSolutions = false,
        private readonly bool $includeQuestions = true,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['quiz.questions.options', 'answers']);
        $questions = $this->quiz->questions;
        $order = $this->question_order ?? [];

        if ($order) {
            $orderMap = array_flip($order);
            $questions = $questions->sortBy(fn ($q) => $orderMap[$q->id] ?? 9999)->values();
        }

        $optionOrders = $this->option_orders ?? [];

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'status' => $this->status,
            'objective_score' => (float) $this->objective_score,
            'essay_score' => (float) $this->essay_score,
            'total_score' => (float) $this->total_score,
            'percentage' => (float) $this->percentage,
            'is_passed' => (bool) $this->is_passed,
            'needs_manual_grading' => (bool) $this->needs_manual_grading,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'questions' => $this->includeQuestions
                ? $questions->map(
                    fn (Question $q) => (new QuestionResource($q, $this->includeSolutions, $optionOrders[$q->id] ?? null))->resolve($request)
                )->all()
                : [],
            'answers' => $this->includeQuestions
                ? $this->answers->map(fn ($a) => [
                    'question_id' => $a->question_id,
                    'selected_option_id' => $a->selected_option_id,
                    'essay_answer' => $a->essay_answer,
                    'score_awarded' => $a->score_awarded !== null ? (float) $a->score_awarded : null,
                    'feedback' => $a->feedback,
                ])->values()->all()
                : [],
        ];
    }
}
