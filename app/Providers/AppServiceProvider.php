<?php

namespace App\Providers;

use App\Repositories\TileSetRepository;
use App\Services\TileSetService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TileSetRepository as a singleton
        $this->app->singleton(TileSetRepository::class, function ($app) {
            return new TileSetRepository();
        });

        // Register TileSetService as a singleton
        $this->app->singleton(TileSetService::class, function ($app) {
            return new TileSetService($app->make(TileSetRepository::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
