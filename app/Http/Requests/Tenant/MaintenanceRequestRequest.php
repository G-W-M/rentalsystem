<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'tenant';
    }

    public function rules(): array
    {
        return [
            'category'    => ['required', 'in:plumbing,electrical,structural,appliance,pest,security,other'],
            'subject'     => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority'    => ['sometimes', 'in:low,medium,high,emergency'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required'    => 'Please select a maintenance category.',
            'category.in'          => 'The selected category is not valid.',
            'description.required' => 'Please describe the issue.',
            'priority.in'          => 'The selected priority is not valid.',
        ];
    }
}