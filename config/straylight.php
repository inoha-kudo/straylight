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
    ],

];
