@extends('layouts.app')

@section('title', 'Purchase Commodity Cargo')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="commodityOrderComposer()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-2 text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                <span>Procurement Portal</span>
                <span>/</span>
                <span class="text-slate-900">Bulk Agricultural Commodities</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Purchase Commodity Cargo</h1>
            <p class="text-sm text-slate-600 mt-1">Select a commodity contract, specify metric tonnage and maritime trade lanes, and proceed to Payment Gateway settlement to unlock vessel tracking.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('shipments.index') }}" class="inline-flex items-center px-3.5 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-md transition">
                View My Orders
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-md text-sm text-rose-800">
            <p class="font-semibold mb-1">Order submission requires attention:</p>
            <ul class="list-disc pl-5 space-y-0.5 text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('shop.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        @csrf

        <!-- Left Column: Catalog Selection & Logistics Parameters -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Step 1: Commodity Selection -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-6 h-6 rounded bg-slate-900 text-white text-xs font-bold flex items-center justify-center">1</span>
                        <h2 class="text-sm font-bold text-slate-900">Select Bulk Commodity Contract</h2>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Spot FOB Maritime Index (USD)</span>
                </div>

                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    @foreach($catalog as $key => $item)
                        <label
                            @click="selectCommodity('{{ $key }}', {{ $item['unit_price'] }}, '{{ addslashes($item['grade']) }}')"
                            :class="selectedCargo === '{{ $key }}' ? 'border-sky-700 bg-sky-50/50 ring-1 ring-sky-700' : 'border-slate-200 bg-white hover:border-slate-300'"
                            class="cursor-pointer border rounded-md p-4 transition block"
                        >
                            <input type="radio" name="cargo_type" value="{{ $key }}" x-model="selectedCargo" class="sr-only">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">HS {{ $item['hs_code'] }}</p>
                                    <h3 class="text-base font-bold text-slate-900 mt-0.5">{{ $key }}</h3>
                                    <p class="text-xs text-slate-600 mt-0.5">{{ $item['name'] }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-base font-bold text-slate-900 tabular-nums">${{ number_format($item['unit_price'], 2) }}</p>
                                    <p class="text-[11px] text-slate-500">per MT</p>
                                </div>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span>{{ $item['grade'] }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Step 2: Volume, Carrier & Trade Corridor -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-6 h-6 rounded bg-slate-900 text-white text-xs font-bold flex items-center justify-center">2</span>
                        <h2 class="text-sm font-bold text-slate-900">Tonnage, Carrier & Maritime Ports</h2>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Maritime Freight: ${{ number_format($freightRate, 2) }}/MT</span>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="weight" class="block text-xs font-semibold text-slate-700 mb-1.5">Order Tonnage (Metric Tons / MT) <span class="text-rose-600">*</span></label>
                            <input
                                type="number"
                                id="weight"
                                name="weight"
                                x-model.number="weight"
                                min="10"
                                max="250000"
                                step="1"
                                required
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 tabular-nums focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none"
                            >
                            <div class="flex items-center gap-2 mt-2">
                                @foreach([250, 500, 1000, 5000] as $preset)
                                    <button type="button" @click="weight = {{ $preset }}" class="px-2 py-0.5 text-[11px] font-medium border border-slate-200 rounded bg-slate-50 hover:bg-slate-100 text-slate-700 tabular-nums">
                                        {{ number_format($preset) }} MT
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="carrier_id" class="block text-xs font-semibold text-slate-700 mb-1.5">Shipping Carrier Line <span class="text-rose-600">*</span></label>
                            <select id="carrier_id" name="carrier_id" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none">
                                @foreach($carriers as $carrier)
                                    <option value="{{ $carrier->id }}" {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }} ({{ $carrier->code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1.5">Vessel allocation activates immediately upon payment settlement.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="origin_id" class="block text-xs font-semibold text-slate-700 mb-1.5">Port of Loading (Origin) <span class="text-rose-600">*</span></label>
                            <select id="origin_id" name="origin_id" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none">
                                @foreach($ports as $port)
                                    <option value="{{ $port->id }}" {{ (old('origin_id') ? old('origin_id') == $port->id : $port->code === 'THBKK') ? 'selected' : '' }}>
                                        {{ $port->code }} — {{ $port->name }} ({{ $port->country }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="destination_id" class="block text-xs font-semibold text-slate-700 mb-1.5">Port of Discharge (Destination) <span class="text-rose-600">*</span></label>
                            <select id="destination_id" name="destination_id" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none">
                                @foreach($ports as $port)
                                    <option value="{{ $port->id }}" {{ (old('destination_id') ? old('destination_id') == $port->id : $port->code === 'IDTPP') ? 'selected' : '' }}>
                                        {{ $port->code }} — {{ $port->name }} ({{ $port->country }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="grade_specification" class="block text-xs font-semibold text-slate-700 mb-1.5">Quality & Grade Specification</label>
                            <input
                                type="text"
                                id="grade_specification"
                                name="grade_specification"
                                x-model="gradeSpec"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none"
                            >
                        </div>

                        <div>
                            <label for="payment_method" class="block text-xs font-semibold text-slate-700 mb-1.5">Preferred Settlement Channel</label>
                            <select id="payment_method" name="payment_method" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none">
                                <option value="QRIS_REALTIME">QRIS Real-Time Clearing</option>
                                <option value="VIRTUAL_ACCOUNT">Corporate Bank Virtual Account</option>
                                <option value="LETTER_OF_CREDIT">Documentary Letter of Credit (L/C)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1.5">Commercial Remarks (Optional)</label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Delivery instructions, surveyor inspection notes, or warehouse reference..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-sky-700 focus:ring-1 focus:ring-sky-700 outline-none">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Proforma Invoice Summary & Tracking Unlock Notice -->
        <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-6">
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 text-white">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Proforma Commercial Invoice</p>
                    <h2 class="text-lg font-bold mt-0.5" x-text="selectedCargo + ' Bulk Purchase'"></h2>
                </div>

                <div class="p-5 space-y-4">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Selected Commodity</dt>
                            <dd class="font-semibold text-slate-900" x-text="selectedCargo"></dd>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Contract Quantity</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums" x-text="formatNumber(weight) + ' MT'"></dd>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Commodity Unit Rate</dt>
                            <dd class="font-medium text-slate-900 tabular-nums" x-text="'$' + formatCurrency(unitPrice) + ' / MT'"></dd>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Commodity Value Subtotal</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums" x-text="'$' + formatCurrency(commoditySubtotal)"></dd>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <dt>Maritime Ocean Freight (${{ number_format($freightRate, 2) }}/MT)</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums" x-text="'$' + formatCurrency(freightSubtotal)"></dd>
                        </div>
                        <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                            <dt class="text-sm font-bold text-slate-900">Total Gross Invoice Due</dt>
                            <dd class="text-xl font-bold text-slate-900 tabular-nums" x-text="'$' + formatCurrency(grossTotal)"></dd>
                        </div>
                    </dl>

                    <!-- Tracking Lock Policy Notice -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-md flex items-start space-x-3">
                        <svg class="w-5 h-5 text-slate-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div class="text-xs text-slate-600 leading-relaxed">
                            <p class="font-semibold text-slate-900">Vessel Tracking Gate Policy</p>
                            <p class="mt-0.5">Live AIS coordinates and vessel route maps remain locked until payment settlement completes at the Payment Gateway.</p>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-md transition flex items-center justify-center space-x-2">
                        <span>Create Order &amp; Proceed to Payment Gateway</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Customer Unpaid / Pending Orders Waiting Checkout -->
            @if($pendingOrders->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Awaiting Payment Settlement</h3>
                        <span class="px-2 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-800 rounded">{{ $pendingOrders->count() }} Pending</span>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @foreach($pendingOrders as $order)
                            <div class="p-4 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 font-mono">{{ $order->shipment_number }}</p>
                                    <p class="text-xs text-slate-600 truncate mt-0.5">
                                        {{ $order->cargo_type }} &bull; {{ number_format($order->weight, 0) }} MT
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5 tabular-nums font-medium">
                                        Due: ${{ number_format($order->gross_amount_usd, 2) }}
                                    </p>
                                </div>
                                <a href="{{ route('shipments.payment', $order->id) }}" class="shrink-0 px-3 py-1.5 bg-sky-700 hover:bg-sky-800 text-white text-xs font-semibold rounded-md transition">
                                    Pay Now
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </form>
</div>

<script>
function commodityOrderComposer() {
    return {
        selectedCargo: '{{ old("cargo_type", "Rice") }}',
        unitPrice: {{ $catalog[old('cargo_type', 'Rice')]['unit_price'] ?? 580.00 }},
        gradeSpec: '{{ addslashes(old("grade_specification", $catalog[old("cargo_type", "Rice")]["grade"] ?? "Export Grade A, Broken 5% Max")) }}',
        weight: {{ (float) old('weight', 500) }},
        freightRate: {{ $freightRate }},

        selectCommodity(key, price, grade) {
            this.selectedCargo = key;
            this.unitPrice = price;
            this.gradeSpec = grade;
        },

        get commoditySubtotal() {
            const w = Number(this.weight) || 0;
            return Math.max(0, w * this.unitPrice);
        },

        get freightSubtotal() {
            const w = Number(this.weight) || 0;
            return Math.max(0, w * this.freightRate);
        },

        get grossTotal() {
            return this.commoditySubtotal + this.freightSubtotal;
        },

        formatCurrency(val) {
            return Number(val || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatNumber(val) {
            return Number(val || 0).toLocaleString('en-US', {
                maximumFractionDigits: 2
            });
        }
    };
}
</script>
@endsection
