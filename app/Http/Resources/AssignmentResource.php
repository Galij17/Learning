<?php

namespace App\Http\Resources;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
class AssignmentResource extends JsonResource
{
    public function __construct(
        Assignment $resource,
        private readonly ?Submission $mySubmission = null,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('course:id,title');
        $status = $this->mySubmission?->status ?? 'upcoming';

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title ?? '',
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->due_at?->toIso8601String(),
            'max_score' => (float) $this->max_score,
            'submission_type' => $this->submission_type,
            'allow_late_submission' => (bool) $this->allow_late_submission,
            'attachment_name' => $this->attachment_name,
            'attachment_url' => $this->attachment_path ? url("/api/assignments/{$this->id}/attachment") : null,
            'status' => $status,
            'my_submission' => $this->mySubmission ? (new SubmissionResource($this->mySubmission))->resolve($request) : null,
            'submissions_count' => (int) ($this->submissions_count ?? 0),
        ];
    }
}
