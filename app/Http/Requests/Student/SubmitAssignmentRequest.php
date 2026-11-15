<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        return [
            'text_answer' => ['nullable', 'string', 'max:30000'],
            'file' => ['nullable', 'file', 'max:25600', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $assignment = $this->route('assignment');
            if (! $assignment) {
                return;
            }

            if ($assignment->submission_type === 'file' && ! $this->hasFile('file')) {
                $validator->errors()->add('file', 'Tugas ini mewajibkan pengumpulan berkas.');
            } elseif ($assignment->submission_type === 'text' && ! $this->filled('text_answer')) {
                $validator->errors()->add('text_answer', 'Tugas ini mewajibkan jawaban teks.');
            } elseif ($assignment->submission_type === 'both' && ! $this->hasFile('file') && ! $this->filled('text_answer')) {
                $validator->errors()->add('text_answer', 'Isi jawaban teks atau unggah berkas pengumpulan.');
            }
        });
    }
}
