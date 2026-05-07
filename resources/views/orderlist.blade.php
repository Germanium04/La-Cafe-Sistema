@extends('layouts.app')

@section('title', 'Orders')

@section('content')
<div class="page">
    <div class="page-title">Orders</div>
    <div class="page-sub">
        @if(session('user_role') === 'admin')
            All orders across all staff — read only
        @else
            Your orders — mark as paid or cancel pending ones
        @endif
    </div>

    @if(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif

    {{-- Only staff can create new orders --}}
    @if(session('user_role') === 'staff')
        <a href="/orders/create" class="btn mb-btn">+ New order</a>
    @endif

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                @if(session('user_role') === 'admin') All orders @else My orders @endif
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Staff</th>
                    <th>Products</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Receipt</th>
                    @if(session('user_role') === 'staff')
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($allOrders as $order)
                <tr>
                    <td class="td-muted-sm">#{{ str_pad($order->order_id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="td-bold">{{ $order->staff_name }}</td>
                    <td class="td-muted-xs">{{ $order->products }}</td>
                    <td class="td-boldest">₱{{ number_format($order->total_amount, 0) }}</td>
                    <td class="td-muted-xs">{{ $order->payment_method ? ucfirst($order->payment_method) : '—' }}</td>
                    <td>
                        @php
                            $statusClass = match(strtolower($order->status)) {
                                'paid'      => 'pill-green',
                                'pending'   => 'pill-orange',
                                'cancelled' => 'pill-red',
                                default     => 'pill-muted',
                            };
                        @endphp
                        <span class="pill {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="td-muted-sm">
                        {{ \Carbon\Carbon::parse($order->order_date)->format('M j, Y') }}
                    </td>

                    {{-- Receipt: available for paid orders to everyone --}}
                    <td>
                        @if(strtolower($order->status) === 'paid')
                            <button type="button"
                                    class="btn btn-sm btn-receipt"
                                    onclick="openReceipt({{ $order->order_id }})">
                                🧾 Receipt
                            </button>
                        @else
                            <span class="muted-dash">—</span>
                        @endif
                    </td>

                    {{-- Actions column: staff only, and only for pending --}}
                    @if(session('user_role') === 'staff')
                    <td>
                        @if(strtolower($order->status) === 'pending')
                        <div class="td-actions">
                            <a href="/orders/{{ $order->order_id }}/edit" class="btn btn-sm">
                                ✎ Edit
                            </a>
                            <form method="POST" action="/orders/{{ $order->order_id }}/status">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="paid">
                                <button type="submit" class="btn btn-sm btn-green">
                                    ✓ Paid
                                </button>
                            </form>
                            <form method="POST" action="/orders/{{ $order->order_id }}/status"
                                  onsubmit="return confirm('Cancel this order?')">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    ✕
                                </button>
                            </form>
                        </div>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ session('user_role') === 'admin' ? 8 : 9 }}" class="td-empty-lg">
                        No orders yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-receipt {
        background: #f5f0eb;
        color: #2c1a0e;
        border: 1px solid #d6c9b8;
        font-weight: 600;
        white-space: nowrap;
    }
    .btn-receipt:hover {
        background: #2c1a0e;
        color: #fff;
        border-color: #2c1a0e;
    }
</style>

{{-- Receipt Modal --}}
<div id="receipt-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; overflow-y:auto; padding:32px 16px 60px;">
    <div style="position:relative; max-width:480px; margin:0 auto;">
        <button onclick="closeReceipt()"
                style="position:absolute; top:-12px; right:-12px; z-index:10;
                       background:#e07b20; color:#fff; border:none; border-radius:50%;
                       width:32px; height:32px; font-size:18px; cursor:pointer; line-height:1;">
            &times;
        </button>
        <iframe id="receipt-frame"
                src=""
                style="width:100%; height:85vh; border:none; border-radius:4px; background:#fff;">
        </iframe>
    </div>
</div>

<script>
    function openReceipt(orderId) {
        document.getElementById('receipt-frame').src = '/orders/' + orderId + '/receipt';
        document.getElementById('receipt-modal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    function closeReceipt() {
        document.getElementById('receipt-modal').style.display = 'none';
        document.getElementById('receipt-frame').src = '';
        document.body.style.overflow = '';
    }
    document.getElementById('receipt-modal').addEventListener('click', function(e) {
        if (e.target === this) closeReceipt();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeReceipt();
    });
</script>
@endsection