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

    {{-- Staff info banner --}}
    @if(session('user_role') === 'staff')
    <div class="flash flash-info">
        📦 Stock is <strong>automatically deducted</strong> when a pending order is
        <strong>marked as Paid</strong>. Manual transactions below require
        <strong>admin approval</strong> before stock is updated.
    </div>
    @endif



    {{-- ═══════════════════════════════════════════════
         MAIN GRID: stock levels (left) + form (right)
    ════════════════════════════════════════════════ --}}
    <div class="order-grid">

        {{-- LEFT: Stock levels --}}
        <div>
            <div class="card">
                <div class="card-title card-title-lg">Current stock levels</div>

                @forelse($ingredients as $ingredient)
                @php
                    // Use admin-set min/max for bar calculation
                    $minStock  = $ingredient->min_stock ?? 0;
                    $maxStock  = $ingredient->max_stock ?? max($ingredient->stock_level * 2, 100);
                    $pct       = $maxStock > 0
                                    ? min(100, round(($ingredient->stock_level / $maxStock) * 100))
                                    : 0;
                    $isOut     = $ingredient->stock_level <= 0;
                    $isLow     = !$isOut && $ingredient->stock_level <= $minStock;
                    $barColor  = $isOut  ? '#e53935'
                               : ($isLow ? '#e07b20' : '#5c35d4');
                    $unitLabel = $ingredient->unit === 'grams' ? 'g' : $ingredient->unit;
                @endphp
                <div class="stock-row">
                    <div class="stock-meta">
                        <span class="stock-name">
                            {{ $ingredient->ingredient_name }}
                            @if($isOut)
                                <span class="pill pill-red pill-inline">OUT OF STOCK</span>
                            @elseif($isLow)
                                <span class="pill pill-orange pill-inline">LOW</span>
                            @endif
                        </span>
                        <span class="stock-qty {{ $isOut ? 'stock-qty-danger' : ($isLow ? 'stock-qty-warning' : '') }}">
                            {{ number_format($ingredient->stock_level) }} {{ $unitLabel }}
                            @if($minStock > 0)
                                <span class="stock-threshold">/ min {{ number_format($minStock) }} {{ $unitLabel }}</span>
                            @endif
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

        {{-- RIGHT: Staff transaction form --}}
        <div>
            @if(session('user_role') === 'staff')
            <div class="card">
                <div class="card-title card-title-sm">Log stock transaction</div>
                <p class="form-helper">
                    Manual adjustments only. Your transaction will be reviewed by an admin
                    before stock is updated.
                </p>

                <form method="POST" action="/inventory/transaction"
                      onsubmit="return validateTransactionForm(event)">
                    @csrf

                    {{-- Ingredient --}}
                    <div class="field-group">
                        <label class="field-label" for="ingredient_id">Ingredient</label>
                        <select name="ingredient_id" id="ingredient_id" class="field-select"
                                required onchange="updateUnitDropdown(this.value)">
                            <option value="" disabled selected>Select ingredient…</option>
                            @foreach($ingredients as $ingredient)
                            @php $ul = $ingredient->unit === 'grams' ? 'g' : $ingredient->unit; @endphp
                            <option value="{{ $ingredient->ingredient_id }}">
                                {{ $ingredient->ingredient_name }}
                                ({{ number_format($ingredient->stock_level) }} {{ $ul }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Transaction type --}}
                    <div class="field-group">
                        <label class="field-label">Transaction type</label>
                        <div class="tx-type-row">
                            <label class="tx-type-label" id="lbl-in">
                                <input type="radio" name="transaction_type" value="IN" required
                                       onchange="highlightType(); updateReasonDropdown()"
                                       class="tx-radio-in">
                                <span class="tx-label-in">▲ Stock IN</span>
                            </label>
                            <label class="tx-type-label" id="lbl-out">
                                <input type="radio" name="transaction_type" value="OUT"
                                       onchange="highlightType(); updateReasonDropdown()"
                                       class="tx-radio-out">
                                <span class="tx-label-out">▼ Stock OUT</span>
                            </label>
                        </div>
                    </div>

                    {{-- Quantity + unit --}}
                    <div class="field-group">
                        <label class="field-label">Quantity</label>
                        <div class="qty-unit-row">
                            <input type="number" name="entered_quantity" id="entered_quantity"
                                   class="field-select qty-input"
                                   min="0.01" step="any" required placeholder="e.g. 2"
                                   autocomplete="off"
                                   style="flex:1; min-width:0; width:0;">
                            <select name="entered_unit" id="entered_unit"
                                    class="field-select unit-select"
                                    style="width:110px !important; flex-shrink:0; flex-grow:0;">
                                <option value="" disabled selected>unit</option>
                            </select>
                        </div>
                        <p class="unit-hint" id="unit-hint" style="display:none;"></p>
                    </div>

                    {{-- Reason --}}
                    <div class="field-group">
                        <label class="field-label" for="reason">Reason</label>
                        <select name="reason" id="reason" class="field-select" required>
                            <option value="" disabled selected>Select a reason…</option>
                        </select>
                    </div>

                    {{-- Notes (optional) --}}
                    <div class="field-group field-group-last">
                        <label class="field-label" for="notes">Notes <span class="field-optional">(optional)</span></label>
                        <textarea name="notes" id="notes" class="field-select"
                                  rows="2" placeholder="Any extra detail…"
                                  style="resize:none; width:100%; font-family:inherit; font-size:14px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; background:#fff; color:var(--text); box-sizing:border-box;"></textarea>
                    </div>

                    <button type="submit" class="btn-place btn-place-full">
                        Submit for approval ↗
                    </button>
                </form>
            </div>

            @else
            {{-- Admin: pending approvals in right column --}}
            <div class="card card-pending-queue">
                <div class="card-title card-title-sm">
                    ⏳ Pending approvals
                    @if(count($pendingTransactions) > 0)
                        <span class="pill pill-orange pill-inline">{{ count($pendingTransactions) }}</span>
                    @endif
                </div>
                <p class="form-helper">Review each transaction before it affects stock levels.</p>

                @if(count($pendingTransactions) > 0)
                @foreach($pendingTransactions as $pt)
                @php $baseLabel = $pt->base_unit === 'grams' ? 'g' : $pt->base_unit; @endphp
                <div class="pending-item">
                    <div class="pending-item-header">
                        <span class="td-bold">{{ $pt->ingredient_name }}</span>
                        <span class="pill {{ strtolower($pt->transaction_type) === 'out' ? 'pill-red' : 'pill-green' }}">
                            {{ $pt->transaction_type }}
                        </span>
                    </div>
                    <div class="pending-item-meta">
                        <span>{{ $pt->entered_quantity }} {{ $pt->entered_unit }}</span>
                        @if($pt->entered_unit !== $baseLabel)
                            <span class="td-muted-sm">→ {{ number_format($pt->base_quantity) }} {{ $baseLabel }}</span>
                        @endif
                        <span class="td-muted-sm">· {{ $pt->reason }}</span>
                        @if($pt->notes)
                            <span class="td-muted-sm">· {{ $pt->notes }}</span>
                        @endif
                    </div>
                    <div class="pending-item-footer">
                        <span class="td-muted-sm">by {{ $pt->staff_name ?? '—' }} · {{ \Carbon\Carbon::parse($pt->transaction_date)->format('M j, Y') }}</span>
                        <div class="approval-actions">
                            <form method="POST" action="/inventory/{{ $pt->transaction_id }}/approve" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-approve" onclick="return confirm('Approve this transaction?')">✓ Approve</button>
                            </form>
                            <button class="btn-reject" onclick="toggleRejectForm({{ $pt->transaction_id }})">✕ Reject</button>
                        </div>
                    </div>
                    <div id="reject-form-{{ $pt->transaction_id }}" class="reject-form" style="display:none;">
                        <form method="POST" action="/inventory/{{ $pt->transaction_id }}/reject">
                            @csrf
                            <textarea name="rejection_note" class="field-select reject-note"
                                      placeholder="Reason for rejection (optional)…" rows="2"></textarea>
                            <div class="reject-form-actions">
                                <button type="submit" class="btn-reject-confirm">Confirm reject</button>
                                <button type="button" class="btn-cancel-reject"
                                        onclick="toggleRejectForm({{ $pt->transaction_id }})">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
                @else
                <div class="flash flash-success" style="margin-bottom:0;">
                    ✅ No pending transactions — all caught up!
                </div>
                @endif
            </div>
            @endif
        </div>

    </div>

    {{-- ═══════════════════════════
         TRANSACTION LOG (all roles)
    ════════════════════════════ --}}
    <div class="card">
        <div class="card-title card-title-lg">Stock transactions log</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ingredient</th>
                    <th>Type</th>
                    <th>Entered</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td class="td-muted-sm">{{ $loop->iteration }}</td>
                    <td class="td-bold">{{ $tx->ingredient_name }}</td>
                    <td>
                        <span class="pill {{ strtolower($tx->transaction_type) === 'out' ? 'pill-red' : 'pill-green' }}">
                            {{ $tx->transaction_type }}
                        </span>
                    </td>
                    <td>{{ $tx->entered_quantity }} {{ $tx->entered_unit }}</td>
                    <td class="td-muted-sm">{{ $tx->reason ?? '—' }}</td>
                    <td>
                        @php
                            $statusPill = match($tx->status) {
                                'approved' => 'pill-green',
                                'rejected' => 'pill-red',
                                default    => 'pill-orange',
                            };
                        @endphp
                        <span class="pill {{ $statusPill }}">{{ ucfirst($tx->status) }}</span>
                        @if($tx->status === 'rejected' && $tx->rejection_note)
                            <div class="rejection-note">{{ $tx->rejection_note }}</div>
                        @endif
                        @if(in_array($tx->status, ['approved','rejected']) && $tx->approved_by_name)
                            <div class="td-muted-sm">by {{ $tx->approved_by_name }}</div>
                        @endif
                    </td>
                    <td class="td-muted-sm">{{ $tx->staff_name ?? '—' }}</td>
                    <td class="td-muted-sm">
                        {{ \Carbon\Carbon::parse($tx->transaction_date)->format('M j, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="td-empty">No transactions recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     STYLES — scoped to inventory additions
════════════════════════════════════════════════════════ --}}
<style>
    /* Pending item card (right column) */
    .pending-item {
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
    }
    .pending-item:last-of-type { border-bottom: none; }
    .pending-item-header {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 4px;
    }
    .pending-item-meta {
        font-size: 13px; color: var(--text);
        display: flex; flex-wrap: wrap; gap: 4px;
        margin-bottom: 8px;
    }
    .pending-item-footer {
        display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap; gap: 6px;
    }

    /* Quantity + unit side by side */
    .qty-unit-row {
        display: flex;
        gap: 8px;
    }
    .qty-input   { flex: 1; min-width: 0; }
    .unit-select { width: 110px; flex-shrink: 0; }

    /* Min stock label under the bar */
    .stock-threshold {
        font-size: 11px;
        color: var(--muted);
        margin-left: 4px;
    }

    /* Optional field label */
    .field-optional { font-size: 11px; color: var(--muted); font-weight: 400; }

    /* Unit conversion hint */
    .unit-hint {
        font-size: 12px;
        color: var(--muted);
        margin-top: 4px;
    }

    /* Pending queue card highlight */
    .card-pending-queue {
        border-left: 4px solid #e07b20;
    }

    /* Approval action buttons */
    .approval-actions { display: flex; gap: 6px; flex-wrap: wrap; }

    .btn-approve {
        padding: 5px 12px; font-size: 12px; font-weight: 600;
        background: #22863a; color: #fff; border: none;
        border-radius: 6px; cursor: pointer;
        transition: background .15s;
    }
    .btn-approve:hover { background: #1a6b2c; }

    .btn-reject {
        padding: 5px 12px; font-size: 12px; font-weight: 600;
        background: #e53935; color: #fff; border: none;
        border-radius: 6px; cursor: pointer;
        transition: background .15s;
    }
    .btn-reject:hover { background: #b71c1c; }

    /* Reject inline form */
    .reject-form { margin-top: 8px; }
    .reject-note { width: 100%; font-size: 12.5px; margin-bottom: 6px; }
    .reject-form-actions { display: flex; gap: 6px; }

    .btn-reject-confirm {
        padding: 5px 12px; font-size: 12px; font-weight: 600;
        background: #e53935; color: #fff; border: none;
        border-radius: 6px; cursor: pointer;
    }
    .btn-cancel-reject {
        padding: 5px 12px; font-size: 12px; font-weight: 500;
        background: var(--card); color: var(--text);
        border: 1px solid var(--border); border-radius: 6px; cursor: pointer;
    }

    /* Rejection note shown in log */
    .rejection-note {
        font-size: 11.5px;
        color: #e53935;
        font-style: italic;
        margin-top: 3px;
    }
</style>

{{-- ══════════════════════════════════════════════════════
     JS — unit dropdown + reason dropdown + highlights
════════════════════════════════════════════════════════ --}}
<script>
    // Unit map from controller: { ingredient_id: { unit_group, base_unit, units[] } }
    const unitMap = @json($unitMap);

    const inReasons  = [
        'Restocking',
        'Low stock replenishment',
        'Initial stock',
        'Supplier delivery',
        'Correction (undercount)',
    ];
    const outReasons = [
        'Spoiled / expired',
        'Damaged',
        'Wastage',
        'Correction (overcount)',
        'Used in recipe',
    ];

    // ── Ingredient select → update allowed units ───────────────────────────
    function updateUnitDropdown(ingredientId) {
        const unitSelect = document.getElementById('entered_unit');
        const hint       = document.getElementById('unit-hint');
        unitSelect.innerHTML = '';

        if (!ingredientId || !unitMap[ingredientId]) {
            unitSelect.innerHTML = '<option value="" disabled selected>unit</option>';
            hint.style.display = 'none';
            return;
        }

        const { units, base_unit } = unitMap[ingredientId];

        units.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            if (u === base_unit) opt.selected = true;
            unitSelect.appendChild(opt);
        });

        // Show hint if more than one unit option
        if (units.length > 1) {
            hint.textContent = `Base unit is ${base_unit}. Other units will be converted automatically.`;
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }

    // ── Transaction type → update reason options ───────────────────────────
    function updateReasonDropdown() {
        const type    = document.querySelector('input[name="transaction_type"]:checked')?.value;
        const select  = document.getElementById('reason');
        const reasons = type === 'OUT' ? outReasons : inReasons;

        select.innerHTML = '<option value="" disabled selected>Select a reason…</option>';
        reasons.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r;
            opt.textContent = r;
            select.appendChild(opt);
        });
    }

    // ── Radio highlight ────────────────────────────────────────────────────
    function highlightType() {
        const inRadio  = document.querySelector('input[value="IN"]');
        const outRadio = document.querySelector('input[value="OUT"]');
        document.getElementById('lbl-in').style.borderColor  = inRadio.checked  ? '#22863a' : 'var(--border)';
        document.getElementById('lbl-out').style.borderColor = outRadio.checked ? '#e53935' : 'var(--border)';
    }

    // ── Transaction form validation ────────────────────────────────────────
    function validateTransactionForm(e) {
        const unit = document.getElementById('entered_unit').value;
        const qty  = document.getElementById('entered_quantity').value;
        const type = document.querySelector('input[name="transaction_type"]:checked');
        const reason = document.getElementById('reason').value;

        if (!document.getElementById('ingredient_id').value) {
            alert('Please select an ingredient.'); e.preventDefault(); return false;
        }
        if (!type) {
            alert('Please select a transaction type (IN or OUT).'); e.preventDefault(); return false;
        }
        if (!qty || parseFloat(qty) <= 0) {
            alert('Please enter a valid quantity.'); e.preventDefault(); return false;
        }
        if (!unit) {
            alert('Please select a unit.'); e.preventDefault(); return false;
        }
        if (!reason) {
            alert('Please select a reason.'); e.preventDefault(); return false;
        }
        return true;
    }

    // ── Toggle reject inline form ──────────────────────────────────────────
    function toggleRejectForm(id) {
        const el = document.getElementById('reject-form-' + id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
</script>
@endsection