<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TileMapResource;
use App\Models\TileMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TileMapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $tileMaps = TileMap::with(['creator', 'layers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return TileMapResource::collection($tileMaps)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
            'tile_width' => 'required|integer|min:1',
            'tile_height' => 'required|integer|min:1',
        ]);

        $tileMap = new TileMap($validated);
        $tileMap->creator()->associate(Auth::user());
        $tileMap->save();

        return (new TileMapResource($tileMap->load(['creator', 'layers'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $tileMap = TileMap::with(['creator', 'layers'])->findOrFail($id);
        
        return (new TileMapResource($tileMap))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tileMap = TileMap::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'width' => 'sometimes|integer|min:1',
            'height' => 'sometimes|integer|min:1',
            'tile_width' => 'sometimes|integer|min:1',
            'tile_height' => 'sometimes|integer|min:1',
        ]);

        $tileMap->update($validated);

        return (new TileMapResource($tileMap->load(['creator', 'layers'])))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $tileMap = TileMap::findOrFail($id);
        $tileMap->delete();

        return response()->json(null, 204);
    }
}
