<?php

declare(strict_types=1);

return [
    'email'    => env('DEMO_USER_EMAIL', 'demo@spendly.app'),
    'password' => env('DEMO_USER_PASSWORD', 'demo'),
    'enabled'  => env('DEMO_ENABLED', false),
];
