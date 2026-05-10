@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page">

    <div class="page-header">
        <div>
            <h1>Good {{ $greeting }}, <span>{{ session('user_name', 'Staff') }}</span></h1>
            <p>{{ now()->format('l, F j, Y') }} &mdash; Your shift summary</p>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">My Orders Today</div>
            <div class="stat-value">{{ $myOrdersToday }}</div>
            <span class="stat-badge badge-blue">placed today</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">My Revenue Today</div>
            <div class="stat-value revenue">₱{{ number_format($myRevenueToday, 2) }}</div>
            <span class="stat-badge badge-green">from paid orders</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">My Pending Orders</div>
            <div class="stat-value">{{ $myPendingOrders }}</div>
            <span class="stat-badge badge-orange">awaiting payment</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Low Stock Alerts</div>
            <div class="stat-value">{{ $lowStockAlerts->count() }}</div>
            <span class="stat-badge badge-{{ $lowStockAlerts->count() > 0 ? 'orange' : 'green' }}">
                {{ $lowStockAlerts->count() > 0 ? 'needs restocking' : 'all good' }}
            </span>
        </div>
    </div>

    {{-- Pending stock transactions notice --}}
    @if(isset($myPendingTransactions) && $myPendingTransactions > 0)
    <div class="flash flash-info" style="margin-bottom:16px;">
        ⏳ You have <strong>{{ $myPendingTransactions }}</strong> stock
        transaction{{ $myPendingTransactions > 1 ? 's' : '' }} awaiting admin approval.
        <a href="/inventory" style="font-weight:700; color:inherit; text-decoration:underline;">View →</a>
    </div>
    @endif

    {{-- MAIN GRID --}}
    <div class="main-grid">
        <div class="left-col">

            {{-- My Recent Orders --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">My recent orders</span>
                    <a href="/orders" class="card-link">View all →</a>
                </div>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Order</th><th>Product</th><th>Amount</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myRecentOrders as $order)
                        <tr>
                            <td class="order-num">#{{ str_pad($order->order_id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="order-product">{{ $order->product_name }}</td>
                            <td class="order-amount">₱{{ number_format($order->total_amount, 0) }}</td>
                            <td>
                                <span class="pill {{ strtolower($order->status) === 'paid' ? 'pill-green' : 'pill-orange' }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="td-empty">No orders yet today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="right-col">

            {{-- Low Stock Alerts — now uses admin-set min_stock --}}
            @if($lowStockAlerts->count() > 0)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">⚠️ Low stock alerts</span>
                    <a href="/inventory" class="card-link">View →</a>
                </div>
                @foreach($lowStockAlerts as $ing)
                @php
                    $unitLabel = $ing->unit === 'grams' ? 'g' : $ing->unit;
                @endphp
                <div class="stock-row">
                    <div class="stock-meta">
                        <span class="stock-name">
                            {{ $ing->ingredient_name }}
                            @if($ing->stock_level <= 0)
                                <span class="pill pill-red pill-inline">OUT OF STOCK</span>
                            @else
                                <span class="pill pill-orange pill-inline">LOW</span>
                            @endif
                        </span>
                        <span class="stock-qty stock-qty-warning">
                            {{ number_format($ing->stock_level) }} {{ $unitLabel }}
                            @if(isset($ing->min_stock) && $ing->min_stock > 0)
                                <span style="font-size:11px; color:var(--muted);">
                                    / min {{ number_format($ing->min_stock) }} {{ $unitLabel }}
                                </span>
                            @endif
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Stock status</span>
                    <a href="/inventory" class="card-link">View →</a>
                </div>
                <p class="td-muted-p">✅ All ingredients are sufficiently stocked.</p>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection