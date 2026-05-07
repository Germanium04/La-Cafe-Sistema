@extends('layouts.app')

@section('title', 'Edit Order')

@section('content')
<div class="page">
    <div class="page-title">Edit Order #{{ str_pad($order->order_id, 3, '0', STR_PAD_LEFT) }}</div>
    <div class="page-sub">Modify items for this pending order — {{ $order->staff_name }}</div>

    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif

    <div class="card">
        <form method="POST" action="/orders/{{ $order->order_id }}" id="edit-form">
            @csrf
            @method('PUT')

            <div class="order-grid">

                {{-- LEFT: Current items + add new --}}
                <div>
                    <div class="menu-label">Current items</div>

                    <div id="current-items">
                        @foreach($orderDetails as $detail)
                        <div class="menu-item selected" id="item-{{ $detail->product_id }}">
                            <div class="menu-item-left">
                                <span>{{ $detail->product_name }}</span>
                                <span class="temp-pill {{ strtolower($detail->temperature) === 'hot' ? 'temp-hot' : 'temp-cold' }}">
                                    {{ $detail->temperature }}
                                </span>
                            </div>
                            <div class="menu-item-actions">
                                <div class="qty-ctrl" onclick="event.stopPropagation()">
                                    <button type="button" class="qty-btn" onclick="changeQty({{ $detail->product_id }}, -1)">−</button>
                                    <span class="qty-num" id="qty-{{ $detail->product_id }}">{{ $detail->quantity }}</span>
                                    <button type="button" class="qty-btn" onclick="changeQty({{ $detail->product_id }}, 1)">+</button>
                                </div>
                                <div class="menu-item-price">₱{{ number_format($detail->base_price, 0) }}</div>
                                <button type="button"
                                    onclick="removeItem({{ $detail->product_id }})"
                                    class="btn-remove">
                                    ✕
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="menu-label menu-label-mt">☕ Hot drinks</div>

                    @foreach(collect($products)->filter(fn($p) => strtolower($p->temperature) === 'hot') as $product)
                    @php $already = collect($orderDetails)->firstWhere('product_id', $product->product_id); @endphp
                    <div class="menu-item"
                         id="add-{{ $product->product_id }}"
                         style="{{ $already ? 'display:none;' : '' }}"
                         onclick="addItem({{ $product->product_id }}, '{{ addslashes($product->product_name) }}', {{ $product->base_price }}, '{{ $product->temperature }}')">
                        <div class="menu-item-left">
                            <span>{{ $product->product_name }}</span>
                            <span class="temp-pill temp-hot">{{ $product->temperature }}</span>
                        </div>
                        <div class="menu-item-price">₱{{ number_format($product->base_price, 0) }}</div>
                    </div>
                    @endforeach

                    <div class="menu-label menu-label-mt-sm">🧊 Cold drinks</div>

                    @foreach(collect($products)->filter(fn($p) => strtolower($p->temperature) === 'cold') as $product)
                    @php $already = collect($orderDetails)->firstWhere('product_id', $product->product_id); @endphp
                    <div class="menu-item"
                         id="add-{{ $product->product_id }}"
                         style="{{ $already ? 'display:none;' : '' }}"
                         onclick="addItem({{ $product->product_id }}, '{{ addslashes($product->product_name) }}', {{ $product->base_price }}, '{{ $product->temperature }}')">
                        <div class="menu-item-left">
                            <span>{{ $product->product_name }}</span>
                            <span class="temp-pill temp-cold">{{ $product->temperature }}</span>
                        </div>
                        <div class="menu-item-price">₱{{ number_format($product->base_price, 0) }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- RIGHT: Summary --}}
                <div>
                    <div class="summary-label">Order summary</div>
                    <div class="summary-box" id="summary-box"></div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span id="summary-total">₱0</span>
                    </div>

                    <div id="hidden-inputs"></div>

                    <div class="action-row">
                        <button type="submit" class="btn-place">Save changes ↗</button>
                        <a href="/orders" class="btn-clear btn-discard">Discard</a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    const cart = {};

    @foreach($orderDetails as $detail)
    cart[{{ $detail->product_id }}] = {
        name:       '{{ addslashes($detail->product_name) }}',
        price:      {{ $detail->base_price }},
        qty:        {{ $detail->quantity }},
        temp:       '{{ strtoupper($detail->temperature) }}',
        withCoffee: {{ $detail->with_coffee ? 'true' : 'false' }}
    };
    @endforeach

    function addItem(id, name, price, temperature) {
        if (cart[id]) return;
        cart[id] = { name, price, qty: 1, temp: temperature.toUpperCase(), withCoffee: false };

        // Hide from the "Add item" list
        document.getElementById('add-' + id).style.display = 'none';

        // Create a new row in "Current items"
        const div = document.createElement('div');
        div.className = 'menu-item selected';
        div.id = 'item-' + id;
        div.innerHTML = `
            <div class="menu-item-left">
                <span>${name}</span>
            </div>
            <div class="menu-item-actions">
                <div class="qty-ctrl">
                    <button type="button" class="qty-btn" onclick="changeQty(${id}, -1)">−</button>
                    <span class="qty-num" id="qty-${id}">1</span>
                    <button type="button" class="qty-btn" onclick="changeQty(${id}, 1)">+</button>
                </div>
                <div class="menu-item-price">₱${price.toLocaleString()}</div>
                <button type="button" onclick="removeItem(${id})" class="btn-remove">✕</button>
            </div>
        `;
        document.getElementById('current-items').appendChild(div);
        renderSummary();
    }

    function changeQty(id, delta) {
        if (!cart[id]) return;
        cart[id].qty += delta;
        if (cart[id].qty <= 0) { removeItem(id); return; }
        document.getElementById('qty-' + id).textContent = cart[id].qty;
        renderSummary();
    }

    function removeItem(id) {
        delete cart[id];

        // Remove from current items
        const el = document.getElementById('item-' + id);
        if (el) el.remove();

        // Always show back in the "Add item" list
        document.getElementById('add-' + id).style.display = '';

        renderSummary();
    }

    function toggleCoffee(id) {
        if (!cart[id]) return;
        cart[id].withCoffee = !cart[id].withCoffee;
    }

    function renderSummary() {
        const box      = document.getElementById('summary-box');
        const totalEl  = document.getElementById('summary-total');
        const hiddenEl = document.getElementById('hidden-inputs');
        const ids      = Object.keys(cart);

        if (!ids.length) {
            box.innerHTML       = '<div class="summary-empty">No items — order will be cancelled if saved empty.</div>';
            totalEl.textContent = '₱0';
            hiddenEl.innerHTML  = '';
            return;
        }

        let total = 0, html = '', hiddenHtml = '';
        ids.forEach(id => {
            const item = cart[id], sub = item.price * item.qty;
            total += sub;

            const coffeeCheckbox = item.temp === 'COLD'
                ? `<label class="coffee-label">
                       <input type="checkbox" id="coffee-cb-${id}" onchange="toggleCoffee(${id})"
                              ${item.withCoffee ? 'checked' : ''}
                              class="coffee-checkbox">
                       ☕ Add coffee
                   </label>`
                : '';

            html += `<div class="summary-row summary-row-col">
                        <div class="summary-row-inner">
                            <span>${item.name} x${item.qty}</span>
                            <span>₱${sub.toLocaleString()}</span>
                        </div>
                        ${coffeeCheckbox}
                     </div>`;

            hiddenHtml += `<input type="hidden" name="items[${id}][product_id]" value="${id}">`;
            hiddenHtml += `<input type="hidden" name="items[${id}][quantity]" value="${item.qty}">`;
            hiddenHtml += `<input type="hidden" name="items[${id}][subtotal]" value="${sub}">`;
            hiddenHtml += `<input type="hidden" name="items[${id}][with_coffee]" value="${item.withCoffee ? 1 : 0}">`;
        });

        box.innerHTML       = html;
        totalEl.textContent = '₱' + total.toLocaleString();
        hiddenEl.innerHTML  = hiddenHtml;

        // Re-sync checkbox states after innerHTML re-render
        ids.forEach(id => {
            const cb = document.getElementById('coffee-cb-' + id);
            if (cb) cb.checked = cart[id].withCoffee;
        });
    }

    renderSummary();
</script>
@endsection