<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:80'],
            'site_tagline' => ['nullable', 'string', 'max:180'],
            'institution_name' => ['required', 'string', 'max:150'],
            'support_email' => ['required', 'email', 'max:150'],
            'allow_registration' => ['boolean'],
            'default_passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
}
