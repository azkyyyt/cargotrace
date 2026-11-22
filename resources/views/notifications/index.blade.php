@extends('layouts.app')

@section('title', 'Global Intelligence & Notification Center')

@section('content')
<div class="space-y-6" x-data="notificationsCenter()">
    <!-- Top Bar: Title, Live Status & Refresh -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Global Intelligence &amp; Notifications</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Feeds Connected
                </span>
            </div>
            <p class="text-xs text-slate-500">
                Real-time international grain logistics news, live foreign exchange rates (SGD, IDR, BRL, CNY), and worldwide maritime weather bulletins.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('notifications.index', ['refresh' => 1]) }}" class="inline-flex items-center text-xs bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold px-3 py-2 rounded-lg border border-sky-200 transition">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sync Live APIs
            </a>
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-lg border border-slate-200 transition">
                    Mark Alerts Read
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Sub-Tabs -->
    <div class="flex border-b border-slate-200 bg-white px-4 rounded-xl shadow-sm space-x-2 overflow-x-auto">
        <button type="button" @click="activeTab = 'forex'" :class="activeTab === 'forex' ? 'border-sky-600 text-sky-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-4 text-xs border-b-2 flex items-center space-x-2 whitespace-nowrap transition">
            <span>💱 Live Forex &amp; Grain Valuations</span>
            <span class="bg-sky-100 text-sky-800 text-[10px] font-bold px-1.5 py-0.5 rounded">USD Base</span>
        </button>
        <button type="button" @click="activeTab = 'weather'" :class="activeTab === 'weather' ? 'border-sky-600 text-sky-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-4 text-xs border-b-2 flex items-center space-x-2 whitespace-nowrap transition">
            <span>🌊 Worldwide Port Weather Bulletins</span>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.5 rounded">{{ count($weatherBulletins) }} Ports</span>
        </button>
        <button type="button" @click="activeTab = 'wire'" :class="activeTab === 'wire' ? 'border-sky-600 text-sky-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-4 text-xs border-b-2 flex items-center space-x-2 whitespace-nowrap transition">
            <span>📰 Breaking Maritime Wire</span>
            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Real-Time</span>
        </button>
        <button type="button" @click="activeTab = 'inapp'" :class="activeTab === 'inapp' ? 'border-sky-600 text-sky-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-4 text-xs border-b-2 flex items-center space-x-2 whitespace-nowrap transition">
            <span>🔔 Consignment Alerts</span>
            <span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $notifications->total() }}</span>
        </button>
    </div>

    <!-- TAB 1: Real-Time Currency Exchange Rates & Bulk Commodity Converter -->
    <div x-show="activeTab === 'forex'" class="space-y-6">
        <!-- Live Currency Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($exchangeRates['currencies'] as $code => $cur)
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:border-sky-300 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xl">{{ $cur['flag'] }}</span>
                        <span class="font-mono text-xs font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded">{{ $code }}</span>
                    </div>
                    <div class="mt-2">
                        <div class="text-[11px] font-medium text-slate-400">{{ $cur['name'] }}</div>
                        <div class="text-lg font-black text-slate-900 font-mono mt-0.5">
                            @if($code === 'IDR')
                                Rp {{ number_format($cur['rate'], 0, ',', '.') }}
                            @else
                                {{ number_format($cur['rate'], 4) }}
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                        <span class="text-slate-500 font-medium">Per 1.00 USD</span>
                        <span class="font-bold text-emerald-600 flex items-center">
                            <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Live Market
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Interactive FX Calculator for Grain Freight & Invoicing -->
        <div class="bg-gradient-to-br from-slate-900 to-sky-950 p-6 rounded-2xl text-white shadow-xl">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2 max-w-md">
                    <span class="text-xs font-bold text-sky-400 uppercase tracking-widest">Interactive Grain Cargo Valuator</span>
                    <h2 class="text-xl font-bold">Real-Time Currency Settlement Calculator</h2>
                    <p class="text-xs text-slate-300">
                        Calculate multi-currency values for grain consignments, ocean chartering fees, and letters of credit against live foreign exchange rates.
                    </p>
                    <div class="text-[11px] text-sky-200/80 pt-1">
                        Live Data Source: <strong class="text-white">{{ $exchangeRates['source'] }}</strong>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/15 flex-1 max-w-xl space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-200 mb-1">Base Amount (USD $):</label>
                            <input type="number" x-model="calcUsd" class="w-full bg-slate-800 text-white font-mono font-bold text-sm px-3 py-2 rounded-lg border border-slate-600 focus:outline-none focus:border-sky-400" min="1" step="100">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-200 mb-1">Target Currency:</label>
                            <select x-model="selectedCur" class="w-full bg-slate-800 text-white font-bold text-sm px-3 py-2 rounded-lg border border-slate-600 focus:outline-none focus:border-sky-400">
                                <option value="IDR">🇮🇩 Indonesian Rupiah (IDR)</option>
                                <option value="SGD">🇸🇬 Singapore Dollar (SGD)</option>
                                <option value="BRL">🇧🇷 Brazilian Real (BRL)</option>
                                <option value="CNY">🇨🇳 Chinese Yuan (CNY)</option>
                                <option value="AUD">🇦🇺 Australian Dollar (AUD)</option>
                                <option value="EUR">🇪🇺 Euro (EUR)</option>
                                <option value="THB">🇹🇭 Thai Baht (THB)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Calculated Result Display -->
                    <div class="bg-black/30 p-3 rounded-lg flex items-center justify-between border border-white/10">
                        <span class="text-xs text-slate-300">Equivalent Settlement Value:</span>
                        <div class="text-right">
                            <span class="text-lg font-black text-amber-300 font-mono" x-text="computedConvertedAmount"></span>
                            <div class="text-[10px] text-slate-400" x-text="'Rate: 1 USD = ' + (rates[selectedCur] ? rates[selectedCur].toLocaleString() : 0) + ' ' + selectedCur"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Commodity Trade Benchmarks -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Global Bulk Grain Standard Price Benchmarks (FOB Equivalent)</h3>
            <p class="text-xs text-slate-500 mb-4">Real-time local currency pricing per Metric Ton (MT) based on latest international exchange parity.</p>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Commodity &amp; Loading Basin</th>
                            <th class="py-2.5 px-3">Benchmark (USD / MT)</th>
                            <th class="py-2.5 px-3">🇮🇩 Value (IDR / MT)</th>
                            <th class="py-2.5 px-3">🇧🇷 Value (BRL / MT)</th>
                            <th class="py-2.5 px-3">🇸🇬 Value (SGD / MT)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @foreach($exchangeRates['commodity_benchmarks'] as $comm)
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-3 px-3 font-bold text-slate-900">{{ $comm['name'] }}</td>
                                <td class="py-3 px-3 font-mono font-bold text-sky-700">${{ number_format($comm['usd_mt'], 2) }}</td>
                                <td class="py-3 px-3 font-mono">Rp {{ number_format($comm['idr_mt'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 font-mono">R$ {{ number_format($comm['brl_mt'], 2) }}</td>
                                <td class="py-3 px-3 font-mono">S$ {{ number_format($comm['sgd_mt'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Worldwide Marine Weather Bulletins & Navigational Impact -->
    <div x-show="activeTab === 'weather'" class="space-y-6">
        <div class="bg-sky-50 border border-sky-200 p-4 rounded-xl flex items-center justify-between text-xs text-sky-900">
            <div class="flex items-center space-x-2">
                <span class="text-lg">🛰️</span>
                <span>
                    <strong>Open-Meteo High-Resolution Marine Satellite Feed:</strong> 
                    Real-time atmospheric pressure, wind velocities, and wave disturbance monitoring across major grain choke points.
                </span>
            </div>
            <span class="font-mono text-[11px] text-sky-700 font-bold">Auto-updated</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($weatherBulletins as $wb)
                @php
                    $cardBorder = match($wb['status_level']) {
                        'ALERT' => 'border-rose-300 ring-1 ring-rose-100',
                        'ADVISORY' => 'border-amber-300 ring-1 ring-amber-100',
                        default => 'border-slate-200',
                    };
                    $badgeCls = match($wb['status_level']) {
                        'ALERT' => 'bg-rose-100 text-rose-800',
                        'ADVISORY' => 'bg-amber-100 text-amber-800',
                        default => 'bg-emerald-100 text-emerald-800',
                    };
                @endphp
                <div class="bg-white p-4 rounded-xl border {{ $cardBorder }} shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl">{{ $wb['flag'] }}</span>
                            <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded-full {{ $badgeCls }}">
                                {{ $wb['status_level'] }}
                            </span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm">{{ $wb['port'] }}</h4>
                        <span class="text-[10px] text-slate-400 block mb-2">{{ $wb['country'] }} &bull; {{ $wb['role'] }}</span>

                        <div class="grid grid-cols-2 gap-2 my-3 p-2 bg-slate-50 rounded-lg text-center">
                            <div>
                                <span class="text-[10px] text-slate-400 block">Temperature</span>
                                <span class="text-sm font-black text-slate-800 font-mono">{{ $wb['temperature'] }}&deg;C</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block">Wind Velocity</span>
                                <span class="text-sm font-black text-sky-700 font-mono">{{ $wb['windspeed'] }} km/h</span>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-600 font-medium mb-1 flex items-center space-x-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <span>{{ $wb['condition'] }}</span>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100">
                        <p class="text-[10px] text-slate-500 italic">{{ $wb['advisory'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 3: Breaking Maritime Wire & Grain News -->
    <div x-show="activeTab === 'wire'" class="space-y-4">
        @foreach($tradeNews as $news)
            @php
                $badgeStyle = match($news['badge_color']) {
                    'amber' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'sky' => 'bg-sky-100 text-sky-800 border-sky-200',
                    default => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                };
            @endphp
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:border-sky-300 transition flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="space-y-1.5 flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border {{ $badgeStyle }} font-mono">
                            {{ $news['badge'] }}
                        </span>
                        <span class="text-xs text-slate-400">&bull; {{ $news['time'] }}</span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">{{ $news['title'] }}</h3>
                    <p class="text-xs text-slate-600">{{ $news['summary'] }}</p>
                    <div class="text-[11px] font-semibold text-sky-700 pt-1 flex items-center space-x-1">
                        <span>Operational Impact:</span>
                        <span class="text-slate-800 font-normal">{{ $news['impact'] }}</span>
                    </div>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <button type="button" @click="activeTab = 'forex'" class="text-xs bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold px-3 py-1.5 rounded-lg border border-slate-200 transition">
                        View Pricing &rarr;
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- TAB 4: In-App Consignment Alerts & Operational Notifications -->
    <div x-show="activeTab === 'inapp'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
            @forelse($notifications as $n)
                @php
                    $bgColor = $n->is_read ? 'bg-white' : 'bg-sky-50/40';
                    $badgeBorder = match($n->type) {
                        'danger' => 'border-rose-500 text-rose-600',
                        'warning' => 'border-amber-500 text-amber-600',
                        'success' => 'border-emerald-500 text-emerald-600',
                        default => 'border-sky-500 text-sky-600',
                    };
                @endphp
                <div class="p-4 {{ $bgColor }} hover:bg-slate-50 transition flex items-start justify-between gap-4">
                    <div class="flex items-start space-x-3">
                        <div class="mt-1 h-2.5 w-2.5 rounded-full {{ $n->is_read ? 'bg-slate-300' : 'bg-sky-500' }}"></div>
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold text-slate-900">{{ $n->title }}</span>
                                <span class="text-[9px] uppercase font-mono font-bold px-1.5 py-0.2 border rounded {{ $badgeBorder }}">{{ $n->type }}</span>
                            </div>
                            <p class="text-xs text-slate-600">{{ $n->message }}</p>
                            <div class="text-[10px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 flex-shrink-0">
                        @if($n->link)
                            <a href="{{ $n->link }}" class="text-xs bg-sky-50 hover:bg-sky-100 text-sky-700 font-medium px-2.5 py-1 rounded transition">
                                View Consignment &rarr;
                            </a>
                        @endif
                        @if(!$n->is_read)
                            <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                @csrf
                                <button type="submit" title="Mark as Read" class="text-slate-400 hover:text-slate-600 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    No consignment notifications to display.
                </div>
            @endforelse
        </div>

        <div class="p-2">
            {{ $notifications->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function notificationsCenter() {
        return {
            activeTab: 'forex',
            calcUsd: 1358000,
            selectedCur: 'IDR',
            rates: @json($exchangeRates['rates']),

            get computedConvertedAmount() {
                const r = this.rates[this.selectedCur] || 1;
                const total = this.calcUsd * r;
                if (this.selectedCur === 'IDR') {
                    return 'Rp ' + Math.round(total).toLocaleString('id-ID');
                } else if (this.selectedCur === 'SGD') {
                    return 'S$ ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else if (this.selectedCur === 'BRL') {
                    return 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else if (this.selectedCur === 'CNY') {
                    return '¥ ' + total.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else if (this.selectedCur === 'AUD') {
                    return 'A$ ' + total.toLocaleString('en-AU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else if (this.selectedCur === 'EUR') {
                    return '€ ' + total.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else if (this.selectedCur === 'THB') {
                    return '฿ ' + total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                return total.toFixed(2);
            }
        };
    }
</script>
@endpush
@endsection
