<?php

return [

    // Shared secret from the dashboard. Without it the endpoint only says {"status":"ok"}.
    'token' => env('UPTIME_AGENT_TOKEN'),

    // URL path the dashboard requests.
    'path' => env('UPTIME_AGENT_PATH', '_uptime'),

    // Checks to run. Each is cheap; disable any you don't use.
    'checks' => [
        'database' => env('UPTIME_AGENT_CHECK_DB', true),
        'cache' => env('UPTIME_AGENT_CHECK_CACHE', false),
        'storage' => env('UPTIME_AGENT_CHECK_STORAGE', false),
    ],

];
