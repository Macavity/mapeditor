<?php

use App\Http\Controllers\Api\TileMapController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tile-maps', TileMapController::class);
}); 