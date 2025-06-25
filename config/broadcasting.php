<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => env('PUSHER_APP_SCHEME', 'https') === 'https',
                'host' => env('PUSHER_HOST', null), // normalmente null se usar pusher oficial
                'port' => env('PUSHER_PORT', null), // null para usar padrão 443/80
                'scheme' => env('PUSHER_APP_SCHEME', 'https'),
                'encrypted' => true,
            ],
            'client_options' => [],
        ],

        // outros drivers
    ],

];

