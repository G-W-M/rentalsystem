<?php

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['landlord', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'address'       => ['required', 'string'],
            'property_type' => ['required', 'in:apartment,house,commercial,office'],
            'status'        => ['sometimes', 'in:active,inactive,maintenance'],
            'description'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'The property name is required.',
            'address.required'       => 'The property address is required.',
            'property_type.in'       => 'The selected property type is not valid.',
            'status.in'              => 'The selected status is not valid.',
        ];
    }
}
