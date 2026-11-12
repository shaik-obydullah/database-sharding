<?php

namespace App\Console\Commands;

use Database\Seeders\ShardOrderSeeder;
use Illuminate\Console\Command;

class ShardSeedCommand extends Command
{
    protected $signature = 'shard:seed';

    protected $description = 'Seed demo orders and let the sharding strategy distribute them';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => ShardOrderSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
