<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Sharding Example</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #0f172a;
            --panel: #1e293b;
            --panel-2: #273449;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --border: #334155;
            --ok: #22c55e;
            --warn: #f59e0b;
            --bad: #ef4444;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 2rem 1rem 4rem;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            line-height: 1.5;
        }

        main { max-width: 1000px; margin: 0 auto; }

        h1 { margin: 0 0 .25rem; font-size: 1.75rem; }
        h2 { margin: 0 0 1rem; font-size: 1.1rem; }

        .muted { color: var(--muted); }
        code {
            background: #0b1220;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: .1rem .35rem;
            font-size: .85em;
        }

        .alert {
            margin: 1rem 0;
            padding: .75rem 1rem;
            border-radius: 8px;
            background: rgba(34, 197, 94, .12);
            border: 1px solid rgba(34, 197, 94, .4);
        }

        .alert.error {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .4);
        }

        .alert ul { margin: 0; padding-left: 1.2rem; }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .75rem;
            margin: 1.5rem 0;
        }

        .card {
            display: flex;
            flex-direction: column;
            gap: .15rem;
            padding: 1rem;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .card.active {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent) inset;
        }

        .card .label { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
        .card strong { font-size: 1.6rem; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: .75rem;
            font-size: .85rem;
            color: var(--muted);
        }

        input {
            display: block;
            width: 100%;
            margin-top: .3rem;
            padding: .6rem .7rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--panel-2);
            color: var(--text);
            font: inherit;
        }

        button {
            padding: .65rem 1.1rem;
            border: 0;
            border-radius: 8px;
            background: var(--accent);
            color: #06263a;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover { filter: brightness(1.08); }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .65rem .5rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        tbody tr:hover { background: rgba(148, 163, 184, .06); }

        .pill {
            display: inline-block;
            padding: .1rem .6rem;
            border-radius: 999px;
            font-size: .75rem;
            background: var(--panel-2);
            border: 1px solid var(--border);
        }

        .pill.paid { color: var(--ok); border-color: rgba(34, 197, 94, .5); }
        .pill.pending { color: var(--warn); border-color: rgba(245, 158, 11, .5); }
        .pill.shipped { color: var(--accent); border-color: rgba(56, 189, 248, .5); }
        .pill.cancelled { color: var(--bad); border-color: rgba(239, 68, 68, .5); }
    </style>
</head>
<body>
<main>
    <h1>Laravel Sharding Example</h1>
    <p class="muted">
        Orders are routed to a SQLite shard by <code>customer_id</code> using a hash-based strategy.
        The same customer always lands on the same shard.
    </p>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="cards">
        @foreach ($counts as $name => $count)
            <div class="card {{ $shard === $name ? 'active' : '' }}">
                <span class="label">{{ $name }}</span>
                <strong>{{ $count }}</strong>
                <span class="muted">orders</span>
            </div>
        @endforeach
    </section>

    <div class="grid">
        <section class="panel">
            <h2>Create an order</h2>
            <form method="POST" action="{{ route('orders.store') }}">
                @csrf
                <label>Customer ID
                    <input type="number" name="customer_id" min="1" value="{{ old('customer_id', $customerId ?? 1) }}" required>
                </label>
                <label>Product
                    <input type="text" name="product" value="{{ old('product') }}" placeholder="Mechanical Keyboard" required>
                </label>
                <label>Amount
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" placeholder="129.99" required>
                </label>
                <button type="submit">Save order</button>
            </form>
        </section>

        <section class="panel">
            <h2>Inspect a customer</h2>
            <form method="GET" action="{{ route('orders.index') }}">
                <label>Customer ID
                    <input type="number" name="customer_id" min="1" value="{{ $customerId }}" placeholder="Any customer id">
                </label>
                <button type="submit">Show shard</button>
            </form>
            <p class="muted">
                @if ($shard)
                    Customer {{ $customerId }} resolves to <strong>{{ $shard }}</strong>.
                @else
                    Showing orders from every shard.
                @endif
            </p>
        </section>
    </div>

    <section class="panel">
        <h2>Orders</h2>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Shard</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->customer_id }}</td>
                    <td>{{ $order->product }}</td>
                    <td>{{ number_format((float) $order->amount, 2) }}</td>
                    <td><span class="pill {{ $order->status }}">{{ $order->status }}</span></td>
                    <td><code>{{ $order->shardName() }}</code></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">
                        No orders yet. Create one above or run <code>php artisan shard:seed</code>.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
