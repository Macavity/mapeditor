<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportTileSetRequest;
use App\Http\Resources\TileSetResource;
use App\Models\TileSet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TileSetController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tileSets = TileSet::all();
        return TileSetResource::collection($tileSets);
    }

    public function store(Request $request): TileSetResource
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'imageWidth' => 'required|integer|min:1',
            'imageHeight' => 'required|integer|min:1',
            'tileWidth' => 'required|integer|min:1',
            'tileHeight' => 'required|integer|min:1',
            'tileCount' => 'required|integer|min:1',
            'firstGid' => 'required|integer|min:1',
            'margin' => 'integer|min:0',
            'spacing' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tileSet = TileSet::create([
            'name' => $request->name,
            'image_width' => $request->imageWidth,
            'image_height' => $request->imageHeight,
            'tile_width' => $request->tileWidth,
            'tile_height' => $request->tileHeight,
            'tile_count' => $request->tileCount,
            'first_gid' => $request->firstGid,
            'margin' => $request->margin ?? 0,
            'spacing' => $request->spacing ?? 0,
        ]);

        session()->flash('success', 'Tile set created successfully.');
        return new TileSetResource($tileSet);
    }

    public function show(string $uuid): TileSetResource
    {
        $tileSet = TileSet::where('uuid', $uuid)->firstOrFail();
        return new TileSetResource($tileSet);
    }

    public function update(Request $request, string $uuid): TileSetResource
    {
        $tileSet = TileSet::where('uuid', $uuid)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'imageWidth' => 'integer|min:1',
            'imageHeight' => 'integer|min:1',
            'tileWidth' => 'integer|min:1',
            'tileHeight' => 'integer|min:1',
            'tileCount' => 'integer|min:1',
            'firstGid' => 'integer|min:1',
            'margin' => 'integer|min:0',
            'spacing' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tileSet->update([
            'name' => $request->name ?? $tileSet->name,
            'image_width' => $request->imageWidth ?? $tileSet->image_width,
            'image_height' => $request->imageHeight ?? $tileSet->image_height,
            'tile_width' => $request->tileWidth ?? $tileSet->tile_width,
            'tile_height' => $request->tileHeight ?? $tileSet->tile_height,
            'tile_count' => $request->tileCount ?? $tileSet->tile_count,
            'first_gid' => $request->firstGid ?? $tileSet->first_gid,
            'margin' => $request->margin ?? $tileSet->margin,
            'spacing' => $request->spacing ?? $tileSet->spacing,
        ]);

        session()->flash('success', 'Tile set updated successfully.');
        return new TileSetResource($tileSet);
    }

    public function destroy(string $uuid): Response
    {
        $tileSet = TileSet::where('uuid', $uuid)->firstOrFail();
        $tileSet->delete();

        session()->flash('success', 'Tile set deleted successfully.');
        return response()->noContent();
    }

    public function import(ImportTileSetRequest $request): TileSetResource
    {
        $image = $request->file('image');
        $imagePath = $image->store('tilesets', 'public');
        
        // Get image dimensions
        $manager = new ImageManager(new Driver());
        $imageInfo = $manager->read(Storage::disk('public')->path($imagePath));
        $imageWidth = $imageInfo->width();
        $imageHeight = $imageInfo->height();
        
        // Calculate tile count based on image dimensions and tile size
        $tileCount = floor($imageWidth / $request->tileWidth) * floor($imageHeight / $request->tileHeight);
        
        $tileSet = TileSet::create([
            'uuid' => (string) Str::uuid(),
            'name' => $request->name,
            'image_path' => $imagePath,
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'tile_width' => $request->tileWidth,
            'tile_height' => $request->tileHeight,
            'tile_count' => $tileCount,
            'margin' => 0,
            'spacing' => 0,
            'first_gid' => 1, // Default value for new tilesets
        ]);

        session()->flash('success', 'Tile set imported successfully.');
        return new TileSetResource($tileSet);
    }
} 