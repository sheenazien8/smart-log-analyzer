<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log File Paths
    |--------------------------------------------------------------------------
    |
    | Specify the paths to your Laravel log files. The package will monitor
    | these locations for new log entries and analyze existing files.
    |
    */
    'log_paths' => [
        storage_path('logs/laravel.log'),
        storage_path('logs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Define which log channels to monitor. Leave empty to monitor all channels.
    |
    */
    'channels' => [
        // 'single', 'daily', 'slack', 'syslog', 'errorlog'
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-time Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable real-time log monitoring. When enabled, the package will watch
    | for new log entries and process them immediately.
    |
    */
    'real_time_monitoring' => env('SMART_LOG_REAL_TIME', true),

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configure how logs are processed and analyzed.
    |
    */
    'processing' => [
        'batch_size' => 1000,
        'memory_limit' => '512M',
        'timeout' => 300,
        'queue_connection' => env('SMART_LOG_QUEUE_CONNECTION', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pattern Recognition
    |--------------------------------------------------------------------------
    |
    | Configure pattern recognition and error grouping settings.
    |
    */
    'pattern_recognition' => [
        'similarity_threshold' => 0.8,
        'min_occurrences' => 3,
        'time_window' => 3600, // seconds
        'max_patterns' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anomaly Detection
    |--------------------------------------------------------------------------
    |
    | Configure anomaly detection thresholds and settings.
    |
    */
    'anomaly_detection' => [
        'enabled' => true,
        'spike_threshold' => 5.0, // multiplier for normal rate
        'minimum_baseline_hours' => 24,
        'check_interval' => 300, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Settings
    |--------------------------------------------------------------------------
    |
    | Configure email alerts and notification settings.
    |
    */
    'alerts' => [
        'enabled' => env('SMART_LOG_ALERTS_ENABLED', true),
        'email' => [
            'from' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'recipients' => [
                // 'dev@example.com',
            ],
            'throttle_minutes' => 60,
        ],
        'severity_levels' => [
            'critical' => ['emergency', 'alert', 'critical'],
            'high' => ['error'],
            'medium' => ['warning'],
            'low' => ['notice', 'info'],
        ],
        'thresholds' => [
            'critical' => 1,
            'high' => 5,
            'medium' => 20,
            'low' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure the web dashboard appearance and behavior.
    |
    */
    'dashboard' => [
        'enabled' => true,
        'route_prefix' => 'smart-log-analyzer',
        'middleware' => ['web'],
        'theme' => 'default',
        'items_per_page' => 25,
        'chart_data_points' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configure the REST API for external integrations.
    |
    */
    'api' => [
        'enabled' => true,
        'route_prefix' => 'api/smart-log-analyzer',
        'middleware' => ['api'],
        'rate_limit' => '60,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configure data retention and storage optimization.
    |
    */
    'storage' => [
        'retention_days' => 30,
        'cleanup_enabled' => true,
        'cleanup_schedule' => '0 2 * * *', // Daily at 2 AM
        'compress_old_data' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for improved performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // seconds
        'prefix' => 'smart_log_analyzer',
        'store' => env('SMART_LOG_CACHE_STORE', 'default'),
    ],
];