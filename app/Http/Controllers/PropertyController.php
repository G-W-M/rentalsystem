<?php

namespace App\Http\Controllers;

use App\Http\Requests\Landlord\StorePropertyRequest;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /** GET /api/landlord/properties — only this landlord's properties. */
    public function index(Request $request): JsonResponse
    {
        $properties = Property::where('landlord_id', $request->user()->id)
            ->withCount('units')
            ->latest()
            ->get();

        return response()->json($properties);
    }

    /** POST /api/landlord/properties */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = Property::create([
            ...$request->validated(),
            'landlord_id' => $request->user()->id,
            'status'      => $request->input('status', 'active'),
        ]);

        return response()->json($property, 201);
    }

    /** GET /api/landlord/properties/{property} */
    public function show(Request $request, Property $property): JsonResponse
    {
        $this->authorizeOwner($request, $property);

        return response()->json($property->load('units'));
    }

    /** PUT/PATCH /api/landlord/properties/{property} */
    public function update(StorePropertyRequest $request, Property $property): JsonResponse
    {
        $this->authorizeOwner($request, $property);

        $property->update($request->validated());

        return response()->json($property);
    }

    /** DELETE /api/landlord/properties/{property} — blocked if any unit occupied. */
    public function destroy(Request $request, Property $property): JsonResponse
    {
        $this->authorizeOwner($request, $property);

        if ($property->units()->where('status', 'occupied')->exists()) {
            return response()->json([
                'message' => 'Cannot delete a property with occupied units.',
            ], 422);
        }

        $property->delete();

        return response()->json(['message' => 'Property deleted.']);
    }

    /** A landlord may only act on their own property. */
    private function authorizeOwner(Request $request, Property $property): void
    {
        abort_unless($property->landlord_id === $request->user()->id, 403, 'Not your property.');
    }
}
