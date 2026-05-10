@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page">

    <div class="page-header">
        <div>
            <h1>Good {{ $greeting }}, <span>{{ session('user_name', 'Admin') }}</span></h1>
            <p>{{ now()->format('l, F j, Y') }} &mdash; Shop overview</p>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value revenue">₱{{ number_format($totalRevenue, 2) }}</div>
            <span class="stat-badge badge-green">{{ $paidOrdersCount }} paid orders</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value">{{ $pendingOrdersCount }}</div>
            <span class="stat-badge badge-orange">awaiting payment</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Products on Menu</div>
            <div class="stat-value">{{ $productsCount }}</div>
            <span class="stat-badge badge-blue">{{ $coldProductsCount }} cold</span>
            &nbsp;
            <span class="stat-badge badge-orange">{{ $hotProductsCount }} hot</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Staff</div>
            <div class="stat-value">{{ $staffCount }}</div>
            <span class="stat-badge badge-green">all accounts</span>
        </div>
    </div>

    {{-- Pending stock approval alert --}}
    @if(isset($pendingStockCount) && $pendingStockCount > 0)
    <div class="flash flash-info" style="margin-bottom:16px;">
        ⏳ <strong>{{ $pendingStockCount }}</strong> stock transaction{{ $pendingStockCount > 1 ? 's' : '' }}
        awaiting your approval.
        <a href="/inventory" style="font-weight:700; color:inherit; text-decoration:underline;">Review now →</a>
    </div>
    @endif

    {{-- MAIN GRID --}}
    <div class="main-grid">
        <div class="left-col">

            {{-- Recent Orders --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Recent orders</span>
                    <a href="/orders" class="card-link">View all →</a>
                </div>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Order</th><th>Product</th><th>Staff</th><th>Amount</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                        <tr>
                            <td class="order-num">#{{ str_pad($order->order_id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="order-product">{{ $order->product_name }}</td>
                            <td class="td-staff-sm">{{ $order->staff_name }}</td>
                            <td class="order-amount">₱{{ number_format($order->total_amount, 0) }}</td>
                            <td>
                                <span class="pill {{ strtolower($order->status) === 'paid' ? 'pill-green' : 'pill-orange' }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="td-empty">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Staff Activity --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Staff activity</span>
                </div>
                @forelse ($staffActivity as $member)
                @php
                    $nameParts = explode(' ', $member->name);
                    $initials  = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                @endphp
                <div class="staff-row">
                    <div class="staff-avatar">{{ $initials }}</div>
                    <div class="staff-info">
                        <div class="staff-name">{{ $member->name }}</div>
                        <div class="staff-orders">
                            {{ $member->order_count }} order{{ $member->order_count !== 1 ? 's' : '' }}
                            @if(($member->pending_orders ?? 0) > 0)
                                &mdash; {{ $member->pending_orders }} pending
                            @endif
                        </div>
                    </div>
                    <div class="staff-revenue">₱{{ number_format($member->total_revenue ?? 0, 0) }}</div>
                </div>
                @empty
                <p class="td-muted-p">No staff activity.</p>
                @endforelse
            </div>

        </div>

        <div class="right-col">

            {{-- Top Products --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Top products</span>
                    <a href="/reports" class="card-link">Report →</a>
                </div>
                @forelse ($topProducts as $i => $product)
                <div class="top-product-row">
                    <div class="rank">{{ $i + 1 }}</div>
                    <div class="top-product-info">
                        <div class="top-product-name">{{ $product->product_name }}</div>
                        <div class="top-product-meta">
                            {{ $product->total_sold ?? 0 }} sold
                            <span class="temp-pill {{ strtolower($product->temperature) === 'cold' ? 'temp-cold' : 'temp-hot' }}">
                                {{ $product->temperature }}
                            </span>
                        </div>
                    </div>
                    <div class="top-product-revenue">₱{{ number_format($product->total_revenue ?? 0, 0) }}</div>
                </div>
                @empty
                <p class="td-muted-p">No sales data yet.</p>
                @endforelse
            </div>

            {{-- Stock Levels — now uses min_stock for real LOW detection --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Stock levels</span>
                    <a href="/inventory" class="card-link">Manage →</a>
                </div>
                @forelse ($ingredients as $ingredient)
                @php
                    $minStock  = $ingredient->min_stock ?? 0;
                    $maxStock  = $ingredient->max_stock ?? max($ingredient->stock_level * 2, 100);
                    $pct       = $maxStock > 0
                                    ? min(100, round(($ingredient->stock_level / $maxStock) * 100))
                                    : 0;
                    $isOut     = $ingredient->stock_level <= 0;
                    $isLow     = !$isOut && $minStock > 0 && $ingredient->stock_level <= $minStock;
                    $barClass  = $isOut  ? 'bar-low'
                               : ($isLow ? 'bar-medium' : 'bar-high');
                    $unitLabel = $ingredient->unit === 'grams' ? 'g' : $ingredient->unit;
                @endphp
                <div class="stock-row">
                    <div class="stock-meta">
                        <span class="stock-name">
                            {{ $ingredient->ingredient_name }}
                            @if($isOut)
                                <span class="pill pill-red pill-inline">OUT</span>
                            @elseif($isLow)
                                <span class="pill pill-orange pill-inline">LOW</span>
                            @endif
                        </span>
                        <span class="stock-qty">{{ number_format($ingredient->stock_level) }} {{ $unitLabel }}</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill {{ $barClass }}" style="width:{{ $pct }}%;"></div>
                    </div>
                </div>
                @empty
                <p class="td-muted-p">No inventory data.</p>
                @endforelse
            </div>

        </div>
    </div>

</div>
@endsection