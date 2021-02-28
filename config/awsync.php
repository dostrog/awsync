<?php

return [
    'default_base_dir' => env('DEFAULT_BASE_DIR', "/assets"),
    'default_journal_dir' => env('DEFAULT_JOURNAL_DIR', ""),

    // In the case when files on the local system and on AWS S3 have the same names, but different sizes,
    // one of the following strategies is applied:
    //
    // 'LocalPriority' - local file takes precedence / the local file will overwrite the remote one
    // 'AmazonPriority' - bucket's file takes precedence / the local file will be overwrote by the remote one
    // 'BiggerPriority' - file with bigger size takes precedence / bigger file will be on both filesystems
    // 'SmallerPriority' - file with smaller size takes precedence / smaller file will be on both filesystems
    //
    'resolve_strategy' => env('RESOLVE_STRATEGY', "LocalPriority"),

    'aws' => [
        'endpoint' => env('AWS_ENDPONIT'),
        'region' => env('AWS_REGION', 'eu-central-1'),
        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        'version' => env('AWS_VERSION', '2006-03-01'),
    ],
];
