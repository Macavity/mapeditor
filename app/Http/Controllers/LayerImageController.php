<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Layer;
use App\Services\TileMapGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LayerImageController extends Controller
{
    public function __construct(
        private readonly TileMapGenerator $tileMapGenerator
    ) {}

    public function show(Request $request, string $uuid): Response|StreamedResponse|BinaryFileResponse
    {
        $layer = Layer::with('tileMap')->where('uuid', $uuid)->firstOrFail();
        
        // Check if we need to force refresh
        $forceRefresh = $request->boolean('refresh', false);
        
        // Check if image exists and is up to date
        $imageExists = $this->tileMapGenerator->layerImageExists($layer);
        $imageUpToDate = $this->tileMapGenerator->isLayerImageUpToDate($layer);
        
        Log::info("Layer image request", [
            'layer_uuid' => $uuid,
            'layer_id' => $layer->id,
            'image_path' => $layer->image_path,
            'image_exists' => $imageExists,
            'image_up_to_date' => $imageUpToDate,
            'force_refresh' => $forceRefresh
        ]);
        
        // Generate image if it doesn't exist, is stale, or if refresh is requested
        if (!$imageExists || !$imageUpToDate || $forceRefresh) {
            try {
                Log::info("Generating single layer image", ['layer_id' => $layer->id]);
                $this->tileMapGenerator->generateLayerImage($layer);
                
                // Refresh the layer model to get the updated image_path
                $layer->refresh();
                
                Log::info("After generation", [
                    'layer_id' => $layer->id,
                    'new_image_path' => $layer->image_path,
                    'storage_exists' => $layer->image_path ? Storage::disk('public')->exists($layer->image_path) : false
                ]);
                
                // Check if generation was successful
                if (!$this->tileMapGenerator->layerImageExists($layer)) {
                    Log::error("Failed to generate layer image", [
                        'layer_id' => $layer->id,
                        'image_path' => $layer->image_path
                    ]);
                    abort(500, 'Failed to generate layer image');
                }
            } catch (\Exception $e) {
                Log::error("Error generating layer image", [
                    'layer_id' => $layer->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                abort(500, 'Error generating layer image: ' . $e->getMessage());
            }
        }
        
        // Return the image file - use the public disk since that's where TileMapGenerator saves images
        $imagePath = Storage::disk('public')->path($layer->image_path);
        
        Log::info("Attempting to serve image", [
            'layer_id' => $layer->id,
            'image_path' => $layer->image_path,
            'full_path' => $imagePath,
            'file_exists' => file_exists($imagePath)
        ]);
        
        if (!file_exists($imagePath)) {
            Log::error("Image file not found on filesystem", [
                'layer_id' => $layer->id,
                'image_path' => $layer->image_path,
                'full_path' => $imagePath
            ]);
            abort(404, 'Layer image not found');
        }
        
        // Get file info
        $mimeType = mime_content_type($imagePath);
        
        Log::info("Serving image successfully", [
            'layer_id' => $layer->id,
            'mime_type' => $mimeType,
            'file_size' => filesize($imagePath)
        ]);
        
        // Return the image as a streamed response
        return response()->file($imagePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour
            'Last-Modified' => gmdate('D, d M Y H:i:s T', filemtime($imagePath)),
        ]);
    }
}
