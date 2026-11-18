<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $discussionId = $this->route('discussion')?->id ?? $this->route('discussion');

        return [
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                Rule::exists('comments', 'id')->where('discussion_id', $discussionId)->whereNull('deleted_at'),
            ],
        ];
    }
}
