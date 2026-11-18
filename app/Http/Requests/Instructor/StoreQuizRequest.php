<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'instructor']) ?? false;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'lesson_id' => [
                'nullable',
                Rule::exists('lessons', 'id')
                    ->whereNull('deleted_at')
                    ->when($courseId !== null, fn ($q) => $q->where('course_id', $courseId)),
            ],
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
            'question_ids.*' => [
                'integer',
                Rule::exists('questions', 'id')->whereNull('deleted_at'),
            ],
            'new_questions' => ['nullable', 'array', 'max:100'],
            'new_questions.*.type' => ['required', 'in:multiple_choice,true_false,essay'],
            'new_questions.*.question_text' => ['required', 'string', 'max:10000'],
            'new_questions.*.explanation' => ['nullable', 'string', 'max:5000'],
            'new_questions.*.points' => ['required', 'numeric', 'min:1', 'max:500'],
            'new_questions.*.difficulty' => ['required', 'in:easy,medium,hard'],
            'new_questions.*.options' => ['nullable', 'array'],
            'new_questions.*.options.*.option_text' => ['required_with:new_questions.*.options', 'string', 'max:2000'],
            'new_questions.*.options.*.is_correct' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $questionIds = $this->input('question_ids');
            if (! is_array($questionIds) || $questionIds === [] || $this->user()?->isAdmin()) {
                return;
            }

            $courseId = $this->route('course')?->id;
            $allowedCount = \App\Models\Question::query()
                ->whereIn('id', $questionIds)
                ->where(function ($q) use ($courseId) {
                    $q->where('created_by', $this->user()->id);
                    if ($courseId !== null) {
                        $q->orWhere('course_id', $courseId);
                    }
                })
                ->count();

            if ($allowedCount !== count(array_unique($questionIds))) {
                $validator->errors()->add('question_ids', 'Sebagian soal yang dipilih bukan milik Anda atau kelas ini.');
            }
        });
    }
}
