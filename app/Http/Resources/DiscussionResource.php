<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Discussion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Discussion */
class DiscussionResource extends JsonResource
{
    public function __construct(
        Discussion $resource,
        private readonly bool $withComments = false,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('user:id,name');
        $comments = $this->withComments
            ? $this->allComments()->with('user:id,name')->orderBy('created_at')->get()
            : collect();

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'body' => $this->body,
            'author_name' => $this->user?->name ?? '',
            'is_pinned' => (bool) $this->is_pinned,
            'is_locked' => (bool) $this->is_locked,
            'comments_count' => (int) ($this->all_comments_count ?? $this->allComments()->count()),
            'comments' => $comments->map(fn (Comment $c) => (new CommentResource($c))->resolve($request))->all(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
