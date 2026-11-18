<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class LessonResource extends JsonResource
{
    /** @param array<int, int> $completedMaterialIds */
    public function __construct(
        Lesson $resource,
        private readonly array $completedMaterialIds = [],
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('materials');

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'meeting_number' => (int) $this->meeting_number,
            'sort_order' => (int) $this->sort_order,
            'materials' => $this->materials
                ->where('is_published', true)
                ->values()
                ->map(fn (Material $m) => (new MaterialResource($m, in_array($m->id, $this->completedMaterialIds, true)))->resolve($request))
                ->all(),
        ];
    }
}
