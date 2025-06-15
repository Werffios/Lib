<?php

namespace App\Providers;

use App\Models\Sale;
use App\Models\Movement;
use App\Observers\SaleObserver;
use App\Observers\MovementObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sale::observe(SaleObserver::class);
        Movement::observe(MovementObserver::class);
    }
}
