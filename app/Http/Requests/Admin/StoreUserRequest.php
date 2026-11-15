<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'identity_number' => ['nullable', 'string', 'max:50', 'unique:users,identity_number'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'string', 'in:admin,instructor,student'],
            'is_active' => ['boolean'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ];
    }
}
