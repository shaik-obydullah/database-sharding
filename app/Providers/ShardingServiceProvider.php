<?php

namespace App\Providers;

use App\Sharding\Contracts\ShardManager as ShardManagerContract;
use App\Sharding\ShardManager;
use Illuminate\Support\ServiceProvider;

class ShardingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('sharding.php'), 'sharding');

        foreach ((array) config('sharding.shards', []) as $name => $connection) {
            config(["database.connections.{$name}" => $connection]);
        }

        $this->app->singleton(ShardManagerContract::class, function ($app) {
            return new ShardManager($app['config']->get('sharding', []), $app);
        });

        $this->app->alias(ShardManagerContract::class, ShardManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                config_path('sharding.php') => config_path('sharding.php'),
            ], 'sharding-config');
        }
    }
}
