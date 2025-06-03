<?php

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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
