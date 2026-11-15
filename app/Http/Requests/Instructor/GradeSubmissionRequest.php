<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'instructor']) ?? false;
    }

    public function rules(): array
    {
        $maxScore = (float) ($this->route('submission')?->assignment?->max_score ?? 100);

        return [
            'score' => ['required', 'numeric', 'min:0', "max:{$maxScore}"],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
