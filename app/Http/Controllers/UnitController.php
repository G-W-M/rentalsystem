<?php

namespace App\Http\Controllers;

use App\Http\Requests\Landlord\StoreUnitRequest;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /** GET /api/landlord/units — units across this landlord's properties. */
    public function index(Request $request): JsonResponse
    {
        $units = Unit::whereHas('property', fn ($q) => $q->where('landlord_id', $request->user()->id))
            ->with('property:id,name')
            ->with('activeOccupancy.tenant.user:id,full_name')
            ->latest()
            ->get();

        return response()->json($units);
    }

    /** POST /api/landlord/units */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->ownProperty($request, (int) $request->input('property_id'));

        $unit = Unit::create([
            ...$request->validated(),
            'status' => $request->input('status', 'available'),
        ]);

        return response()->json($unit, 201);
    }

    /** GET /api/landlord/units/{unit} */
    public function show(Request $request, Unit $unit): JsonResponse
    {
        $this->ownProperty($request, $unit->property_id);

        return response()->json(
            $unit->load('property:id,name', 'activeOccupancy.tenant.user:id,full_name')
        );
    }

    /** PUT/PATCH /api/landlord/units/{unit} */
    public function update(StoreUnitRequest $request, Unit $unit): JsonResponse
    {
        $this->ownProperty($request, $unit->property_id);

        $unit->update($request->validated());

        return response()->json($unit);
    }

    /** DELETE /api/landlord/units/{unit} — blocked if occupied. */
    public function destroy(Request $request, Unit $unit): JsonResponse
    {
        $this->ownProperty($request, $unit->property_id);

        if ($unit->status === 'occupied') {
            return response()->json(['message' => 'Cannot delete an occupied unit.'], 422);
        }

        $unit->delete();

        return response()->json(['message' => 'Unit deleted.']);
    }

    private function ownProperty(Request $request, int $propertyId): void
    {
        $owns = Property::where('id', $propertyId)
            ->where('landlord_id', $request->user()->id)
            ->exists();

        abort_unless($owns, 403, 'That property is not yours.');
    }
}
