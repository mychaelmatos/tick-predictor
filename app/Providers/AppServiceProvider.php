<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use App\Deriv\DerivClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Quem pedir um DerivClient recebe um já apontado para a URL do config.
        $this->app->bind(DerivClient::class, fn () => new DerivClient(config('services.deriv.ws_url')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
