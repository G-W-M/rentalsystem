<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaretakerController extends Controller
{
    /**
     * GET /api/caretaker/properties
     * Properties belonging to this caretaker's landlord (read-only view —
     * caretakers don't manage properties, they just need to see which ones
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
}
