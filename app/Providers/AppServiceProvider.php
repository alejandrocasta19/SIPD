<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Fechas relativas en español: "hace 5 minutos", "hace 2 horas", etc.
        Carbon::setLocale('es');

        // Paginación en español
        Paginator::useBootstrap();
    }
}
