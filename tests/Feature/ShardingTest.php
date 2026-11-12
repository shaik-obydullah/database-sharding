<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Sharding\Facades\Sharding;
use Tests\Concerns\InteractsWithShards;
use Tests\TestCase;

class ShardingTest extends TestCase
{
    use InteractsWithShards;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateShards();
    }

    protected function tearDown(): void
    {
        $this->truncateShards();
        $this->removeTemporaryShards();

        parent::tearDown();
    }

    public function test_same_customer_is_always_routed_to_the_same_shard(): void
    {
        $first = Order::onShard(42)->create($this->orderAttributes(42));
        $second = Order::onShard(42)->create($this->orderAttributes(42));

        $this->assertSame(Sharding::shardFor(42), $first->getConnectionName());
        $this->assertSame($first->getConnectionName(), $second->getConnectionName());
        $this->assertSame(2, Order::onShard(42)->count());
    }

    public function test_saving_without_choosing_a_shard_routes_by_shard_key(): void
    {
        $order = Order::create($this->orderAttributes(7));

        $this->assertSame(Sharding::shardFor(7), $order->getConnectionName());
        $this->assertSame(1, Order::onShard(7)->count());
    }

    public function test_orders_can_be_read_from_every_shard(): void
    {
        Order::onShard(1)->create($this->orderAttributes(1));
        Order::onShard(2)->create($this->orderAttributes(2));
        Order::onShard(3)->create($this->orderAttributes(3));

        $this->assertCount(3, Order::allShards());
        $this->assertSame(3, array_sum(Order::countPerShard()));
    }

    public function test_strategy_only_returns_configured_shards(): void
    {
        foreach ([1, 7, 42, 999, 123456] as $customerId) {
            $this->assertContains(Sharding::shardFor($customerId), Sharding::shards());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderAttributes(int $customerId): array
    {
        return [
            'customer_id' => $customerId,
            'product' => 'Test Product',
            'amount' => 19.99,
            'status' => 'pending',
        ];
    }
}
