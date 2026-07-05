<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email is required.',
            'email.email'       => 'Enter a valid email address.',
            'password.required' => 'Password is required.',
        ];
    }
}