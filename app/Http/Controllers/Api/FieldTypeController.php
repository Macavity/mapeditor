<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FieldType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FieldTypeController extends Controller
{
    /**
     * Display a listing of field types.
     */
    public function index(): JsonResponse
    {
        $fieldTypes = FieldType::orderBy('name')->get();

        return response()->json([
            'data' => $fieldTypes,
        ]);
    }

    /**
     * Store a newly created field type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:field_types,name',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i|max:7',
        ]);

        $fieldType = FieldType::create($validated);

        return response()->json([
            'data' => $fieldType,
        ], 201);
    }

    /**
     * Display the specified field type.
     */
    public function show(FieldType $fieldType): JsonResponse
    {
        return response()->json([
            'data' => $fieldType,
        ]);
    }

    /**
     * Update the specified field type.
     */
    public function update(Request $request, FieldType $fieldType): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('field_types', 'name')->ignore($fieldType->id),
            ],
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i|max:7',
        ]);

        $fieldType->update($validated);

        return response()->json([
            'data' => $fieldType->fresh(),
        ]);
    }

    /**
     * Remove the specified field type.
     */
    public function destroy(FieldType $fieldType): JsonResponse
    {
        $usageCount = DB::table('layers')
                ->where('type', 'field_type')
                ->whereRaw('JSON_CONTAINS(data, ?)', [json_encode(['field_type_id' => $fieldType->id])])
                ->count();

        if ($usageCount > 0) {
            return response()->json([
                'error' => 'Cannot delete field type that is being used in layers',
            ], 422);
        }

        $fieldType->delete();

        return response()->json(null, 204);
    }
}
