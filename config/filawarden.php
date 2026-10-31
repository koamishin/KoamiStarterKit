<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FilaWarden Operations Intelligence Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('FILAWARDEN_ENABLED', true),

    'navigation' => [
        'group' => 'Operations Intelligence',
        'sort' => 1,
        'icon' => 'heroicon-o-shield-check',
    ],

    /*
    |--------------------------------------------------------------------------
    | Page & Action Authorization
    |--------------------------------------------------------------------------
    | Configure who can access FilaWarden diagnostic pages in production.
    */
    'authorization' => [
        'enforce_in_production' => env('FILAWARDEN_ENFORCE_AUTH', true),
        'permission' => env('FILAWARDEN_PERMISSION', 'access_filawarden'),
        'callback' => null, // fn ($user) => $user->is_super_admin
    ],

    'thresholds' => [
        'cpu' => [
            'warning' => 70, // %
            'critical' => 90, // %
        ],
        'memory' => [
            'warning' => 75, // %
            'critical' => 90, // %
        ],
        'disk' => [
            'warning' => 80, // %
            'critical' => 95, // %
        ],
        'load' => [
            'warning' => 2.0,
            'critical' => 4.0,
        ],
    ],

    'cache' => [
        'telemetry_ttl' => 10, // seconds
        'audit_ttl' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Published vulnerability advisories
    |--------------------------------------------------------------------------
    | Installed PHP and Composer package versions are compared with the
    | php/php-src GitHub security advisories and the Packagist advisory feed.
    | Matches at or above minimum_severity are reported. Results are cached.
    */
    'advisories' => [
        'enabled' => env('FILAWARDEN_ADVISORIES_ENABLED', true),
        'cache_ttl' => (int) env('FILAWARDEN_ADVISORIES_CACHE_TTL', 21600),
        'minimum_severity' => env('FILAWARDEN_ADVISORIES_MIN_SEVERITY', 'high'),
        'timeout' => (int) env('FILAWARDEN_ADVISORIES_TIMEOUT', 8),
        'github_php_advisories_url' => 'https://api.github.com/repos/php/php-src/security-advisories',
        'packagist_advisories_url' => 'https://packagist.org/api/security-advisories/',
    ],

    'max_scanned_files' => env('FILAWARDEN_MAX_SCANNED_FILES', 500),

    'log_path' => env('FILAWARDEN_LOG_PATH', null),

    'pro_upgrade_url' => 'https://spiggle.dev/filawarden-pro',
];
