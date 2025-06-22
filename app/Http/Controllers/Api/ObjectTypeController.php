<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObjectType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ObjectTypeController extends Controller
{
    /**
     * Display a listing of object types.
     */
    public function index(): JsonResponse
    {
        $objectTypes = ObjectType::orderBy('name')->get();

        return response()->json([
            'data' => $objectTypes,
        ]);
    }

    /**
     * Store a newly created object type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:object_types,name',
            'type' => 'nullable|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i|max:7',
            'description' => 'nullable|string|max:1000',
            'is_solid' => 'boolean',
        ]);

        $objectType = ObjectType::create($validated);

        return response()->json([
            'data' => $objectType,
        ], 201);
    }

    /**
     * Display the specified object type.
     */
    public function show(ObjectType $objectType): JsonResponse
    {
        return response()->json([
            'data' => $objectType,
        ]);
    }

    /**
     * Update the specified object type.
     */
    public function update(Request $request, ObjectType $objectType): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('object_types', 'name')->ignore($objectType->id),
            ],
            'type' => 'nullable|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i|max:7',
            'description' => 'nullable|string|max:1000',
            'is_solid' => 'boolean',
        ]);

        $objectType->update($validated);

        return response()->json([
            'data' => $objectType->fresh(),
        ]);
    }

    /**
     * Remove the specified object type.
     */
    public function destroy(ObjectType $objectType): JsonResponse
    {
        // Check if this object type is being used in any layers
        $usageCount = DB::table('layers')
            ->where('type', 'object')
            ->whereJsonLength('data', '>', 0)
            ->get()
            ->filter(function ($layer) use ($objectType) {
                $data = json_decode($layer->data, true);
                if (!is_array($data)) return false;
                
                return collect($data)->contains(function ($item) use ($objectType) {
                    return isset($item['objectType']) && $item['objectType'] === $objectType->id;
                });
            })
            ->count();

        if ($usageCount > 0) {
            return response()->json([
                'message' => 'Cannot delete object type that is being used in layers',
            ], 422);
        }

        $objectType->delete();

        return response()->json([
            'message' => 'Object type deleted successfully',
        ]);
    }
} 