<?php

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['landlord', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'cost_estimate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_major'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost_estimate.numeric' => 'The cost estimate must be a number.',
            'cost_estimate.min'     => 'The cost estimate cannot be negative.',
        ];
    }
}