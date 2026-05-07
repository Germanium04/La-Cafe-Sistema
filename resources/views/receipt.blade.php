<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — Order #{{ str_pad($order->order_id, 3, '0', STR_PAD_LEFT) }}</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f0ebe3;
            min-height: 100vh;
            padding: 32px 16px 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            width: 100%;
            max-width: 420px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
            flex: 1;
        }
        .btn-back  { background: #fff; color: #2c1a0e; border: 1.5px solid #d6c9b8; }
        .btn-back:hover  { background: #f5f0eb; }
        .btn-print { background: #2c1a0e; color: #fff; }
        .btn-print:hover { background: #1a0f08; }

        .receipt {
            background: #fff;
            width: 100%;
            max-width: 420px;
            padding: 32px 28px 40px;
            box-shadow: 0 6px 32px rgba(0,0,0,0.12);
            position: relative;
        }
        .receipt::after {
            content: '';
            display: block;
            position: absolute;
            bottom: -12px;
            left: 0; right: 0;
            height: 12px;
            background:
                radial-gradient(circle at 12px -4px, transparent 12px, #fff 13px) top left / 24px 100% repeat-x,
                radial-gradient(circle at 12px 16px, #f0ebe3 12px, transparent 13px) top left / 24px 100% repeat-x;
        }

        .shop-header { text-align: center; padding-bottom: 18px; }
        .shop-name {
            font-size: 22px; font-weight: 900; letter-spacing: 0.1em;
            text-transform: uppercase; color: #2c1a0e;
            font-family: Georgia, serif;
        }
        .shop-sub { font-size: 11.5px; color: #9e8c7a; margin-top: 4px; letter-spacing: 0.04em; }

        .dashed { border: none; border-top: 1.5px dashed #d6c9b8; margin: 14px 0; }
        .solid  { border: none; border-top: 2px solid #2c1a0e; margin: 14px 0; }

        .meta-row {
            display: flex; justify-content: space-between;
            font-size: 11.5px; color: #6b5a47; margin-bottom: 6px;
        }
        .meta-row .val { font-weight: 700; color: #2c1a0e; text-align: right; max-width: 60%; }

        .badge {
            display: inline-block; padding: 2px 10px; border-radius: 20px;
            font-size: 10.5px; font-weight: 800; letter-spacing: 0.06em;
        }
        .badge-paid      { background: #dcfce7; color: #166534; }
        .badge-pending   { background: #fef9c3; color: #854d0e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .items-head {
            display: flex; font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.08em; color: #9e8c7a;
            margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #eee;
        }
        .c-qty   { width: 28px; text-align: center; flex-shrink: 0; }
        .c-name  { flex: 1; padding: 0 8px; }
        .c-price { width: 52px; text-align: right; flex-shrink: 0; }
        .c-sub   { width: 62px; text-align: right; flex-shrink: 0; }

        .item-row {
            display: flex; align-items: flex-start;
            font-size: 12px; color: #2c1a0e; margin-bottom: 8px; line-height: 1.45;
        }
        .item-row .c-qty   { color: #6b5a47; padding-top: 1px; }
        .item-row .c-price { color: #9e8c7a; font-size: 11px; padding-top: 2px; }
        .item-row .c-sub   { font-weight: 800; }

        .tag {
            display: inline-block; font-size: 9px; padding: 1px 5px; border-radius: 3px;
            font-weight: 800; letter-spacing: 0.03em; vertical-align: middle; margin-left: 3px;
        }
        .tag-hot    { background: #fef2ec; color: #c0440f; }
        .tag-cold   { background: #ecf5fe; color: #0f6ec0; }
        .tag-coffee { background: #f5f0eb; color: #6b3a1f; }

        .total-row {
            display: flex; justify-content: space-between;
            font-size: 12px; color: #6b5a47; margin-bottom: 5px;
        }
        .total-row.grand {
            font-size: 16px; font-weight: 900; color: #2c1a0e;
            margin-top: 8px; padding-top: 8px; border-top: 2px solid #2c1a0e;
        }

        .payment-block {
            background: #f9f5f0; border: 1px solid #e8dfd3;
            border-radius: 6px; padding: 12px 14px; margin-top: 14px;
        }
        .pay-row {
            display: flex; justify-content: space-between;
            font-size: 12px; color: #6b5a47; margin-bottom: 5px;
        }
        .pay-row:last-child { margin-bottom: 0; }
        .pay-row .val { font-weight: 800; color: #2c1a0e; }

        .receipt-footer { text-align: center; font-size: 11px; color: #9e8c7a; margin-top: 20px; line-height: 1.8; }
        .receipt-footer .tagline { font-size: 12.5px; font-weight: 800; color: #2c1a0e; display: block; margin-bottom: 2px; }

        @media print {
            body { background: #fff !important; padding: 0 !important; }
            .toolbar { display: none !important; }
            .receipt { box-shadow: none !important; max-width: 100% !important; padding: 20px 20px 32px !important; }
            .receipt::after { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <!-- <button onclick="window.parent.closeReceipt()" class="btn btn-back">← Back to Orders</button> -->
        <button onclick="window.print()" class="btn btn-print">🖨 Print / Save PDF</button>
    </div>

    <div class="receipt">

        <div class="shop-header">
            <div class="shop-name">Don Macchiato</div>
            <div class="shop-sub">☕ Coffee Shop &nbsp;·&nbsp; Brewed with love</div>
        </div>

        <hr class="solid">

        <div class="meta-row">
            <span>Receipt No.</span>
            <span class="val">#{{ str_pad($order->order_id, 3, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="meta-row">
            <span>Order Date</span>
            <span class="val">{{ \Carbon\Carbon::parse($order->order_date)->format('M j, Y g:i A') }}</span>
        </div>
        @if($order->payment_date)
        <div class="meta-row">
            <span>Paid On</span>
            <span class="val">{{ \Carbon\Carbon::parse($order->payment_date)->format('M j, Y g:i A') }}</span>
        </div>
        @endif
        <div class="meta-row">
            <span>Served by</span>
            <span class="val">{{ $order->staff_name }}</span>
        </div>
        <div class="meta-row">
            <span>Status</span>
            <span class="val">
                @php
                    $badgeClass = match(strtolower($order->status)) {
                        'paid'      => 'badge-paid',
                        'cancelled' => 'badge-cancelled',
                        default     => 'badge-pending',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ strtoupper($order->status) }}</span>
            </span>
        </div>

        <hr class="dashed">

        <div class="items-head">
            <span class="c-qty">Qty</span>
            <span class="c-name">Item</span>
            <span class="c-price">Price</span>
            <span class="c-sub">Total</span>
        </div>

        @foreach($items as $item)
        <div class="item-row">
            <span class="c-qty">{{ $item->quantity }}x</span>
            <span class="c-name">
                {{ $item->product_name }}
                <span class="tag {{ strtolower($item->temperature) === 'hot' ? 'tag-hot' : 'tag-cold' }}">{{ $item->temperature }}</span>
                @if($item->with_coffee)
                    <span class="tag tag-coffee">+Coffee</span>
                @endif
            </span>
            <span class="c-price">P{{ number_format($item->base_price, 0) }}</span>
            <span class="c-sub">P{{ number_format($item->subtotal, 0) }}</span>
        </div>
        @endforeach

        <hr class="dashed">

        <div class="total-row">
            <span>Subtotal</span>
            <span>P{{ number_format($order->total_amount, 0) }}</span>
        </div>
        <div class="total-row">
            <span>Discount</span>
            <span>P0</span>
        </div>
        <div class="total-row grand">
            <span>TOTAL</span>
            <span>P{{ number_format($order->total_amount, 0) }}</span>
        </div>

        <div class="payment-block">
            @if($order->payment_method)
                <div class="pay-row">
                    <span>Payment Method</span>
                    <span class="val">{{ $order->payment_method }}</span>
                </div>
                <div class="pay-row">
                    <span>Amount Paid</span>
                    <span class="val">P{{ number_format($order->amount_paid ?? $order->total_amount, 0) }}</span>
                </div>
                <div class="pay-row">
                    <span>Change</span>
                    <span class="val">P{{ number_format(max(0, ($order->amount_paid ?? $order->total_amount) - $order->total_amount), 0) }}</span>
                </div>
                <div class="pay-row">
                    <span>Payment Status</span>
                    <span class="val">{{ $order->payment_status ?? 'COMPLETED' }}</span>
                </div>
            @else
                <div class="pay-row">
                    <span>Payment</span>
                    <span class="val">Pending</span>
                </div>
            @endif
        </div>

        <hr class="dashed">

        <div class="receipt-footer">
            <span class="tagline">Thank you for your order! ☕</span>
            Come back soon &amp; enjoy every sip.<br>
            — Don Macchiato Coffee Shop —
        </div>

    </div>

</body>
</html>