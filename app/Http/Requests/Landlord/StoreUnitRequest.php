<?php

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['landlord', 'admin'], true);
    }

    public function rules(): array
    {
        // On update, the bound {unit} route param provides the id to ignore so a
        // unit keeping its own number does not collide with itself.
        $unitId = $this->route('unit')?->id;

        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'unit_number' => [
                'required', 'string', 'max:20',
                Rule::unique('units')
                    ->where(fn ($q) => $q->where('property_id', $this->input('property_id')))
                    ->ignore($unitId),
            ],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'status'      => ['sometimes', 'in:available,occupied,maintenance,unavailable'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.exists' => 'The selected property does not exist.',
            'unit_number.unique' => 'That unit number already exists in this property.',
            'rent_amount.min'    => 'Rent cannot be negative.',
            'status.in'          => 'The selected unit status is not valid.',
        ];
    }
}
