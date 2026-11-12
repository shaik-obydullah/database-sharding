<?php

namespace App\Sharding\Contracts;

use Illuminate\Database\ConnectionInterface;

interface ShardManager
{
    /**
     * Get the shard connection name for the given key.
     */
    public function shardFor(string|int $key): string;

    /**
     * Get the database connection for the given key.
     */
    public function connectionFor(string|int $key): ConnectionInterface;

    /**
     * @return array<int, string>
     */
    public function shards(): array;

    /**
     * @return array<string, ConnectionInterface>
     */
    public function connections(): array;

    /**
     * Run a callback against every shard connection.
     *
     * @param  callable(ConnectionInterface, string): void  $callback
     */
    public function eachShard(callable $callback): void;
}
