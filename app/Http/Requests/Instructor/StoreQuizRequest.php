<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'instructor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:quiz,exam'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:360'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_results' => ['boolean'],
            'show_explanations' => ['boolean'],
            'is_published' => ['boolean'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ];
    }
}
