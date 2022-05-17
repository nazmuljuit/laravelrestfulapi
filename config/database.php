<?php

return [
    'default' => 'sqlsrv',
    'connections' => [
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '205.188.5.54'),
            'port' => env('DB_PORT', '1435'),
            'database' => env('DB_DATABASE', 'AMG'),
            'username' => env('DB_USERNAME', 'AppTest'),
            'password' => env('DB_PASSWORD', 'apptest321'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv2' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB2_HOST', '205.188.5.54'),
            'port' => env('DB_PORT', '1435'),
            'database' => env('DB2_DATABASE', 'UNIS'),
            'username' => env('DB2_USERNAME', 'AppTest'),
            'password' => env('DB2_PASSWORD', 'apptest321'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
    ]
];
