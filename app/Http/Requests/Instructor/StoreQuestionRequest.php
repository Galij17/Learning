<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'instructor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'type' => ['required', 'in:multiple_choice,true_false,essay'],
            'question_text' => ['required', 'string', 'max:10000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'numeric', 'min:1', 'max:500'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'is_bank' => ['boolean'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['required_with:options', 'string', 'max:2000'],
            'options.*.is_correct' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            if (in_array($type, ['multiple_choice', 'true_false'], true)) {
                $options = collect($this->input('options', []));
                if ($options->count() < 2) {
                    $validator->errors()->add('options', 'Soal objektif minimal memiliki 2 pilihan jawaban.');
                }
                if ($options->where('is_correct', true)->count() !== 1) {
                    $validator->errors()->add('options', 'Pilih tepat 1 jawaban yang benar.');
                }
            }
        });
    }
}
