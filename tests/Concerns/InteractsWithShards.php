<?php

namespace Tests\Concerns;

use App\Sharding\Contracts\ShardManager;
use Illuminate\Support\Facades\DB;

trait InteractsWithShards
{
    /**
     * @var array<int, string>
     */
    protected array $temporaryShardFiles = [];

    protected function migrateShards(): void
    {
        $this->useTemporaryShards();

        $this->artisan('shard:migrate')->assertExitCode(0);
    }

    /**
     * Point every SQLite shard at a throwaway file so tests never touch
     * the development databases in database/shards.
     */
    protected function useTemporaryShards(): void
    {
        $shards = config('sharding.shards', []);

        foreach ($shards as $name => &$connection) {
            if (($connection['driver'] ?? null) !== 'sqlite') {
                continue;
            }

            $path = tempnam(sys_get_temp_dir(), "laravel_shard_{$name}_");

            if ($path === false) {
                continue;
            }

            $connection['database'] = $path;
            $this->temporaryShardFiles[] = $path;
        }

        unset($connection);

        config([
            'sharding.shards' => $shards,
            'database.connections' => array_merge(config('database.connections', []), $shards),
        ]);

        foreach (array_keys($shards) as $name) {
            DB::purge($name);
        }

        $this->app->forgetInstance(ShardManager::class);
    }

    protected function truncateShards(): void
    {
        foreach (array_keys(config('sharding.shards', [])) as $name) {
            DB::connection($name)->table('orders')->truncate();
        }
    }

    protected function removeTemporaryShards(): void
    {
        foreach ($this->temporaryShardFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->temporaryShardFiles = [];
    }
}
