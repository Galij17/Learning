<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Question */
class QuestionResource extends JsonResource
{
    /** @param array<int, int>|null $optionOrder */
    public function __construct(
        Question $resource,
        private readonly bool $includeSolutions = false,
        private readonly ?array $optionOrder = null,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('options');
        $options = $this->options;

        if ($this->optionOrder) {
            $orderMap = array_flip($this->optionOrder);
            $options = $options->sortBy(fn ($o) => $orderMap[$o->id] ?? $o->sort_order)->values();
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'question_text' => $this->question_text,
            'points' => (float) ($this->pivot?->points ?? $this->points),
            'difficulty' => $this->difficulty,
            'explanation' => $this->includeSolutions ? $this->explanation : null,
            'options' => $options->map(fn ($o) => [
                'id' => $o->id,
                'option_text' => $o->option_text,
                'sort_order' => (int) $o->sort_order,
                'is_correct' => $this->includeSolutions ? (bool) $o->is_correct : null,
            ])->values()->all(),
        ];
    }
}
