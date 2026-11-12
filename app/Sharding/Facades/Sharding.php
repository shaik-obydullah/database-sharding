<?php

namespace App\Sharding\Facades;

use App\Sharding\Contracts\ShardManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string shardFor(string|int $key)
 * @method static \Illuminate\Database\ConnectionInterface connectionFor(string|int $key)
 * @method static array shards()
 * @method static array connections()
 * @method static void eachShard(callable $callback)
 *
 * @see \App\Sharding\ShardManager
 */
class Sharding extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShardManager::class;
    }
}
