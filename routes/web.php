<?php

use App\Http\Controllers\LayerImageController;
use App\Http\Controllers\MapTestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('manage-maps', function () {
        return Inertia::render('manage-maps');
    })->name('manage-maps');

    Route::get('import', function () {
        return Inertia::render('import.ImportWizard');
    })->name('manage-maps.import');

    Route::get('manage-tilesets', function () {
        return Inertia::render('manage-tilesets');
    })->name('manage-tilesets');

    Route::get('manage-field-types', function () {
        return Inertia::render('manage-field-types');
    })->name('manage-field-types');

    Route::get('manage-object-types', function () {
        return Inertia::render('manage-object-types');
    })->name('manage-object-types');

    Route::get('settings/api-tokens', function () {
        return Inertia::render('settings.api-tokens');
    })->name('settings.api-tokens');

    Route::get('maps/{uuid}/edit', function (string $uuid) {
        return Inertia::render('maps.edit', [
            'uuid' => $uuid
        ]);
    })->name('maps.edit');

    Route::get('maps/{uuid}/test', [MapTestController::class, 'show'])->name('maps.test');

    // User management routes (admin only)
    Route::resource('manage-users', UserController::class)->names([
        'index' => 'manage-users.index',
        'create' => 'manage-users.create',
        'store' => 'manage-users.store',
        'edit' => 'manage-users.edit',
        'update' => 'manage-users.update',
        'destroy' => 'manage-users.destroy',
    ]);
    Route::patch('manage-users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('manage-users.toggle-admin');
});

// Public route for serving layer images (no auth required)
Route::get('layers/{uuid}.png', [LayerImageController::class, 'show'])->name('layers.image');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
