<?php

/**
 * Provider base da aplicação onde são
 * registrados serviços globais e rotas
 * de broadcast do Laravel.
 */

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
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

        Broadcast::routes(); // <- ESSA LINHA TEM QUE ESTAR AQUI
    }
}
