<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShardMigrateCommand extends Command
{
    protected $signature = 'shard:migrate {--fresh : Delete SQLite shard files before migrating}';

    protected $description = 'Create the shard databases and run the shard migrations on every shard';

    public function handle(): int
    {
        $shards = (array) config('sharding.shards', []);

        if ($shards === []) {
            $this->error('No shards are configured in config/sharding.php.');

            return self::FAILURE;
        }

        foreach ($shards as $name => $connection) {
            if ($this->option('fresh')) {
                $this->deleteSqliteDatabase($connection);
            }

            $this->ensureSqliteDatabaseExists($connection);

            $this->info("Migrating shard [{$name}]");

            $this->call('migrate', [
                '--database' => $name,
                '--path' => 'database/migrations/shards',
                '--force' => true,
            ]);
        }

        $this->newLine();
        $this->info('All shards migrated.');

        return self::SUCCESS;
    }

    protected function deleteSqliteDatabase(array $connection): void
    {
        if (($connection['driver'] ?? null) !== 'sqlite') {
            return;
        }

        $path = $connection['database'] ?? null;

        if ($path && $path !== ':memory:' && is_file($path)) {
            unlink($path);
        }
    }

    protected function ensureSqliteDatabaseExists(array $connection): void
    {
        if (($connection['driver'] ?? null) !== 'sqlite') {
            return;
        }

        $path = $connection['database'] ?? null;

        if (! $path || $path === ':memory:' || is_file($path)) {
            return;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        touch($path);
    }
}
