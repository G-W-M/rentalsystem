<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaretakerController extends Controller
{
    /**
     * GET /api/caretaker/properties
     * Properties belonging to this caretaker's landlord (read-only —
     * caretakers don't manage properties, they need to see which ones
     * they're responsible for).
     */
    public function properties(Request $request): JsonResponse
    {
        $caretaker = Caretaker::find($request->user()->id);

        if ($caretaker === null) {
            return response()->json(['message' => 'Caretaker profile not found.'], 404);
        }

        $properties = Property::where('landlord_id', $caretaker->landlord_id)
            ->withCount('units')
            ->get(['id', 'name', 'address', 'property_type', 'status']);

        return response()->json($properties);
    }

    /**
     * GET /api/caretaker/units
     * Units (with their occupying tenant, if any) across this caretaker's
     * landlord's properties — lets the caretaker see what they're
     * responsible for and who lives where.
     */
    public function units(Request $request): JsonResponse
    {
        $caretaker = Caretaker::find($request->user()->id);

        if ($caretaker === null) {
            return response()->json(['message' => 'Caretaker profile not found.'], 404);
        }

        $units = Unit::whereHas('property', fn ($q) =>
                $q->where('landlord_id', $caretaker->landlord_id))
            ->with('property:id,name')
            ->with('activeOccupancy.tenant.user:id,full_name,phone')
            ->get();

        return response()->json($units);
    }
}
