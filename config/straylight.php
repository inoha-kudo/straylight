<?php

return [

    'connection' => [
        'driver' => 'straylight',
        'disk' => env('STRAYLIGHT_FILESYSTEM_DISK', 'local'),
        'path' => env('STRAYLIGHT_DATABASE_PATH', 'database.sqlite'),
        'prefix' => '',
        'foreign_key_constraints' => env('STRAYLIGHT_FOREIGN_KEYS', true),
        'busy_timeout' => null,
        'synchronous' => 'OFF',
        'transaction_mode' => 'DEFERRED',
        'pragmas' => [
            'locking_mode' => 'EXCLUSIVE',
        ],
        'lock' => [
            'store' => env('STRAYLIGHT_LOCK_STORE'),
            'seconds' => (int) env('STRAYLIGHT_LOCK_SECONDS', 0),
            'wait_seconds' => (int) env('STRAYLIGHT_LOCK_WAIT_SECONDS', 10),
        ],
    ],

];
