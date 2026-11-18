<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Course */
class CourseResource extends JsonResource
{
    public function __construct(
        Course $resource,
        private readonly ?float $progress = null,
        private readonly ?string $enrollmentStatus = null,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['category:id,name', 'subject:id,name', 'instructor:id,name']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null,
            'category_name' => $this->category?->name ?? 'Umum',
            'subject_name' => $this->subject?->name,
            'instructor_id' => $this->instructor_id,
            'instructor_name' => $this->instructor?->name ?? '',
            'status' => $this->status,
            'enrollment_status' => $this->enrollmentStatus ?? 'active',
            'students_count' => (int) ($this->enrollments_count ?? $this->students_count ?? $this->enrollments()->count()),
            'materials_count' => (int) ($this->materials_count ?? $this->materials()->count()),
            'progress_percentage' => $this->progress ?? 0.0,
            'assignment_weight' => (int) $this->assignment_weight,
            'quiz_weight' => (int) $this->quiz_weight,
            'exam_weight' => (int) $this->exam_weight,
            'certificate_enabled' => (bool) $this->certificate_enabled,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
