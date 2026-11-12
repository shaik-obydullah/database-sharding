<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class ShardOrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'Mechanical Keyboard',
            'USB-C Hub',
            '27 inch Monitor',
            'Noise-cancelling Headphones',
            'Standing Desk',
            'Ergonomic Mouse',
        ];

        $statuses = ['pending', 'paid', 'shipped', 'cancelled'];

        foreach (range(1, 60) as $i) {
            $customerId = random_int(1, 12);

            Order::onShard($customerId)->create([
                'customer_id' => $customerId,
                'product' => $products[array_rand($products)],
                'amount' => random_int(500, 50000) / 100,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }

        $this->command?->info('Seeded 60 orders across all shards.');
    }
}
