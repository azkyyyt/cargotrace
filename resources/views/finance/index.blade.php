@extends('layouts.app')

@section('title', 'Revenue & Financial Statements')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Header & Report Downloads -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-2 text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                <span>Corporate Accounting</span>
                <span>/</span>
                <span class="text-slate-900">Revenue &amp; Vessel Fee Ledger</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Revenue &amp; Financial Statements</h1>
            <p class="text-sm text-slate-600 mt-1">Accumulated commodity purchase revenues, maritime vessel charter fees, settled net revenue, and unsettled receivables.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('finance.export.csv', request()->query()) }}" class="inline-flex items-center space-x-2 px-3.5 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-md transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Export CSV (.csv)</span>
            </a>

            <a href="{{ route('finance.export.xlsx', request()->query()) }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-md transition">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download Excel Report (.xlsx)</span>
            </a>
        </div>
    </div>

    <!-- Financial KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Settled Gross Revenue -->
        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Gross Revenue (Paid)</p>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">{{ $summary['paid_count'] }} Paid</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">${{ number_format($summary['paid_gross_usd'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 tabular-nums">
                Cargo ${{ number_format($summary['paid_commodity_usd'], 0) }} + Freight ${{ number_format($summary['paid_freight_usd'], 0) }}
            </p>
        </div>

        <!-- Vessel Charter Fee Deduction -->
        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Vessel Charter Fees</p>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded">Fee Kapal</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">-${{ number_format($summary['paid_vessel_fee_usd'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-1.5">Deducted carrier charter allocation ($28/MT settled base)</p>
        </div>

        <!-- Net Clean Revenue -->
        <div class="bg-slate-900 border border-slate-900 rounded-lg p-5 shadow-sm text-white">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">Net Revenue (Clean)</p>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-emerald-900/60 text-emerald-300 border border-emerald-700 rounded">Sudah Bersih</span>
            </div>
            <p class="text-2xl font-bold text-white mt-2 tabular-nums">${{ number_format($summary['paid_net_usd'], 2) }}</p>
            <p class="text-xs text-slate-400 mt-1.5">Verified Gross minus Vessel Charter Fees</p>
        </div>

        <!-- Unsettled / Pending Receivables -->
        <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Unsettled / Pending</p>
                <span class="px-2 py-0.5 text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 rounded">{{ $summary['pending_count'] }} Belum Lunas</span>
            </div>
            <p class="text-2xl font-bold text-amber-700 mt-2 tabular-nums">${{ number_format($summary['pending_gross_usd'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 tabular-nums">
                Projected Net: ${{ number_format($summary['pending_net_usd'], 2) }} after settlement
            </p>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('finance.index') }}" method="GET" class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Settlement Status</label>
                <select name="payment_status" class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-900 bg-white focus:border-sky-700 outline-none">
                    <option value="">All Contracts ({{ $summary['total_contracts'] }})</option>
                    <option value="PAID" {{ request('payment_status') === 'PAID' ? 'selected' : '' }}>Settled Clean (PAID)</option>
                    <option value="PENDING" {{ request('payment_status') === 'PENDING' ? 'selected' : '' }}>Unsettled (PENDING)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Commodity</label>
                <select name="cargo_type" class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-900 bg-white focus:border-sky-700 outline-none">
                    <option value="">All Commodities</option>
                    @foreach(['Rice', 'Wheat', 'Corn', 'Soybeans'] as $grain)
                        <option value="{{ $grain }}" {{ request('cargo_type') === $grain ? 'selected' : '' }}>{{ $grain }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Shipping Carrier</label>
                <select name="carrier_id" class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-900 bg-white focus:border-sky-700 outline-none">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" {{ (string) request('carrier_id') === (string) $carrier->id ? 'selected' : '' }}>
                            {{ $carrier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-900 focus:border-sky-700 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-900 focus:border-sky-700 outline-none">
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-md transition">
                    Filter Ledger
                </button>
                @if(request()->hasAny(['payment_status', 'cargo_type', 'carrier_id', 'start_date', 'end_date']))
                    <a href="{{ route('finance.index') }}" class="px-3 py-1.5 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-md transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Transaction Revenue Ledger Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Itemized Purchase &amp; Charter Fee Ledger</h2>
            <span class="text-xs text-slate-500">All monetary values in United States Dollars (USD)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-[11px] font-semibold uppercase tracking-wider text-slate-600">
                        <th class="py-3 px-4">Order Ref</th>
                        <th class="py-3 px-4">Customer &amp; Commodity</th>
                        <th class="py-3 px-4">Payment Status</th>
                        <th class="py-3 px-4 text-right">Commodity ($)</th>
                        <th class="py-3 px-4 text-right">Freight ($)</th>
                        <th class="py-3 px-4 text-right">Gross Total ($)</th>
                        <th class="py-3 px-4 text-right">Vessel Fee ($)</th>
                        <th class="py-3 px-4 text-right">Net Clean ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <a href="{{ route('shipments.show', $s->id) }}" class="font-mono font-semibold text-sky-700 hover:text-sky-900">
                                    {{ $s->shipment_number }}
                                </a>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $s->created_at?->format('d M Y') }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900">{{ $s->customer?->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $s->cargo_type }} &bull; {{ number_format($s->weight, 0) }} MT &bull; {{ $s->carrier?->code ?? '-' }}
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($s->payment_status === 'PAID')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        PAID
                                    </span>
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ $s->payment_method ?? 'Settled' }}</div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        PENDING
                                    </span>
                                    <div class="text-[10px] text-amber-700 mt-0.5">Unsettled (Kotor)</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-medium text-slate-700 tabular-nums">${{ number_format($s->commodity_price_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-medium text-slate-700 tabular-nums">${{ number_format($s->freight_charge_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900 tabular-nums">${{ number_format($s->gross_amount_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-medium text-rose-700 tabular-nums">-${{ number_format($s->vessel_fee_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-bold tabular-nums {{ $s->payment_status === 'PAID' ? 'text-emerald-700' : 'text-slate-500' }}">
                                ${{ number_format($s->net_revenue_usd, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 px-4 text-center text-sm text-slate-500">No financial records match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($summary['total_contracts'] > 0)
                    <tfoot class="border-t-2 border-slate-300 bg-slate-50 text-xs">
                        @if($summary['paid_count'] > 0)
                            <tr class="font-semibold text-slate-900">
                                <td colspan="3" class="py-3 px-4">
                                    Settled Clean Revenue Total ({{ $summary['paid_count'] }} Paid Contracts)
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">${{ number_format($summary['paid_commodity_usd'], 2) }}</td>
                                <td class="py-3 px-4 text-right tabular-nums">${{ number_format($summary['paid_freight_usd'], 2) }}</td>
                                <td class="py-3 px-4 text-right tabular-nums">${{ number_format($summary['paid_gross_usd'], 2) }}</td>
                                <td class="py-3 px-4 text-right text-rose-700 tabular-nums">-${{ number_format($summary['paid_vessel_fee_usd'], 2) }}</td>
                                <td class="py-3 px-4 text-right text-emerald-700 tabular-nums">${{ number_format($summary['paid_net_usd'], 2) }}</td>
                            </tr>
                        @endif
                        <tr class="font-bold bg-slate-900 text-white">
                            <td colspan="3" class="py-3 px-4">
                                Grand Ledger Total ({{ $summary['total_contracts'] }} All Filtered Contracts incl. Pending)
                            </td>
                            <td class="py-3 px-4 text-right tabular-nums">${{ number_format($summary['combined_commodity_usd'], 2) }}</td>
                            <td class="py-3 px-4 text-right tabular-nums">${{ number_format($summary['combined_freight_usd'], 2) }}</td>
                            <td class="py-3 px-4 text-right tabular-nums">${{ number_format($summary['combined_gross_usd'], 2) }}</td>
                            <td class="py-3 px-4 text-right text-rose-300 tabular-nums">-${{ number_format($summary['combined_vessel_fee_usd'], 2) }}</td>
                            <td class="py-3 px-4 text-right text-emerald-300 tabular-nums">${{ number_format($summary['combined_net_usd'], 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($shipments->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 bg-white">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
