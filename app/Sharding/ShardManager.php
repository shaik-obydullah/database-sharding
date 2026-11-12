<?php

namespace App\Sharding;

use App\Sharding\Contracts\ShardManager as ShardManagerContract;
use App\Sharding\Contracts\ShardStrategy;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;

class ShardManager implements ShardManagerContract
{
    protected ShardStrategy $strategy;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $shards;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config, protected Container $container)
    {
        $this->shards = $config['shards'] ?? [];

        $strategy = $config['strategy'] ?? 'modulo';
        $class = $config['strategies'][$strategy] ?? $strategy;

        $this->strategy = $container->make($class);
    }

    public function shardFor(string|int $key): string
    {
        return $this->strategy->resolve($key, $this->shards());
    }

    public function connectionFor(string|int $key): ConnectionInterface
    {
        return $this->container->make('db')->connection($this->shardFor($key));
    }

    public function shards(): array
    {
        return array_keys($this->shards);
    }

    public function connections(): array
    {
        $connections = [];

        foreach ($this->shards() as $shard) {
            $connections[$shard] = $this->container->make('db')->connection($shard);
        }

        return $connections;
    }

    public function eachShard(callable $callback): void
    {
        foreach ($this->connections() as $shard => $connection) {
            $callback($connection, $shard);
        }
    }

    public function strategy(): ShardStrategy
    {
        return $this->strategy;
    }
}
