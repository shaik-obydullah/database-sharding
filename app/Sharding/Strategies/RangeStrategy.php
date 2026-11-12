<?php

namespace App\Sharding\Strategies;

use App\Sharding\Contracts\ShardStrategy;
use InvalidArgumentException;

class RangeStrategy implements ShardStrategy
{
    public function resolve(string|int $key, array $shards): string
    {
        if ($shards === []) {
            throw new InvalidArgumentException('No shards are configured.');
        }

        $value = (int) $key;

        foreach ((array) config('sharding.ranges', []) as $shard => [$min, $max]) {
            if ($value >= $min && $value <= $max) {
                return $shard;
            }
        }

        return $shards[0];
    }
}
