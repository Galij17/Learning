<?php

namespace App\Http\Resources;

use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Grade */
class GradeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['course:id,title', 'student:id,name']);

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title ?? '',
            'student_id' => $this->student_id,
            'student_name' => $this->student?->name,
            'assignment_avg' => (float) $this->assignment_avg,
            'quiz_avg' => (float) $this->quiz_avg,
            'exam_avg' => (float) $this->exam_avg,
            'final_score' => (float) $this->final_score,
            'letter_grade' => $this->letter_grade,
            'instructor_feedback' => $this->instructor_feedback,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
