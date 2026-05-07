@extends('layouts.app')

@section('title', 'New Order')

@section('content')
<div class="page">
    <div class="page-title">New order</div>
    <div class="page-sub">Select products to add to the order</div>

    @if(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif

    <div class="card">
        <form method="POST" action="{{ url('/orders') }}" id="order-form">
            @csrf
            <div class="order-grid">

                {{-- LEFT: Menu --}}
                <div>
                    <div class="field-group field-group-last">
                        <label class="field-label" for="payment_method">Payment method</label>
                        <select name="payment_method" id="payment_method" class="field-select">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                        </select>
                    </div>

                    <div class="menu-label">☕ Hot drinks</div>
                    @foreach(collect($products)->filter(fn($p) => strtolower($p->temperature) === 'hot') as $product)
                    <div class="menu-item" id="item-{{ $product->product_id }}"
                         onclick="toggleItem({{ $product->product_id }}, '{{ addslashes($product->product_name) }}', {{ $product->base_price }}, 'HOT')">
                        <div class="menu-item-left">
                            <span>{{ $product->product_name }}</span>
                            <span class="temp-pill temp-hot">{{ $product->temperature }}</span>
                        </div>
                        <div class="menu-item-actions">
                            <div class="qty-ctrl" id="qty-ctrl-{{ $product->product_id }}" style="display:none;" onclick="event.stopPropagation()">
                                <button type="button" class="qty-btn" onclick="changeQty({{ $product->product_id }}, -1)">−</button>
                                <span class="qty-num" id="qty-{{ $product->product_id }}">1</span>
                                <button type="button" class="qty-btn" onclick="changeQty({{ $product->product_id }}, 1)">+</button>
                            </div>
                            <div class="menu-item-price">₱{{ number_format($product->base_price, 0) }}</div>
                        </div>
                    </div>
                    @endforeach

                    <div class="menu-label menu-label-mt-sm">🧊 Cold drinks</div>
                    @foreach(collect($products)->filter(fn($p) => strtolower($p->temperature) === 'cold') as $product)
                    <div class="menu-item" id="item-{{ $product->product_id }}"
                         onclick="toggleItem({{ $product->product_id }}, '{{ addslashes($product->product_name) }}', {{ $product->base_price }}, 'COLD')">
                        <div class="menu-item-left">
                            <span>{{ $product->product_name }}</span>
                            <span class="temp-pill temp-cold">{{ $product->temperature }}</span>
                        </div>
                        <div class="menu-item-actions">
                            <div class="qty-ctrl" id="qty-ctrl-{{ $product->product_id }}" style="display:none;" onclick="event.stopPropagation()">
                                <button type="button" class="qty-btn" onclick="changeQty({{ $product->product_id }}, -1)">−</button>
                                <span class="qty-num" id="qty-{{ $product->product_id }}">1</span>
                                <button type="button" class="qty-btn" onclick="changeQty({{ $product->product_id }}, 1)">+</button>
                            </div>
                            <div class="menu-item-price">₱{{ number_format($product->base_price, 0) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- RIGHT: Summary --}}
                <div>
                    <div class="summary-label">Order summary</div>
                    <div class="summary-box" id="summary-box">
                        <div class="summary-empty">No items selected yet.</div>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span id="summary-total">₱0</span>
                    </div>

                    <div id="hidden-inputs"></div>

                    <div class="action-row">
                        <button type="submit" class="btn-place">Place order ↗</button>
                        <button type="button" class="btn-clear" onclick="clearOrder()">Clear</button>
                        <a href="/orders" class="btn-clear btn-discard">Cancel</a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    const cart = {};

    function toggleItem(id, name, price, temp) {
        cart[id] ? removeItem(id) : (cart[id] = { name, price, qty: 1, temp, withCoffee: false },
            document.getElementById('item-' + id).classList.add('selected'),
            document.getElementById('qty-ctrl-' + id).style.display = 'flex');
        renderSummary();
    }

    function changeQty(id, delta) {
        if (!cart[id]) return;
        cart[id].qty += delta;
        cart[id].qty <= 0 ? removeItem(id) : (document.getElementById('qty-' + id).textContent = cart[id].qty);
        renderSummary();
    }

    function removeItem(id) {
        delete cart[id];
        document.getElementById('item-' + id).classList.remove('selected');
        document.getElementById('qty-ctrl-' + id).style.display = 'none';
        document.getElementById('qty-' + id).textContent = 1;
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
            box.innerHTML = '<div class="summary-empty">No items selected yet.</div>';
            totalEl.textContent = '₱0'; hiddenEl.innerHTML = ''; return;
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

        ids.forEach(id => {
            const cb = document.getElementById('coffee-cb-' + id);
            if (cb) cb.checked = cart[id].withCoffee;
        });
    }

    function clearOrder() { Object.keys(cart).forEach(id => removeItem(id)); }
</script>
@endsection