<?php

namespace App\Http\Requests\Landlord;

use App\Models\TenantOccupancy;
use App\Models\Unit;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AllocateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['landlord', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'unit_id'        => ['required', 'integer', 'exists:units,id'],
            'start_date'     => ['required', 'date'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_paid'   => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Enforce the two hard invariants BEFORE the controller's transaction:
     *   1. the unit must be available
     *   2. the tenant must have no current occupancy
     * The tenant id comes from the route ({tenant} = user_id).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = (int) $this->route('tenant');

                $unit = Unit::find($this->input('unit_id'));
                if ($unit !== null && $unit->status !== 'available') {
                    $validator->errors()->add('unit_id', 'That unit is not available.');
                }

                $hasActive = TenantOccupancy::where('tenant_id', $tenantId)
                    ->where('is_current', true)
                    ->exists();

                if ($hasActive) {
                    $validator->errors()->add('unit_id', 'This tenant already has an active occupancy.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.required'    => 'A unit must be selected.',
            'unit_id.exists'      => 'The selected unit does not exist.',
            'start_date.required' => 'A start date is required.',
        ];
    }
}
