# Laravel Sharding Example

A practical, self-contained example of **database sharding in Laravel**. Orders
are distributed across three database shards by `customer_id`, so every order
for a given customer is stored on, and read from, the same shard.

The example uses **SQLite files** as shards. That means no MySQL, no Docker, and
no database server to install — but the exact same code works with MySQL or
PostgreSQL by changing the connection entries in `config/sharding.php`.

## Requirements

- PHP 8.2+ with the `pdo_sqlite` extension
- Composer 2

The repository is a complete Laravel 12 application: `composer install` is all
you need before running the commands below.

## How it works

```
Order::onShard($customerId)      -> picks shard via strategy
        |
        v
ShardManager::shardFor(key)      -> "shard_2"
        |
        v
database.connections.shard_2     -> database/shards/shard_2.sqlite
```

Key pieces:

| File | Responsibility |
| --- | --- |
| `composer.json`, `artisan` | Laravel 12 application skeleton |
| `bootstrap/app.php` | Application bootstrap and route registration |
| `config/sharding.php` | Lists the shards and selects a strategy |
| `app/Sharding/ShardManager.php` | Maps a key to a shard connection |
| `app/Sharding/Strategies/ModuloStrategy.php` | Deterministic hash (default) |
| `app/Sharding/Strategies/RangeStrategy.php` | Numeric key ranges |
| `app/Sharding/Concerns/Shardable.php` | `onShard()`, `allShards()`, auto-route on save |
| `app/Sharding/Facades/Sharding.php` | `Sharding::shardFor(...)`, `Sharding::shards()` |
| `app/Providers/ShardingServiceProvider.php` | Registers connections + manager |
| `app/Models/Order.php` | Sharded model keyed by `customer_id` |
| `app/Console/Commands/ShardMigrateCommand.php` | `php artisan shard:migrate` |
| `app/Console/Commands/ShardSeedCommand.php` | `php artisan shard:seed` |
| `app/Console/Commands/ShardStatusCommand.php` | `php artisan shard:status` |
| `database/migrations/shards/` | One migration, run once per shard |
| `resources/views/orders/index.blade.php` | Demo UI with shard counts |

## Quick start

```bash
# 1. Install dependencies and create your local .env / app key
composer install
cp .env.example .env
php artisan key:generate

# 2. Create the SQLite shard files and run the shard migration on each shard
php artisan shard:migrate

# 3. Seed demo orders (they are routed across all shards)
php artisan shard:seed

# 4. Inspect distribution
php artisan shard:status

# 5. Open the demo UI
php artisan serve
# visit http://127.0.0.1:8000/orders
```

Or run steps 1–3 in one command:

```bash
composer setup
```

## Using it in an existing application

The sharding code is also drop-in friendly. Copy `app/Sharding/`,
`app/Models/Order.php`, `config/sharding.php`, the commands in
`app/Console/Commands/`, and the shard migration, then register
`App\Providers\ShardingServiceProvider` in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ShardingServiceProvider::class,
];
```

On Laravel 10, add the provider to `config/app.php` instead:

```php
'providers' => [
    // ...
    App\Providers\ShardingServiceProvider::class,
],
```

## Reading and writing

```php
use App\Models\Order;
use App\Sharding\Facades\Sharding;

// Which shard owns customer 42?
Sharding::shardFor(42);              // "shard_2" (deterministic)

// Write to the correct shard.
$order = Order::onShard(42)->create([
    'customer_id' => 42,
    'product' => 'Mechanical Keyboard',
    'amount' => 129.99,
    'status' => 'pending',
]);

// Read the same customer's orders from that shard only.
$orders = Order::onShard(42)->latest('id')->get();

// Reads fan out across every shard (use sparingly).
$all = Order::allShards();
$counts = Order::countPerShard();    // ['shard_1' => 19, ...]

// Which shard did this model come from?
$order->shardName();                 // "shard_2"
```

Writes are also auto-routed when you do not call `onShard()`:

```php
$order = Order::create([
    'customer_id' => 42,
    'product' => 'USB-C Hub',
    'amount' => 39.50,
    'status' => 'pending',
]);

$order->getConnectionName();         // "shard_2"
```

`Shardable::save()` routes by `$shardKey` before Laravel builds the insert
query, which is why `Order::create()` works correctly.

## Switching strategy

Default is hash-based routing:

```php
// config/sharding.php
'strategy' => 'modulo',
```

Change to range-based routing:

```php
'strategy' => 'range',
```

Range boundaries live under `ranges`. Keys outside all ranges fall back to
`shard_1`.

To add your own strategy, implement `App\Sharding\Contracts\ShardStrategy` and
register it:

```php
'strategies' => [
    'modulo' => App\Sharding\Strategies\ModuloStrategy::class,
    'range' => App\Sharding\Strategies\RangeStrategy::class,
    'custom' => App\Sharding\Strategies\CustomStrategy::class,
],
```

## Migrating shards

`shard:migrate` creates missing SQLite files, then runs
`database/migrations/shards` once per shard using `--database=<shard>`:

```bash
php artisan shard:migrate           # migrate all shards
php artisan shard:migrate --fresh   # delete SQLite shard files first
```

The same migration is applied to every shard, so each shard has an identical
schema (`orders` in this example).

## Tests

```bash
php artisan test --filter=ShardingTest
```

`tests/Concerns/InteractsWithShards.php` points the shards at temporary SQLite
files, migrates them, and truncates them after each test so the development
databases are never touched.

## Preview without Laravel

If you only have PHP (with `pdo_sqlite`), `examples/standalone_demo.php`
demonstrates the same hash strategy and routing without Laravel:

```bash
php examples/standalone_demo.php
```

It prints the customer-to-shard routing table, order counts per shard, and the
orders for one customer read from a single shard.

## Production notes

- Replace the SQLite entries in `config/sharding.php` with MySQL/PostgreSQL
  connections. The manager and model code do not change.
- Cross-shard queries (`allShards()`, `countPerShard()`) hit every shard. Do not
  use them on hot paths.
- Cross-shard transactions are not supported by this example. Keep a single
  logical transaction inside one shard, or use an outbox/compensation pattern.
- Adding a shard changes `modulo` routing for most keys. Plan a re-sharding
  strategy (consistent hashing or a lookup/directory table) before scaling out.
- Auto-increment ids are only unique inside a shard. Use a UUID/ULID or a
  composite key if you need globally unique ids.
