<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Sharding\Facades\Sharding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $customerId = $request->integer('customer_id') ?: null;

        $orders = $customerId
            ? Order::onShard($customerId)->latest('id')->get()
            : Order::allShards()->sortByDesc('id')->values();

        return view('orders.index', [
            'orders' => $orders,
            'customerId' => $customerId,
            'shard' => $customerId ? Sharding::shardFor($customerId) : null,
            'counts' => Order::countPerShard(),
            'shards' => Sharding::shards(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'min:1'],
            'product' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $order = Order::onShard($data['customer_id'])
            ->create($data + ['status' => 'pending']);

        return redirect()
            ->route('orders.index', ['customer_id' => $data['customer_id']])
            ->with('status', sprintf(
                'Order #%d saved on shard [%s] for customer %d.',
                $order->id,
                $order->getConnectionName(),
                $order->customer_id,
            ));
    }
}
