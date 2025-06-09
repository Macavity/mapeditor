<?php

use App\Http\Controllers\Api\TileMapController;
use App\Http\Controllers\Api\TileSetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // User endpoint for authentication verification
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::apiResource('tile-maps', TileMapController::class);
    Route::get('tile-maps/{tile_map}/layers', [TileMapController::class, 'layers']);
    Route::get('tile-maps/{tile_map}/layer-counts', [TileMapController::class, 'getLayerCounts']);
    Route::post('tile-maps/{tile_map}/layers/sky', [TileMapController::class, 'createSkyLayer']);
    Route::post('tile-maps/{tile_map}/layers/floor', [TileMapController::class, 'createFloorLayer']);
    Route::put('tile-maps/{tile_map}/layers', [TileMapController::class, 'updateLayers']);
    Route::put('tile-maps/{tile_map}/layers/{layer}', [TileMapController::class, 'updateLayer']);
    Route::put('tile-maps/{tile_map}/layers/{layer}/data', [TileMapController::class, 'updateLayerData']);
    Route::delete('tile-maps/{tile_map}/layers/{layer}', [TileMapController::class, 'deleteLayer']);
    
    Route::apiResource('tile-sets', TileSetController::class);
    Route::post('tile-sets/import', [TileSetController::class, 'import']);
}); 