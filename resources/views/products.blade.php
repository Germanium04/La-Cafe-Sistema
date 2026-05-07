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
                <tr>
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
                <tr>
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

@endsection