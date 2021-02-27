<?php

return [
    'default_base_dir' => env('DEFAULT_BASE_DIR', "/assets"),
    'aws' => [
        'endpoint' => env('AWS_ENDPONIT'),
        'region' => env('AWS_REGION', 'eu-central-1'),
        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        'version' => env('AWS_VERSION', '2006-03-01'),
    ],
];
