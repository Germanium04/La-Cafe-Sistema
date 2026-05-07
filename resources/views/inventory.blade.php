@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="page">
    <div class="page-title">Inventory</div>
    <div class="page-sub">Track ingredient stock levels and log IN/OUT transactions</div>

    @if(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif

    {{-- Info banner: only shown to staff since admin can't log transactions --}}
    @if(session('user_role') === 'staff')
    <div class="flash flash-info">
        📦 Stock is <strong>automatically deducted</strong> when a pending order is
        <strong>marked as Paid</strong> — not when the order is first placed.
        Use the form below only for <strong>manual</strong> adjustments.
    </div>
    @endif

    <div class="order-grid">

        {{-- LEFT: Stock levels --}}
        <div>
            <div class="card">
                <div class="card-title card-title-lg">Current stock levels</div>

                @forelse($ingredients as $ingredient)
                @php
                    $max      = max($ingredient->stock_level * 2, 100);
                    $pct      = $max > 0 ? min(100, round(($ingredient->stock_level / $max) * 100)) : 0;
                    $isLow    = $pct <= 15 || $ingredient->stock_level <= 0;
                    $barColor = $ingredient->stock_level <= 0 ? '#e53935'
                              : ($isLow ? '#e07b20' : '#5c35d4');
                    $unitLabel = match($ingredient->unit) {
                        'grams' => 'g', 'ml' => 'ml', default => $ingredient->unit
                    };
                @endphp
                <div class="stock-row">
                    <div class="stock-meta">
                        <span class="stock-name">
                            {{ $ingredient->ingredient_name }}
                            @if($ingredient->stock_level <= 0)
                                <span class="pill pill-red pill-inline">OUT OF STOCK</span>
                            @elseif($isLow)
                                <span class="pill pill-orange pill-inline">LOW</span>
                            @endif
                        </span>
                        <span class="stock-qty {{ $ingredient->stock_level <= 0 ? 'stock-qty-danger' : ($isLow ? 'stock-qty-warning' : '') }}">
                            {{ number_format($ingredient->stock_level) }} {{ $unitLabel }}
                        </span>
                    </div>
                    <div class="bar-track">
                        <div class="inv-bar" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                    </div>
                </div>
                @empty
                <p class="td-muted-p">No ingredients found.</p>
                @endforelse
            </div>
        </div>

        {{-- RIGHT: Manual transaction form — staff only --}}
        <div>
            @if(session('user_role') === 'staff')
            <div class="card">
                <div class="card-title card-title-sm">Log stock transaction</div>
                <p class="form-helper">
                    Manual adjustments only — order-driven deductions are automatic on payment.
                </p>

                <form method="POST" action="/inventory/transaction">
                    @csrf

                    <div class="field-group">
                        <label class="field-label" for="ingredient_id">Ingredient</label>
                        <select name="ingredient_id" id="ingredient_id" class="field-select" required>
                            <option value="" disabled selected>Select ingredient…</option>
                            @foreach($ingredients as $ingredient)
                            <option value="{{ $ingredient->ingredient_id }}">
                                {{ $ingredient->ingredient_name }}
                                ({{ number_format($ingredient->stock_level) }}
                                {{ match($ingredient->unit) { 'grams' => 'g', 'ml' => 'ml', default => $ingredient->unit } }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Transaction type</label>
                        <div class="tx-type-row">
                            <label class="tx-type-label" id="lbl-in">
                                <input type="radio" name="transaction_type" value="IN" required
                                       onchange="highlightType()"
                                       class="tx-radio-in">
                                <span class="tx-label-in">▲ Stock IN</span>
                            </label>
                            <label class="tx-type-label" id="lbl-out">
                                <input type="radio" name="transaction_type" value="OUT"
                                       onchange="highlightType()"
                                       class="tx-radio-out">
                                <span class="tx-label-out">▼ Stock OUT</span>
                            </label>
                        </div>
                    </div>

                    <div class="field-group field-group-last">
                        <label class="field-label" for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="field-select input-full"
                               min="1" required placeholder="e.g. 500">
                    </div>

                    <button type="submit" class="btn-place btn-place-full">
                        Log transaction ↗
                    </button>
                </form>
            </div>

            @else
            {{-- Admin: read-only notice instead of the form --}}
            <div class="card">
                <div class="card-title card-title-sm">Stock transactions</div>
                <p class="form-helper">
                    Only staff members can log manual stock transactions.
                    Stock is automatically deducted when an order is marked as paid.
                </p>
            </div>
            @endif
        </div>

    </div>

    {{-- Transaction log — visible to all, but shows "By" column --}}
    <div class="card">
        <div class="card-title card-title-lg">Stock transactions log</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ingredient</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                @php
                    $unitLabel = match($tx->unit) {
                        'grams' => 'g', 'ml' => 'ml', default => $tx->unit
                    };
                @endphp
                <tr>
                    <td class="td-muted-sm">{{ $loop->iteration }}</td>
                    <td class="td-bold">{{ $tx->ingredient_name }}</td>
                    <td>
                        <span class="pill {{ strtolower($tx->transaction_type) === 'out' ? 'pill-red' : 'pill-green' }}">
                            {{ $tx->transaction_type }}
                        </span>
                    </td>
                    <td>{{ number_format($tx->quantity) }} {{ $unitLabel }}</td>
                    <td class="td-muted-sm">{{ $tx->staff_name ?? '—' }}</td>
                    <td class="td-muted-sm">
                        {{ \Carbon\Carbon::parse($tx->transaction_date)->format('M j, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="td-empty">No transactions recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    function highlightType() {
        const inRadio  = document.querySelector('input[value="IN"]');
        const outRadio = document.querySelector('input[value="OUT"]');
        document.getElementById('lbl-in').style.borderColor  = inRadio.checked  ? '#22863a' : 'var(--border)';
        document.getElementById('lbl-out').style.borderColor = outRadio.checked ? '#e53935' : 'var(--border)';
    }
</script>
@endsection