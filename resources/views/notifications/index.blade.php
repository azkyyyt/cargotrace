@extends('layouts.app')

@section('title', 'Global Intelligence & Notification Center')

@push('styles')
<style>
    /* Weather Atmospheric Visual Animations */
    @keyframes lightningFlash {
        0%, 82%, 100% {
            opacity: 0.15;
            filter: drop-shadow(0 0 0px #facc15);
            transform: scale(0.96);
        }
        84%, 90% {
            opacity: 1;
            filter: drop-shadow(0 0 14px #fde047) drop-shadow(0 0 28px #eab308);
            transform: scale(1.08);
        }
        86% {
            opacity: 0.35;
        }
    }
    .anim-lightning-bolt {
        animation: lightningFlash 2.4s infinite ease-in-out;
        transform-origin: center top;
    }

    @keyframes cloudDriftGentle {
        0%, 100% { transform: translateX(0px); }
        50% { transform: translateX(7px); }
    }
    .anim-cloud-drift {
        animation: cloudDriftGentle 4.5s infinite ease-in-out;
    }

    @keyframes rainDropFall {
        0% { transform: translateY(-4px) scaleY(0.7); opacity: 0; }
        40% { opacity: 1; }
        100% { transform: translateY(14px) scaleY(1.1); opacity: 0; }
    }
    .anim-raindrop-1 { animation: rainDropFall 1.1s infinite linear; }
    .anim-raindrop-2 { animation: rainDropFall 1.3s infinite 0.35s linear; }
    .anim-raindrop-3 { animation: rainDropFall 1.0s infinite 0.65s linear; }

    @keyframes sunGlowPulse {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 6px #f59e0b); }
        50% { transform: scale(1.06); filter: drop-shadow(0 0 16px #fbbf24); }
    }
    .anim-sun-pulse {
        animation: sunGlowPulse 3.2s infinite ease-in-out;
        transform-origin: center;
    }
</style>
@endpush

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
        <button type="button" @click="activeTab = 'weather'" :class="activeTab === 'weather' ? 'border-sky-600 text-sky-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-4 text-xs border-b-2 flex items-center space-x-2 whitespace-nowrap transition">
            <span>🌊 Worldwide Port Weather Bulletins</span>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.5 rounded">{{ count($weatherBulletins) }} Ports</span>
        </button>
        <button type="button" @click="activeTab = 'forex'" :class="activeTab === 'forex' ? 'border-sky-600 text-sky-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-4 text-xs border-b-2 flex items-center space-x-2 whitespace-nowrap transition">
            <span>💱 Live Forex &amp; Grain Valuations</span>
            <span class="bg-sky-100 text-sky-800 text-[10px] font-bold px-1.5 py-0.5 rounded">USD Base</span>
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

    <!-- TAB 1: Worldwide Marine Weather Bulletins with Illustrated Skies -->
    <div x-show="activeTab === 'weather'" class="space-y-6">
        <!-- Weather Status Info & Interactive Filter Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-900 text-white p-4 rounded-2xl shadow-md border border-slate-800">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🛰️</span>
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                        <span>Open-Meteo High-Resolution Marine Satellite Feed</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Live Radar</span>
                    </h3>
                    <p class="text-xs text-slate-300">Pantauan visual atmosferik real-time, kondisi awan mendung, hujan maritim, hingga awan petir di 8 pelabuhan pangan dunia.</p>
                </div>
            </div>

            <!-- Visual Weather Category Filter Buttons -->
            <div class="flex items-center space-x-1.5 overflow-x-auto py-1">
                <button type="button" @click="weatherFilter = 'all'" :class="weatherFilter === 'all' ? 'bg-sky-500 text-white font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 rounded-lg text-[11px] transition whitespace-nowrap">
                    Semua ({{ count($weatherBulletins) }})
                </button>
                <button type="button" @click="weatherFilter = 'thunderstorm'" :class="weatherFilter === 'thunderstorm' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 rounded-lg text-[11px] transition whitespace-nowrap flex items-center space-x-1">
                    <span>⚡ Awan Petir</span>
                </button>
                <button type="button" @click="weatherFilter = 'cloudy'" :class="weatherFilter === 'cloudy' ? 'bg-slate-300 text-slate-900 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 rounded-lg text-[11px] transition whitespace-nowrap flex items-center space-x-1">
                    <span>☁️ Mendung</span>
                </button>
                <button type="button" @click="weatherFilter = 'rain'" :class="weatherFilter === 'rain' ? 'bg-sky-400 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 rounded-lg text-[11px] transition whitespace-nowrap flex items-center space-x-1">
                    <span>🌧️ Hujan</span>
                </button>
                <button type="button" @click="weatherFilter = 'partly_cloudy'" :class="weatherFilter === 'partly_cloudy' ? 'bg-blue-400 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 rounded-lg text-[11px] transition whitespace-nowrap flex items-center space-x-1">
                    <span>⛅ Cerah</span>
                </button>
            </div>
        </div>

        <!-- Port Weather Cards Grid with Rich Atmospheric Visuals -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($weatherBulletins as $wb)
                @php
                    $cat = $wb['category'] ?? 'clear';
                    
                    // Card background theme and sky illustration colors
                    $theme = match($cat) {
                        'thunderstorm' => [
                            'banner_bg' => 'bg-slate-950',
                            'banner_border' => 'border-indigo-500/40',
                            'badge' => 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
                            'pill_text' => 'Storm & Lightning Alert',
                            'temp_color' => 'text-amber-300',
                            'advisory_border' => 'border-l-4 border-rose-500 bg-rose-50/50',
                        ],
                        'cloudy' => [
                            'banner_bg' => 'bg-slate-800',
                            'banner_border' => 'border-slate-600/50',
                            'badge' => 'bg-slate-600/30 text-slate-200 border border-slate-500/30',
                            'pill_text' => 'Overcast Conditions',
                            'temp_color' => 'text-sky-300',
                            'advisory_border' => 'border-l-4 border-slate-400 bg-slate-50/50',
                        ],
                        'rain' => [
                            'banner_bg' => 'bg-slate-900',
                            'banner_border' => 'border-sky-500/40',
                            'badge' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                            'pill_text' => 'Maritime Rainfall',
                            'temp_color' => 'text-cyan-300',
                            'advisory_border' => 'border-l-4 border-amber-500 bg-amber-50/50',
                        ],
                        'partly_cloudy' => [
                            'banner_bg' => 'bg-sky-900',
                            'banner_border' => 'border-sky-400/30',
                            'badge' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                            'pill_text' => 'Partly Cloudy',
                            'temp_color' => 'text-amber-200',
                            'advisory_border' => 'border-l-4 border-emerald-500 bg-emerald-50/50',
                        ],
                        default => [
                            'banner_bg' => 'bg-sky-800',
                            'banner_border' => 'border-sky-300/40',
                            'badge' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                            'pill_text' => 'Clear Maritime Sky',
                            'temp_color' => 'text-amber-300',
                            'advisory_border' => 'border-l-4 border-emerald-500 bg-emerald-50/50',
                        ],
                    };
                @endphp

                <div x-show="weatherFilter === 'all' || weatherFilter === '{{ $cat }}'" class="bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col justify-between overflow-hidden group">
                    <!-- Top Visual Atmospheric Banner -->
                    <div class="relative p-5 {{ $theme['banner_bg'] }} border-b {{ $theme['banner_border'] }} text-white overflow-hidden min-h-[145px] flex flex-col justify-between">
                        
                        <!-- Atmospheric Weather SVG Illustration -->
                        <div class="absolute right-2 top-2 w-32 h-32 pointer-events-none opacity-90">
                            @if($cat === 'thunderstorm')
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <path d="M25 60 a18 18 0 0 1 12 -30 a22 22 0 0 1 40 4 a16 16 0 0 1 12 26 z" fill="#1e293b" />
                                    <polygon points="50,48 38,68 47,68 41,88 64,62 53,62 58,48" fill="#facc15" stroke="#fef08a" stroke-width="1.5" />
                                    <line x1="28" y1="68" x2="24" y2="78" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" />
                                    <line x1="68" y1="68" x2="64" y2="78" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" />
                                    <line x1="78" y1="65" x2="74" y2="75" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" />
                                </svg>
                            @elseif($cat === 'cloudy')
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <path d="M40 50 a16 16 0 0 1 10 -26 a20 20 0 0 1 34 4 a14 14 0 0 1 10 22 z" fill="#475569" opacity="0.65" />
                                    <path d="M20 66 a18 18 0 0 1 14 -30 a24 24 0 0 1 42 4 a18 18 0 0 1 14 26 z" fill="#64748b" />
                                    <path d="M15 75 q 15 -3 30 0" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round" opacity="0.5" />
                                    <path d="M50 78 q 20 -3 35 0" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round" opacity="0.4" />
                                </svg>
                            @elseif($cat === 'rain')
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <path d="M22 55 a16 16 0 0 1 12 -28 a22 22 0 0 1 38 4 a15 15 0 0 1 12 24 z" fill="#0369a1" />
                                    <line x1="30" y1="62" x2="25" y2="76" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" />
                                    <line x1="45" y1="62" x2="40" y2="76" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" />
                                    <line x1="60" y1="62" x2="55" y2="76" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" />
                                    <line x1="75" y1="62" x2="70" y2="76" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" />
                                </svg>
                            @elseif($cat === 'partly_cloudy')
                                <!-- Cerah Berawan (Sun Behind Clouds) -->
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <!-- Sun -->
                                    <circle cx="62" cy="36" r="18" fill="#f59e0b" filter="drop-shadow(0 0 10px #fbbf24)" class="anim-sun-pulse" />
                                    <!-- Sun Rays -->
                                    <g stroke="#fbbf24" stroke-width="2" stroke-linecap="round">
                                        <line x1="62" y1="12" x2="62" y2="6" />
                                        <line x1="79" y1="19" x2="84" y2="14" />
                                        <line x1="86" y1="36" x2="92" y2="36" />
                                    </g>
                                    <!-- Drifting Cumulus Cloud in front -->
                                    <path d="M22 66 a16 16 0 0 1 12 -28 a22 22 0 0 1 38 4 a16 16 0 0 1 14 24 z" fill="#f8fafc" filter="drop-shadow(0 4px 8px rgba(0,0,0,0.3))" class="anim-cloud-drift" />
                                </svg>
                            @else
                                <!-- Cerah Optimal Lautan (Radiant Sun) -->
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <circle cx="50" cy="50" r="22" fill="#f59e0b" filter="drop-shadow(0 0 16px #fbbf24)" class="anim-sun-pulse" />
                                    <g stroke="#fde047" stroke-width="3" stroke-linecap="round">
                                        <line x1="50" y1="15" x2="50" y2="5" />
                                        <line x1="50" y1="85" x2="50" y2="95" />
                                        <line x1="15" y1="50" x2="5" y2="50" />
                                        <line x1="85" y1="50" x2="95" y2="50" />
                                        <line x1="25" y1="25" x2="18" y2="18" />
                                        <line x1="75" y1="75" x2="82" y2="82" />
                                        <line x1="75" y1="25" x2="82" y2="18" />
                                        <line x1="25" y1="75" x2="18" y2="82" />
                                    </g>
                                </svg>
                            @endif
                        </div>

                        <!-- Top Row: Port Flag Pill & Status -->
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-bold text-white shadow-sm">
                                <span>{{ $wb['flag'] }}</span>
                                <span class="font-mono text-[10px] uppercase tracking-wider">{{ $wb['country'] }}</span>
                            </span>

                            <span class="text-[9px] uppercase font-mono font-black px-2 py-0.5 rounded-full {{ $theme['badge'] }}">
                                {{ $wb['status_level'] }}
                            </span>
                        </div>

                        <!-- Center: Giant Temperature & Visual Condition -->
                        <div class="relative z-10 mt-3">
                            <div class="text-3xl font-black font-mono tracking-tight {{ $theme['temp_color'] }}">
                                {{ $wb['temperature'] }}&deg;<span class="text-lg font-bold text-white/80">C</span>
                            </div>
                            <div class="inline-block mt-1 px-2.5 py-0.5 rounded-md bg-black/25 backdrop-blur-sm text-[11px] font-bold text-white tracking-wide border border-white/10">
                                {{ $theme['pill_text'] }}
                            </div>
                        </div>
                    </div>

                    <!-- Card Body: Port Name, Metrics & Advisory -->
                    <div class="p-4 space-y-3 flex-1 flex flex-col justify-between bg-white">
                        <div>
                            <h4 class="font-black text-slate-900 text-sm tracking-tight flex items-center space-x-1.5">
                                <span>⚓</span>
                                <span>{{ $wb['port'] }}</span>
                            </h4>
                            <span class="text-[10px] text-slate-500 block mt-0.5 font-medium leading-tight">
                                {{ $wb['role'] }}
                            </span>

                            <!-- Atmospheric Parameters Grid -->
                            <div class="grid grid-cols-2 gap-2 mt-3 p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-center">
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">💨 Wind Velocity</span>
                                    <span class="text-xs font-black text-slate-800 font-mono">{{ $wb['windspeed'] }} km/h</span>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">🌊 Wave Swell</span>
                                    <span class="text-xs font-black text-sky-700 font-mono">{{ $wb['wave_height'] ?? '1.2' }}m Swell</span>
                                </div>
                            </div>
                        </div>

                        <!-- Nautical Dispatch Advisory -->
                        <div class="p-2.5 rounded-lg {{ $theme['advisory_border'] }} text-[11px] text-slate-600 leading-snug">
                            <span class="font-bold text-slate-800 block text-[10px] uppercase tracking-wider mb-0.5">Maritime Advisory:</span>
                            <p class="italic text-[11px]">{{ $wb['advisory'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 2: Live Forex & Grain Cargo Settlement Valuator -->
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
        <div class="bg-slate-900 p-6 rounded-lg text-white shadow-sm border border-slate-800">
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
            activeTab: 'weather', // Defaults to Weather Bulletins so the user immediately sees the rich visual skies!
            weatherFilter: 'all',
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
