<?php

namespace App\Console\Commands;

use App\Sharding\Facades\Sharding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ShardStatusCommand extends Command
{
    protected $signature = 'shard:status';

    protected $description = 'Show how many orders live on each shard';

    public function handle(): int
    {
        $rows = [];

        foreach (Sharding::shards() as $shard) {
            try {
                $count = DB::connection($shard)->table('orders')->count();
            } catch (Throwable) {
                $count = 'not migrated';
            }

            $rows[] = [$shard, $count];
        }

        $this->table(['Shard', 'Orders'], $rows);

        return self::SUCCESS;
    }
}
