<?php

declare(strict_types=1);

/**
 * Framework-free preview of the Laravel sharding example.
 *
 * This file mirrors the same hash strategy used by
 * App\Sharding\Strategies\ModuloStrategy, but uses PDO + SQLite directly so
 * you can see sharding work without installing Laravel or a database server.
 *
 * Run:
 *   php examples/standalone_demo.php
 *
 * Requirements: PHP 8.1+ with pdo_sqlite.
 */
$shards = [
    'shard_1' => __DIR__.'/../database/shards/demo_shard_1.sqlite',
    'shard_2' => __DIR__.'/../database/shards/demo_shard_2.sqlite',
    'shard_3' => __DIR__.'/../database/shards/demo_shard_3.sqlite',
];

function shardFor(string|int $key, array $shards): string
{
    $names = array_keys($shards);
    $hash = hexdec(substr(md5((string) $key), 0, 8));

    return $names[$hash % count($names)];
}

$connections = [];

foreach ($shards as $name => $path) {
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    if (is_file($path)) {
        unlink($path);
    }

    $pdo = new PDO('sqlite:'.$path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(
        'CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            product TEXT NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL
        )'
    );

    $connections[$name] = $pdo;
}

$products = [
    'Mechanical Keyboard',
    'USB-C Hub',
    '27 inch Monitor',
    'Noise-cancelling Headphones',
    'Standing Desk',
    'Ergonomic Mouse',
];

$statuses = ['pending', 'paid', 'shipped', 'cancelled'];

for ($i = 0; $i < 60; $i++) {
    $customerId = random_int(1, 12);
    $shard = shardFor($customerId, $shards);

    $statement = $connections[$shard]->prepare(
        'INSERT INTO orders (customer_id, product, amount, status) VALUES (?, ?, ?, ?)'
    );

    $statement->execute([
        $customerId,
        $products[array_rand($products)],
        random_int(500, 50000) / 100,
        $statuses[array_rand($statuses)],
    ]);
}

echo "Routing table (customer_id -> shard)\n";

foreach (range(1, 12) as $customerId) {
    printf("  customer %2d -> %s\n", $customerId, shardFor($customerId, $shards));
}

echo "\nOrders per shard\n";

$total = 0;

foreach ($connections as $name => $pdo) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $total += $count;
    printf("  %-8s %3d orders\n", $name, $count);
}

printf("  %-8s %3d orders\n", 'total', $total);

$customerId = 7;
$shard = shardFor($customerId, $shards);

$statement = $connections[$shard]->prepare(
    'SELECT id, customer_id, product, amount, status FROM orders WHERE customer_id = ? ORDER BY id'
);
$statement->execute([$customerId]);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

echo "\nOrders for customer {$customerId} (read only from {$shard})\n";

foreach ($rows as $row) {
    printf("  #%d %-28s %8.2f  %s\n", $row['id'], $row['product'], $row['amount'], $row['status']);
}
