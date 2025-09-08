<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
<<<<<<< HEAD
    'allowed_origins' => [
        'http://localhost:3000',
        'https://localhost:3000', 
        env('FRONTEND_URL', 'https://localhost:3000'),
        '*'  // Temporarily allow all origins for testing - remove in production
    ],
=======
    'allowed_origins' => ['http://localhost:3000'],
>>>>>>> parent of 4c6bfa1 (Merge branch 'master' of https://github.com/ceylonrover/CeylonRover_ServerSide)
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];