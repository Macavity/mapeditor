<?php

use App\Http\Controllers\Api\TileMapController;
use App\Http\Controllers\Api\TileSetController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tile-maps', TileMapController::class);
    Route::apiResource('tile-sets', TileSetController::class);
    Route::post('tile-sets/import', [TileSetController::class, 'import']);
}); 