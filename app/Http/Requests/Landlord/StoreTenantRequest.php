<?php

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['landlord', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            // user account (no self-registration; landlord creates the tenant)
            'full_name' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'username'  => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', 'string', 'min:6'],

            // tenant profile
            'id_number'         => ['nullable', 'string', 'max:50'],
            'nationality'       => ['nullable', 'string', 'max:100'],
            'gender'            => ['nullable', 'in:male,female,other'],
            'employment_status' => ['nullable', 'in:employed,self-employed,student,retired'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_phone'   => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'   => 'The tenant name is required.',
            'email.unique'         => 'A user with this email already exists.',
            'username.unique'      => 'That username is taken.',
            'password.min'         => 'Password must be at least 6 characters.',
            'gender.in'            => 'The selected gender is not valid.',
            'employment_status.in' => 'The selected employment status is not valid.',
        ];
    }
}
