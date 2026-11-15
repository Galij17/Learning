<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:20000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'submission_type' => ['required', 'in:both,file,text'],
            'allow_late_submission' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }
}
