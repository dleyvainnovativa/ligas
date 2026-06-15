<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tier definitions
    |--------------------------------------------------------------------------
    |
    | Each tier defines the limits a manager can use. `null` means unlimited.
    | The defaults below are conservative placeholders; your client decides
    | the real numbers based on market pricing.
    |
    */

    'free' => [
        'label'       => 'Free',
        'price_label' => 'Gratis',
        'tagline'     => 'Para probar el sistema con una liga pequeña.',
        'highlight_color' => '#9aa0a6',
        'limits' => [
            'active_leagues'        => 1,
            'players_per_league'    => 12,
            'jornadas_per_league'   => 3,
        ],
        'features' => [
            'Página pública básica',
            'Calendario y resultados',
            'Standings automáticos',
        ],
    ],

    'plus' => [
        'label'       => 'Plus',
        'price_label' => '$199 MXN/mes',
        'tagline'     => 'Para clubes con varias divisiones.',
        'highlight_color' => '#3b82f6',
        'limits' => [
            'active_leagues'        => 5,
            'players_per_league'    => 60,
            'jornadas_per_league'   => 12,
        ],
        'features' => [
            'Todo lo de Free',
            'Hasta 5 ligas activas',
            'Anuncios en página pública',
            'Propuestas de marcador',
        ],
    ],

    'pro' => [
        'label'       => 'Pro',
        'price_label' => '$499 MXN/mes',
        'tagline'     => 'Para clubes grandes y organizadores profesionales.',
        'highlight_color' => '#edc35f',
        'limits' => [
            'active_leagues'        => null,  // unlimited
            'players_per_league'    => null,
            'jornadas_per_league'   => null,
        ],
        'features' => [
            'Todo lo de Plus',
            'Ligas ilimitadas',
            'Jugadores ilimitados',
            'Jornadas ilimitadas',
            'Soporte prioritario',
        ],
    ],

];
