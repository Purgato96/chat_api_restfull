<?php

/**
 * Configura os canais e a autenticação
 * utilizados pelo broadcasting de eventos
 * em tempo real via Pusher.
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar as rotas de broadcasting com o middleware 'auth:sanctum' ou 'auth' para proteger o endpoint de autenticação
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Registrar os canais definidos em routes/channels.php
        require base_path('routes/channels.php');
    }
}
