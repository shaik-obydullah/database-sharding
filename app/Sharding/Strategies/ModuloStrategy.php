<?php

namespace App\Sharding\Strategies;

use App\Sharding\Contracts\ShardStrategy;
use InvalidArgumentException;

class ModuloStrategy implements ShardStrategy
{
    public function resolve(string|int $key, array $shards): string
    {
        if ($shards === []) {
            throw new InvalidArgumentException('No shards are configured.');
        }

        $hash = hexdec(substr(md5((string) $key), 0, 8));

        return array_values($shards)[$hash % count($shards)];
    }
}
