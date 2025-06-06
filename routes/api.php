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
    
    Route::apiResource('tile-sets', TileSetController::class);
    Route::post('tile-sets/import', [TileSetController::class, 'import']);
}); 