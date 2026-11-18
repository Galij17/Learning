<?php

namespace App\Http\Resources;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Certificate */
class CertificateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['course:id,title,instructor_id', 'course.instructor:id,name', 'student:id,name']);

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title ?? '',
            'student_name' => $this->student?->name ?? '',
            'certificate_number' => $this->certificate_number,
            'verification_hash' => $this->verification_hash,
            'final_score' => (float) $this->final_score,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'instructor_name' => $this->course?->instructor?->name ?? '',
            'download_url' => url("/api/certificates/{$this->id}/download"),
        ];
    }
}
