<?php

use App\Sharding\Strategies\ModuloStrategy;
use App\Sharding\Strategies\RangeStrategy;

return [
    /*
    |--------------------------------------------------------------------------
    | Shard strategy
    |--------------------------------------------------------------------------
    |
    | The strategy decides which shard a given key (customer_id in this
    | example) belongs to. Two strategies ship with this example:
    | "modulo" (hash based) and "range" (numeric ranges).
    |
    */
    'strategy' => env('SHARD_STRATEGY', 'modulo'),

    'strategies' => [
        'modulo' => ModuloStrategy::class,
        'range' => RangeStrategy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shard connections
    |--------------------------------------------------------------------------
    |
    | Every entry is a normal Laravel database connection. The service
    | provider copies these into database.connections at runtime, so you do
    | not have to duplicate them in config/database.php.
    |
    | This example uses SQLite files so it runs without a server or Docker.
    | Swap the driver/database values for MySQL/PostgreSQL in production.
    |
    */
    'shards' => [
        'shard_1' => [
            'driver' => 'sqlite',
            'database' => database_path('shards/shard_1.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'shard_2' => [
            'driver' => 'sqlite',
            'database' => database_path('shards/shard_2.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'shard_3' => [
            'driver' => 'sqlite',
            'database' => database_path('shards/shard_3.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Range strategy boundaries
    |--------------------------------------------------------------------------
    |
    | Only used when strategy is "range". Each shard gets an inclusive
    | [min, max] key range. Keys outside every range fall back to shard_1.
    |
    */
    'ranges' => [
        'shard_1' => [0, 333],
        'shard_2' => [334, 666],
        'shard_3' => [667, PHP_INT_MAX],
    ],
];
