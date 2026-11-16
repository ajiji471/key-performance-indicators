<?php

return [
    'admin' => [
        'email' => env('SEED_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SEED_ADMIN_PASSWORD', 'password'),
    ],
    'inputer1' => [
        'name' => env('SEED_INPUTER1_NAME', 'Ajiji'),
        'email' => env('SEED_INPUTER1_EMAIL', 'user@example.com'),
        'password' => env('SEED_INPUTER1_PASSWORD', 'password'),
    ],
    'inputer2' => [
        'name' => env('SEED_INPUTER2_NAME', 'Gasti Nurina'),
        'email' => env('SEED_INPUTER2_EMAIL', 'gasti@example.com'),
        'password' => env('SEED_INPUTER2_PASSWORD', 'password'),
    ],
    'auditor' => [
        'email' => env('SEED_AUDITOR_EMAIL', 'auditor@example.com'),
        'password' => env('SEED_AUDITOR_PASSWORD', 'password'),
    ],
];
