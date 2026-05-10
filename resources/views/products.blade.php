@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="page">
    <div class="page-title">Products</div>
    <div class="page-sub">
        @if(session('user_role') === 'admin')
            Manage menu items and their ingredient requirements
        @else
            Browse the current menu
        @endif
    </div>

    @if(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif

    @if(session('user_role') === 'admin')
        <a href="/products/create" class="btn mb-btn">+ Add product ↗</a>
    @endif

    @php
        $hotProducts  = collect($products)->filter(fn($p) => strtoupper($p->temperature) === 'HOT');
        $coldProducts = collect($products)->filter(fn($p) => strtoupper($p->temperature) === 'COLD');
    @endphp

    {{-- ── HOT DRINKS ── --}}
    <div class="section-divider">
        <span class="section-divider-label">☕ Hot Drinks</span>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Base Price</th>
                    <th>Ingredients</th>
                    @if(session('user_role') === 'admin')
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($hotProducts as $product)
                <tr class="product-row" onclick="showIngredients({{ $product->product_id }}, '{{ addslashes($product->product_name) }}')" style="cursor:pointer;">
                    <td class="product-name">{{ $product->product_name }}</td>
                    <td class="price-cell">₱{{ number_format($product->base_price, 0) }}</td>
                    <td class="ingredients-list">{{ $product->ingredients ?? '—' }}</td>
                    @if(session('user_role') === 'admin')
                        <td class="action-cell">
                            <a href="/products/{{ $product->product_id }}/edit" class="btn-edit">Edit</a>
                            <form method="POST" action="/products/{{ $product->product_id }}/delete"
                                  onsubmit="return confirm('Archive {{ addslashes($product->product_name) }}? It will be hidden from the menu but kept in records.')">
                                @csrf
                                <button type="submit" class="btn-delete">Archive</button>
                            </form>
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ session('user_role') === 'admin' ? 4 : 3 }}" class="td-empty-lg">No hot drinks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── COLD DRINKS ── --}}
    <div class="section-divider section-divider-mt">
        <span class="section-divider-label">🧊 Cold Drinks</span>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Base Price</th>
                    <th>Ingredients</th>
                    @if(session('user_role') === 'admin')
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($coldProducts as $product)
                <tr class="product-row" onclick="showIngredients({{ $product->product_id }}, '{{ addslashes($product->product_name) }}')" style="cursor:pointer;">
                    <td class="product-name">{{ $product->product_name }}</td>
                    <td class="price-cell">₱{{ number_format($product->base_price, 0) }}</td>
                    <td class="ingredients-list">{{ $product->ingredients ?? '—' }}</td>
                    @if(session('user_role') === 'admin')
                        <td class="action-cell">
                            <a href="/products/{{ $product->product_id }}/edit" class="btn-edit">Edit</a>
                            <form method="POST" action="/products/{{ $product->product_id }}/delete"
                                  onsubmit="return confirm('Archive {{ addslashes($product->product_name) }}? It will be hidden from the menu but kept in records.')">
                                @csrf
                                <button type="submit" class="btn-delete">Archive</button>
                            </form>
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ session('user_role') === 'admin' ? 4 : 3 }}" class="td-empty-lg">No cold drinks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<style>
    .section-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .section-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }
    .section-divider-mt {
        margin-top: 28px;
    }
    .section-divider-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .action-cell {
        width: 120px;
        text-align: right;
        display: flex;
        align-items: center;
        gap: 6px;
        justify-content: flex-end;
    }
    .action-cell form {
        margin: 0;
    }
    .btn-edit {
        font-size: 12px;
        font-weight: 600;
        color: var(--accent);
        text-decoration: none;
        padding: 4px 10px;
        border: 1px solid var(--accent);
        border-radius: 6px;
        transition: background 0.15s, color 0.15s;
        white-space: nowrap;
    }
    .btn-edit:hover {
        background: var(--accent);
        color: #fff;
    }
    .btn-delete {
        font-size: 12px;
        font-weight: 600;
        color: var(--muted);
        background: none;
        cursor: pointer;
        padding: 4px 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .btn-delete:hover {
        border-color: #e53e3e;
        color: #e53e3e;
        background: #fff5f5;
    }
</style>

{{-- Ingredients Popup Modal --}}
<div id="ing-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--card); border-radius:var(--radius); padding:28px; max-width:400px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.2); position:relative;">
        <button onclick="closeModal()" style="position:absolute; top:12px; right:14px; background:none; border:none; font-size:20px; cursor:pointer; color:var(--muted);">&times;</button>
        <div id="modal-title" style="font-size:16px; font-weight:700; color:var(--text); margin-bottom:4px;"></div>
        <div style="font-size:12px; color:var(--muted); margin-bottom:16px;">Ingredients per cup</div>
        <div id="modal-body"></div>
    </div>
</div>

<script>
const ingredientsByProduct = @json($ingredientsByProduct);

function showIngredients(productId, productName) {
    const ingredients = ingredientsByProduct[productId] || [];
    document.getElementById('modal-title').textContent = productName;

    if (!ingredients.length) {
        document.getElementById('modal-body').innerHTML = '<p style="color:var(--muted); font-size:13px;">No ingredients recorded.</p>';
    } else {
        let html = '';
        ingredients.forEach(ing => {
            html += `<div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); font-size:13.5px;">
                        <span style="color:var(--text);">${ing.ingredient_name}</span>
                        <span style="color:var(--muted); font-weight:600;">${ing.quantity_used} ${ing.unit}</span>
                     </div>`;
        });
        document.getElementById('modal-body').innerHTML = html;
    }

    document.getElementById('ing-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('ing-modal').style.display = 'none';
}

document.getElementById('ing-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

@endsection