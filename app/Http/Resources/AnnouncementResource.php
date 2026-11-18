<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
class AnnouncementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['author:id,name', 'course:id,title']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title,
            'author_name' => $this->author?->name ?? '',
            'is_pinned' => (bool) $this->is_pinned,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
