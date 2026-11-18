<?php

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
class MaterialResource extends JsonResource
{
    public function __construct(
        Material $resource,
        private readonly bool $isCompleted = false,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'type' => $this->type,
            'file_url' => $this->file_path ? url("/api/materials/{$this->id}/stream") : null,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'video_url' => $this->video_url,
            'content' => $this->content,
            'sort_order' => (int) $this->sort_order,
            'is_completed' => $this->isCompleted,
            'can_download' => $this->file_path !== null,
        ];
    }
}
