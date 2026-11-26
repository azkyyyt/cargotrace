@extends('layouts.app')

@section('title', 'Payment Gateway — ' . $shipment->shipment_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ channel: '{{ old('payment_method', $shipment->payment_method ?? 'QRIS_REALTIME') }}' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-2 text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                <a href="{{ route('shop.index') }}" class="hover:text-slate-900">Purchase Cargo</a>
                <span>/</span>
                <span class="text-slate-900">Payment Gateway Checkout</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Settlement Checkout &mdash; {{ $shipment->shipment_number }}</h1>
            <p class="text-sm text-slate-600 mt-1">Complete escrow payment verification to confirm vessel hold allocation and unlock live maritime route tracking.</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
            Status: {{ $shipment->payment_status }} &bull; Tracking Locked
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Payment Channel Form -->
        <div class="lg:col-span-7 bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Select Clearing Method</h2>
                <span class="text-xs text-slate-500 font-mono">TXN-{{ $shipment->shipment_number }}</span>
            </div>

            <form action="{{ route('shipments.pay', $shipment->id) }}" method="POST" class="p-5 space-y-5">
                @csrf

                <div class="space-y-3">
                    <!-- QRIS Option -->
                    <label
                        @click="channel = 'QRIS_REALTIME'"
                        :class="channel === 'QRIS_REALTIME' ? 'border-sky-700 bg-sky-50/40 ring-1 ring-sky-700' : 'border-slate-200 bg-white hover:border-slate-300'"
                        class="block cursor-pointer border rounded-md p-4 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="payment_method" value="QRIS_REALTIME" x-model="channel" class="h-4 w-4 text-sky-700 border-slate-300">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">QRIS Instant Clearing</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Standardized Indonesian QR clearing gateway with immediate confirmation.</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded">Instant</span>
                        </div>

                        <div x-show="channel === 'QRIS_REALTIME'" class="mt-4 pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center gap-4">
                            <div class="p-2.5 bg-white border border-slate-200 rounded-md shrink-0">
                                <!-- Crisp deterministic QR verification graphic -->
                                <svg class="w-24 h-24 text-slate-900" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M3 3h7v7H3V3zm2 2v3h3V5H5zm9-2h7v7h-7V3zm2 2v3h3V5h-3zM3 14h7v7H3v-7zm2 2v3h3v-3H5zm11-2h2v2h-2v-2zm3 0h2v2h-2v-2zm-5 3h2v2h-2v-2zm3 0h2v2h-2v-2zm3 0h2v4h-2v-4zm-6 3h2v2h-2v-2zm3 0h2v2h-2v-2z"/>
                                </svg>
                            </div>
                            <div class="text-xs text-slate-600 space-y-1">
                                <p class="font-semibold text-slate-900">Merchant ID: PT CargoTrace Maritime Clearing</p>
                                <p>NMID: <span class="font-mono text-slate-900">ID1026008941203</span></p>
                                <p>Invoice Reference: <span class="font-mono text-slate-900">{{ $shipment->shipment_number }}</span></p>
                                <p class="text-slate-500">Settlement Amount: <strong class="text-slate-900 tabular-nums">${{ number_format($shipment->gross_amount_usd, 2) }}</strong></p>
                            </div>
                        </div>
                    </label>

                    <!-- Virtual Account Option -->
                    <label
                        @click="channel = 'VIRTUAL_ACCOUNT'"
                        :class="channel === 'VIRTUAL_ACCOUNT' ? 'border-sky-700 bg-sky-50/40 ring-1 ring-sky-700' : 'border-slate-200 bg-white hover:border-slate-300'"
                        class="block cursor-pointer border rounded-md p-4 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="payment_method" value="VIRTUAL_ACCOUNT" x-model="channel" class="h-4 w-4 text-sky-700 border-slate-300">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Corporate Bank Virtual Account</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Direct corporate treasury transfer (BCA / Mandiri / DBS Institutional).</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded">T+0</span>
                        </div>

                        <div x-show="channel === 'VIRTUAL_ACCOUNT'" class="mt-3 pt-3 border-t border-slate-200 text-xs text-slate-600 space-y-1">
                            <p>Virtual Account Number:</p>
                            <p class="text-base font-mono font-bold text-slate-900 tracking-wider">8808-2026-{{ str_pad((string) $shipment->id, 6, '0', STR_PAD_LEFT) }}</p>
                            <p class="text-slate-500">Beneficiary: CargoTrace Maritime Escrow Trust</p>
                        </div>
                    </label>

                    <!-- Letter of Credit Option -->
                    <label
                        @click="channel = 'LETTER_OF_CREDIT'"
                        :class="channel === 'LETTER_OF_CREDIT' ? 'border-sky-700 bg-sky-50/40 ring-1 ring-sky-700' : 'border-slate-200 bg-white hover:border-slate-300'"
                        class="block cursor-pointer border rounded-md p-4 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="payment_method" value="LETTER_OF_CREDIT" x-model="channel" class="h-4 w-4 text-sky-700 border-slate-300">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Documentary Letter of Credit (L/C)</p>
                                    <p class="text-xs text-slate-500 mt-0.5">SWIFT MT700 trade finance instrument backed by issuing bank guarantee.</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded">Trade Finance</span>
                        </div>

                        <div x-show="channel === 'LETTER_OF_CREDIT'" class="mt-3 pt-3 border-t border-slate-200 text-xs text-slate-600 space-y-1">
                            <p>Document Reference: <span class="font-mono font-semibold text-slate-900">LC-SBLC-{{ $shipment->shipment_number }}</span></p>
                            <p class="text-slate-500">Bill of Lading release authorized under Uniform Customs &amp; Practice (UCP 600).</p>
                        </div>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <a href="{{ route('shipments.show', $shipment->id) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Return to Order Overview
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-md transition flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Confirm &amp; Settle Payment (${{ number_format($shipment->gross_amount_usd, 2) }})</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Invoice Breakdown Summary -->
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-900 text-white">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Commercial Invoice</p>
                <h2 class="text-lg font-bold mt-0.5">{{ $shipment->shipment_number }}</h2>
            </div>

            <div class="p-5 space-y-4">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-slate-600">
                        <dt>Buyer</dt>
                        <dd class="font-medium text-slate-900">{{ $shipment->customer->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <dt>Commodity</dt>
                        <dd class="font-semibold text-slate-900">{{ $shipment->cargo_type }} ({{ number_format($shipment->weight, 0) }} MT)</dd>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <dt>Trade Lane</dt>
                        <dd class="font-mono text-xs font-semibold text-slate-900">
                            {{ $shipment->origin?->code }} &rarr; {{ $shipment->destination?->code }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <dt>Assigned Carrier</dt>
                        <dd class="font-medium text-slate-900">{{ $shipment->carrier?->name ?? 'TBD' }}</dd>
                    </div>
                </dl>

                <div class="pt-4 border-t border-slate-200 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Commodity Contract Value</span>
                        <span class="font-medium text-slate-900 tabular-nums">${{ number_format($shipment->commodity_price_usd, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Maritime Freight Charge</span>
                        <span class="font-medium text-slate-900 tabular-nums">${{ number_format($shipment->freight_charge_usd, 2) }}</span>
                    </div>
                    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                        <span class="font-bold text-slate-900">Total Settlement Due (USD)</span>
                        <span class="text-xl font-bold text-slate-900 tabular-nums">${{ number_format($shipment->gross_amount_usd, 2) }}</span>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-md text-xs text-slate-600 leading-relaxed">
                    Once settled, shipment status transitions automatically from <strong class="text-slate-900">CREATED</strong> to <strong class="text-slate-900">BOOKED</strong> and live Leaflet vessel telemetry unlocks on your tracking dashboard.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
