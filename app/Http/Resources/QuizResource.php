<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quiz */
class QuizResource extends JsonResource
{
    public function __construct(
        Quiz $resource,
        private readonly int $attemptsUsed = 0,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('course:id,title');

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title ?? '',
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'duration_minutes' => (int) $this->duration_minutes,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'max_attempts' => (int) $this->max_attempts,
            'passing_score' => (float) $this->passing_score,
            'shuffle_questions' => (bool) $this->shuffle_questions,
            'questions_count' => (int) ($this->questions_count ?? $this->questions()->count()),
            'attempts_used' => $this->attemptsUsed,
            'is_published' => (bool) $this->is_published,
        ];
    }
}
