<?php

namespace App\Sharding\Contracts;

interface ShardStrategy
{
    /**
     * Resolve a shard key to a shard connection name.
     *
     * @param  array<int, string>  $shards
     */
    public function resolve(string|int $key, array $shards): string;
}
