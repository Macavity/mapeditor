<?php

use App\Http\Controllers\LayerImageController;
use App\Http\Controllers\MapTestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('manage-maps', function () {
        return Inertia::render('ManageMaps');
    })->name('manage-maps');

    Route::get('manage-tilesets', function () {
        return Inertia::render('ManageTilesets');
    })->name('manage-tilesets');

    Route::get('manage-field-types', function () {
        return Inertia::render('ManageFieldTypes');
    })->name('manage-field-types');

    Route::get('settings/api-tokens', function () {
        return Inertia::render('Settings/ApiTokens');
    })->name('settings.api-tokens');

    Route::get('maps/{uuid}/edit', function (string $uuid) {
        return Inertia::render('MapEditor', [
            'uuid' => $uuid
        ]);
    })->name('maps.edit');

    Route::get('maps/{uuid}/test', [MapTestController::class, 'show'])->name('maps.test');
});

// Public route for serving layer images (no auth required)
Route::get('layers/{uuid}.png', [LayerImageController::class, 'show'])->name('layers.image');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
