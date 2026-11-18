<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveAdminCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'exists:categories,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'instructor_id' => ['required', 'exists:users,id,deleted_at,NULL'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
        ];
    }
}
