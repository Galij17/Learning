<?php

namespace App\Http\Resources;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Submission */
class SubmissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('student:id,name');

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'student_id' => $this->student_id,
            'student_name' => $this->student?->name,
            'text_answer' => $this->text_answer,
            'file_name' => $this->file_name,
            'file_url' => $this->file_path ? url("/api/submissions/{$this->id}/download") : null,
            'file_size' => $this->file_size,
            'status' => $this->status,
            'score' => $this->score !== null ? (float) $this->score : null,
            'feedback' => $this->feedback,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'graded_at' => $this->graded_at?->toIso8601String(),
        ];
    }
}
