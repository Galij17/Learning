<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'instructor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
            'assignment_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'quiz_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'min_completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'min_passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'certificate_enabled' => ['boolean'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sum = (int) $this->input('assignment_weight', 0)
                + (int) $this->input('quiz_weight', 0)
                + (int) $this->input('exam_weight', 0);

            if ($sum !== 100) {
                $validator->errors()->add('assignment_weight', 'Total bobot Tugas, Kuis, dan Ujian harus tepat 100%.');
            }
        });
    }
}
