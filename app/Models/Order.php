<?php

namespace App\Models;

use App\Sharding\Concerns\Shardable;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use Shardable;

    /**
     * Orders are distributed by customer id, so every read/write for the
     * same customer hits the same shard.
     */
    public static string $shardKey = 'customer_id';

    protected $fillable = [
        'customer_id',
        'product',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
