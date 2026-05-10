@extends('layouts.app')

@section('title', 'Reports')

@section('content')

{{-- ── PDF PRINT STYLES ── --}}
<style>
    @media print {
        nav, .report-toolbar, .section-tag, .page-sub { display: none !important; }
        body { background: #fff !important; }
        .page { padding: 16px !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
        .kpi-row { break-inside: avoid; }
        .print-header { display: block !important; }
    }
    .print-header {
        display: none;
        margin-bottom: 18px;
        border-bottom: 2px solid #2c1a0e;
        padding-bottom: 10px;
    }
    .print-header h2 { font-size: 20px; font-weight: 800; }
    .print-header p  { font-size: 12px; color: #757575; margin-top: 4px; }
</style>

<div class="page">

    {{-- Print-only header --}}
    <div class="print-header">
        <h2>La Cafe Sistema — Sales Report</h2>
        <p>Don Macchiato Coffee Shop &nbsp;|&nbsp; Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}</p>
    </div>

    <div class="page-title">Reports</div>
    <div class="page-sub">
        Filter by date range and export as PDF
    </div>

    {{-- ── TOOLBAR: date filter + export ── --}}
    <div class="report-toolbar">
        <form method="GET" action="/reports" class="toolbar-form">
            <div class="toolbar-fields">
                <div class="toolbar-field">
                    <label class="field-label" for="date_from">From</label>
                    <input type="date" name="date_from" id="date_from"
                           class="field-select toolbar-date"
                           value="{{ $dateFrom }}">
                </div>
                <div class="toolbar-field">
                    <label class="field-label" for="date_to">To</label>
                    <input type="date" name="date_to" id="date_to"
                           class="field-select toolbar-date"
                           value="{{ $dateTo }}">
                </div>
                <button type="submit" class="btn toolbar-btn-apply">
                    Apply ↗
                </button>
            </div>

            {{-- Quick presets --}}
            <div class="toolbar-presets">
                <span class="preset-label">Quick:</span>
                @php
                    $presets = [
                        'Today'      => [now()->toDateString(), now()->toDateString()],
                        'This week'  => [now()->startOfWeek()->toDateString(), now()->toDateString()],
                        'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
                        'This year'  => [now()->startOfYear()->toDateString(), now()->toDateString()],
                    ];
                @endphp
                @foreach($presets as $label => [$from, $to])
                    @php $active = $dateFrom === $from && $dateTo === $to; @endphp
                    <a href="/reports?date_from={{ $from }}&date_to={{ $to }}"
                       class="preset-pill {{ $active ? 'preset-pill-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </form>

        <button onclick="window.print()" class="btn toolbar-btn-pdf">
            ⬇ Export PDF
        </button>
    </div>

    {{-- ── KPI Cards ── --}}
    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value">₱{{ number_format($totalRevenue, 0) }}</div>
            <div class="kpi-sub">
                {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}
                –
                {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
            </div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Top Product</div>
            <div class="kpi-product-name">{{ $topProduct->product_name ?? '—' }}</div>
            <div class="kpi-sub">₱{{ number_format($topProduct->total_revenue ?? 0, 0) }} revenue</div>
        </div>
    </div>

    {{-- ── Product Sales Report ── --}}
    <div class="card">
        <div class="section-header">
            <span class="section-title">Product sales report</span>
            <span class="section-tag">VIEW: product_sales_report</span>
        </div>

        @php $maxRevenue = collect($productSales)->max('total_revenue') ?: 1; @endphp

        @forelse($productSales as $row)
        @php $pct = round(($row->total_revenue / $maxRevenue) * 100); @endphp
        <div class="sales-row">
            <div class="sales-name">{{ $row->product_name }}</div>
            <div class="sales-bar-wrap">
                <div class="sales-bar-track">
                    <div class="sales-bar-fill" style="width:{{ $pct }}%;"></div>
                </div>
            </div>
            <div class="sales-meta">{{ $row->total_sold ?? 0 }} sold &middot; ₱{{ number_format($row->total_revenue ?? 0, 0) }}</div>
        </div>
        @empty
        <p class="td-muted-p">No sales data for this period.</p>
        @endforelse
    </div>

    {{-- ── Sales Summary ── --}}
    <div class="card">
        <div class="section-header">
            <span class="section-title">Sales summary</span>
            <span class="section-tag">VIEW: sales_summary</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Staff</th>
                    <th>Amount</th>
                    <th>Order Status</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesSummary as $row)
                <tr>
                    <td>#{{ str_pad($row->order_id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="td-staff">{{ $row->staff_name }}</td>
                    <td class="td-amount">₱{{ number_format($row->total_amount, 0) }}</td>
                    <td>
                        <span class="pill {{ strtolower($row->status) === 'paid' ? 'pill-green' : 'pill-orange' }}">
                            {{ $row->status }}
                        </span>
                    </td>
                    <td class="td-staff">{{ $row->payment_method ?? '—' }}</td>
                    <td>
                        @if($row->payment_status)
                            <span class="pill pill-green">{{ $row->payment_status }}</span>
                        @else
                            <span class="muted-dash">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="td-empty">No orders in this date range.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- ── Toolbar + preset styles ── --}}
<style>
    .report-toolbar {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .toolbar-form      { display: flex; flex-direction: column; gap: 10px; }
    .toolbar-fields    { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
    .toolbar-field     { display: flex; flex-direction: column; }
    .toolbar-date      { width: 160px; padding: 8px 12px; font-size: 13.5px; }
    .toolbar-btn-apply {
        padding: 8px 18px; font-size: 13.5px; font-weight: 600;
        background: var(--accent); color: #fff; border: none;
        border-radius: 8px; cursor: pointer; transition: background .15s, transform .1s;
    }
    .toolbar-btn-apply:hover { background: var(--accent2); transform: translateY(-1px); }

    .toolbar-presets   { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .preset-label      { font-size: 11.5px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .preset-pill {
        font-size: 12px; padding: 4px 12px; border-radius: 20px;
        border: 1px solid var(--border); background: var(--card);
        color: var(--text); text-decoration: none; font-weight: 500;
        transition: background .15s, border-color .15s, color .15s;
    }
    .preset-pill:hover       { background: #f0ebe3; border-color: var(--accent); }
    .preset-pill-active      { background: var(--accent); color: #fff; border-color: var(--accent); font-weight: 700; }
    .preset-pill-active:hover { background: var(--accent2); }

    .toolbar-btn-pdf {
        padding: 10px 20px; font-size: 13.5px; font-weight: 700;
        background: #2c1a0e; color: #fff; border: none;
        border-radius: 8px; cursor: pointer; white-space: nowrap;
        transition: background .15s, transform .1s;
        align-self: flex-end;
    }
    .toolbar-btn-pdf:hover { background: #1a0f08; transform: translateY(-1px); }

    @media (max-width: 700px) {
        .report-toolbar { flex-direction: column; align-items: stretch; }
        .toolbar-btn-pdf { width: 100%; text-align: center; }
        .toolbar-date { width: 100%; }
    }
</style>

@endsection