<?php

namespace App\Sharding\Concerns;

use App\Sharding\Facades\Sharding;
use Illuminate\Support\Collection;

trait Shardable
{
    /**
     * The column used to pick a shard. Override by declaring
     * `public static string $shardKey = 'column';` on the model.
     */
    public static function shardKeyColumn(): string
    {
        return property_exists(static::class, 'shardKey') ? static::$shardKey : 'id';
    }

    /**
     * Point this model instance at the shard that owns the given key.
     */
    public function useShard(string|int $key): static
    {
        return $this->setConnection(Sharding::shardFor($key));
    }

    /**
     * Start a query on the shard that owns the given key.
     */
    public static function onShard(string|int $key): static
    {
        return (new static)->useShard($key);
    }

    /**
     * The connection (shard) backing this model instance.
     */
    public function shardName(): ?string
    {
        return $this->getConnectionName();
    }

    /**
     * Read every order from every shard and merge the results.
     */
    public static function allShards(): Collection
    {
        return collect(Sharding::shards())
            ->flatMap(fn (string $shard) => (new static)->setConnection($shard)->newQuery()->get());
    }

    /**
     * Count rows per shard.
     *
     * @return array<string, int>
     */
    public static function countPerShard(): array
    {
        $counts = [];

        foreach (Sharding::shards() as $shard) {
            $counts[$shard] = (new static)->setConnection($shard)->newQuery()->count();
        }

        return $counts;
    }

    /**
     * Route writes automatically when the model was not assigned a shard.
     */
    public function save(array $options = [])
    {
        if (! $this->isOnShard()) {
            $key = $this->{static::shardKeyColumn()};

            if ($key !== null) {
                $this->useShard($key);
            }
        }

        return parent::save($options);
    }

    protected function isOnShard(): bool
    {
        return in_array($this->getConnectionName(), Sharding::shards(), true);
    }
}
