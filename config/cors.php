<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'https://localhost:3000', 
        env('FRONTEND_URL', 'http://localhost:3000'),
        '*' // Temporarily allow all origins for testing - remove in production
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];