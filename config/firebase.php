<?php

return [
    'project_id'  => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),

    'web' => [
        'apiKey'      => env('FIREBASE_API_KEY'),
        'authDomain'  => env('FIREBASE_AUTH_DOMAIN'),
        'projectId'   => env('FIREBASE_PROJECT_ID'),
        'appId'       => env('FIREBASE_APP_ID'),
    ],
];
