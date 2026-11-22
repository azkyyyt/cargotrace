@extends('layouts.app')

@section('title', 'Shipment ' . $shipment->shipment_number . ' - Grain Logistics')

@section('content')
<div class="space-y-6" x-data="showShipmentManager()">

    <!-- Top Breadcrumb & Status Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('shipments.index') }}" class="hover:underline">Shipments</a>
                <span>&rsaquo;</span>
                <span class="font-mono font-bold text-slate-800">{{ $shipment->shipment_number }}</span>
                <span>&rsaquo;</span>
                <span class="text-sky-700 font-semibold">{{ $shipment->cargo_type }} Freight</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight font-mono">{{ $shipment->shipment_number }}</h1>
                
                @php
                    $badgeClass = match($shipment->current_status) {
                        'DELIVERED' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                        'IN_TRANSIT' => 'bg-sky-100 text-sky-800 border-sky-300',
                        'PICKED_UP' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                        'BOOKED' => 'bg-purple-100 text-purple-800 border-purple-300',
                        'CANCELLED' => 'bg-slate-200 text-slate-800 border-slate-300',
                        default => 'bg-amber-100 text-amber-800 border-amber-300',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-extrabold border {{ $badgeClass }}">
                    {{ $shipment->current_status }}
                </span>

                <!-- Payment Status Badge -->
                @if($shipment->payment_status === 'PAID')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 flex items-center space-x-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span>PAID ({{ str_replace('_', ' ', $shipment->payment_method ?? 'VERIFIED') }})</span>
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 flex items-center space-x-1.5 animate-pulse shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>AWAITING PAYMENT</span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Simulated Payment Gateway Trigger -->
            @if($shipment->payment_status !== 'PAID')
                <button type="button" @click="simulatePaymentModal = true" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>⚡ Settle Payment (Gateway Sandbox)</span>
                </button>
            @endif

            @if(Auth::user()->canManageShipments() && !$shipment->isTerminal())
                <button type="button" @click="addEventModal = true" class="bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-sm transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span>Record Checkpoint</span>
                </button>

                <button type="button" @click="raiseExceptionModal = true" class="bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold px-3.5 py-2 rounded-lg shadow-sm transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Flag Exception</span>
                </button>

                <a href="{{ route('shipments.edit', $shipment->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-lg transition">
                    Edit Details
                </a>
            @endif
        </div>
    </div>

    <!-- Status Lifecycle Stepper -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Grain Consignment Lifecycle Progression</span>
            @if(Auth::user()->canManageShipments() && !$shipment->isTerminal())
                <span class="text-[11px] text-sky-600 font-semibold">Click next operational milestone below to advance</span>
            @endif
        </div>

        @php
            $stages = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
            $currentIndex = array_search($shipment->current_status, $stages);
            $currentIndex = ($currentIndex === false) ? -1 : $currentIndex;
        @endphp

        <div class="grid grid-cols-5 gap-2 text-center text-xs">
            @foreach($stages as $idx => $st)
                @php
                    $isPassed = $idx <= $currentIndex;
                    $isCurrent = $idx === $currentIndex;
                    $canAdvance = ($idx === $currentIndex + 1) && Auth::user()->canManageShipments() && !$shipment->isTerminal();
                @endphp
                <div class="p-3 rounded-lg border transition {{ $isCurrent ? 'bg-sky-600 text-white font-bold border-sky-600 shadow' : ($isPassed ? 'bg-emerald-50 text-emerald-800 border-emerald-200 font-medium' : ($canAdvance ? 'bg-sky-50 text-sky-800 border-sky-300 border-dashed hover:bg-sky-100 cursor-pointer' : 'bg-slate-50 text-slate-400 border-slate-200')) }}"
                     @if($canAdvance) @click="selectedTargetStatus = '{{ $st }}'; transitionModal = true;" @endif>
                    <div class="text-[10px] uppercase tracking-wider opacity-75">Step {{ $idx + 1 }}</div>
                    <div class="mt-0.5 font-bold">{{ str_replace('_', ' ', $st) }}</div>
                    @if($canAdvance)
                        <div class="text-[9px] text-sky-700 underline mt-1">Advance &rarr;</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- ETA, Delay Evaluation & Weather Status Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- ETA & Deterministic Delay Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">ETA &amp; Delay Calculation</h3>
                @if($shipment->delay_status === 'DELAYED')
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                        DELAY DETECTED ({{ $shipment->delay_severity }})
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                        ON TIME SCHEDULE
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium block">Contractual Expected Arrival</span>
                    <span class="font-bold text-slate-800">{{ $shipment->expected_arrival->format('M d, Y H:i') }}</span>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium block">Deterministic ETA</span>
                    <span class="font-bold {{ $shipment->delay_status === 'DELAYED' ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $shipment->estimated_arrival->format('M d, Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="p-3 rounded-lg border {{ $shipment->delay_status === 'DELAYED' ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900' }} text-xs">
                <div class="font-bold flex items-center justify-between">
                    <span>Variance: +{{ $shipment->delay_duration_hours }} Hours</span>
                    <span class="text-[10px] opacity-75 font-normal">Buffer Threshold: {{ env('GROUP_DELAY_THRESHOLD_HOURS', 18) }}h</span>
                </div>
                <p class="text-[11px] mt-1">{{ $shipment->delay_reason ?: 'Vessel is navigating within nominal nautical margins along maritime trade corridors.' }}</p>
            </div>
        </div>

        <!-- Live Oceanic Weather & Delay Check Integration (Open-Meteo) -->
        <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Live Contextual Oceanic Weather (External API)</h3>
                    <p class="text-[11px] text-slate-500">Real-time Open-Meteo environmental monitoring along maritime lane</p>
                </div>
                
                <!-- Weather Impact Calculation Button -->
                <form action="{{ route('shipments.weatherCheck', $shipment->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm transition flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>⚡ Calculate Live Weather Delay Impact</span>
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <!-- Origin Weather -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                        <span>Origin: {{ $shipment->origin ? $shipment->origin->city : 'N/A' }} ({{ $shipment->origin ? $shipment->origin->country : '' }})</span>
                        <span class="text-sky-600 font-extrabold text-sm">{{ $originWeather['temperature'] ?? '--' }}&deg;C</span>
                    </div>
                    <div class="text-[11px] text-slate-600">{{ $originWeather['condition'] ?? 'Observing conditions' }}</div>
                    <div class="text-[10px] text-slate-400 mt-1">Wind speed: <strong class="text-slate-700">{{ $originWeather['windspeed'] ?? '--' }} km/h</strong> &bull; {{ $originWeather['source'] ?? 'Service' }}</div>
                </div>

                <!-- Destination Weather -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                        <span>Destination: {{ $shipment->destination ? $shipment->destination->city : 'N/A' }} ({{ $shipment->destination ? $shipment->destination->country : '' }})</span>
                        <span class="text-sky-600 font-extrabold text-sm">{{ $destinationWeather['temperature'] ?? '--' }}&deg;C</span>
                    </div>
                    <div class="text-[11px] text-slate-600">{{ $destinationWeather['condition'] ?? 'Observing conditions' }}</div>
                    <div class="text-[10px] text-slate-400 mt-1">Wind speed: <strong class="text-slate-700">{{ $destinationWeather['windspeed'] ?? '--' }} km/h</strong> &bull; {{ $destinationWeather['source'] ?? 'Service' }}</div>
                </div>
            </div>

            <div class="bg-sky-50/60 p-2.5 rounded-lg border border-sky-100 flex items-start space-x-2 text-[11px] text-slate-600">
                <span class="text-sky-600 font-bold">&bull; Deterministic Delay Formula:</span>
                <span>Wind &gt; 35 km/h &rarr; <strong>+8h delay</strong> &bull; Wind &gt; 50 km/h &rarr; <strong>+16h delay</strong> &bull; Wind &gt; 75 km/h &rarr; <strong>+28h delay</strong> (Triggers automatic WEATHER_DELAY exception).</span>
            </div>
        </div>
    </div>

    <!-- Leaflet Interactive Map View (Esri HD English Basemap + Navigable Sea Corridors) -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                    <span>Interactive Nautical Route &amp; Vessel Tracking Map</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800">HD English Labels</span>
                </h3>
                <p class="text-xs text-slate-500">Leaflet.js &bull; High-Definition Global Basemap &bull; Navigable Sea Lanes &bull; Live Vessel Position &amp; Bearing</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded font-mono font-bold">
                    {{ count($mapPoints) }} Waypoints
                </span>
                <span class="text-xs bg-sky-100 text-sky-800 px-2.5 py-1 rounded font-bold">
                    Single World (No Wrap)
                </span>
            </div>
        </div>

        <!-- Map Canvas with Ocean Background and Live AIS Satellite Telemetry HUD -->
        <div class="relative">
            <div id="shipmentMap" class="w-full h-80 sm:h-96 rounded-xl border border-slate-300 shadow-inner z-10" style="background: #a5bfdd;"></div>
            
            <!-- Real-Time Floating AIS HUD -->
            <div class="absolute bottom-3 left-3 right-3 sm:right-auto z-20 bg-slate-900/95 backdrop-blur-md text-white p-3 rounded-xl border border-white/20 shadow-xl flex flex-wrap items-center gap-3 text-xs">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="font-black text-sky-300 tracking-wider text-[11px] uppercase">LIVE AIS SATELLITE HUD</span>
                </div>
                <div class="h-4 w-px bg-slate-700 hidden sm:block"></div>
                <div>
                    <span class="text-slate-400 text-[10px] block">Steaming Speed</span>
                    <span id="aisSpeedDisplay" class="font-mono font-bold text-amber-300">14.8 kts</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] block">Course Bearing</span>
                    <span id="aisHeadingDisplay" class="font-mono font-bold text-sky-300">112.3°</span>
                </div>
                <div class="max-w-[150px] truncate">
                    <span class="text-slate-400 text-[10px] block">Navigational Fairway</span>
                    <span id="aisSectorDisplay" class="font-bold text-slate-100 block truncate">Underway Transit</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] block">Voyage Progress</span>
                    <span id="aisProgressDisplay" class="font-mono font-bold text-emerald-300">42.5%</span>
                </div>
                <div class="flex items-center space-x-1 ml-auto">
                    <button type="button" id="btnSpeed1x" class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-600 text-white hover:bg-sky-500 transition">1x Real</button>
                    <button type="button" id="btnSpeed5x" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition">5x Cruise</button>
                    <button type="button" id="btnSpeed20x" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition">20x Sim</button>
                    <button type="button" id="btnPlayPause" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition">⏸️</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Consignment Specs, Items, Timeline & Exceptions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Tracking Timeline & Manifest Items -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Chronological Tracking Timeline -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Historical Tracking Timeline</h3>
                        <p class="text-xs text-slate-500">Immutable chronological milestone ledger</p>
                    </div>
                    @if(Auth::user()->canManageShipments() && !$shipment->isTerminal())
                        <button type="button" @click="addEventModal = true" class="text-xs bg-sky-50 text-sky-700 hover:bg-sky-100 font-semibold px-2.5 py-1.5 rounded transition">
                            + Add Event
                        </button>
                    @endif
                </div>

                <div class="relative border-l-2 border-slate-200 ml-3 space-y-6">
                    @forelse($shipment->trackingEvents as $event)
                        <div class="relative pl-6">
                            @php
                                $dotColor = match($event->event_type) {
                                    'EXCEPTION_FLAG' => 'bg-rose-500 ring-rose-200',
                                    'STATUS_UPDATE' => 'bg-sky-600 ring-sky-200',
                                    default => 'bg-slate-400 ring-slate-200',
                                };
                            @endphp
                            <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full {{ $dotColor }} ring-4"></div>
                            
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <span class="font-bold text-xs text-slate-900">{{ $event->location_name }}</span>
                                <time class="text-[11px] text-slate-400 font-mono">{{ $event->event_time->format('M d, Y H:i') }}</time>
                            </div>
                            
                            <div class="mt-1 flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $event->event_type === 'EXCEPTION_FLAG' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $event->status }} &bull; {{ $event->event_type }}
                                </span>
                                @if($event->latitude && $event->longitude)
                                    <span class="text-[10px] font-mono text-slate-400">
                                        [{{ number_format($event->latitude, 4) }}, {{ number_format($event->longitude, 4) }}]
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1.5 text-xs text-slate-600">{{ $event->description }}</p>
                            <span class="text-[10px] text-slate-400 block mt-1">Logged by: {{ $event->creator ? $event->creator->name : 'System Automated' }}</span>
                        </div>
                    @empty
                        <div class="pl-6 text-xs text-slate-400 py-3">No tracking events recorded yet.</div>
                    @endforelse
                </div>
            </div>

            <!-- Bulk Grain Cargo Manifest Breakdown -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-900">Bulk Grain Manifest &amp; Quality Specifications</h3>
                    <span class="text-xs font-mono font-bold text-sky-700">{{ $shipment->items->count() }} Manifest Entries</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3">Grain Commodity</th>
                                <th class="py-2.5 px-3">Grade Specification</th>
                                <th class="py-2.5 px-3">Moisture %</th>
                                <th class="py-2.5 px-3">Quantity</th>
                                <th class="py-2.5 px-3">Weight (MT)</th>
                                <th class="py-2.5 px-3">Packaging</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($shipment->items as $item)
                                <tr>
                                    <td class="py-3 px-3 font-bold text-slate-900">
                                        {{ $item->item_description }}
                                    </td>
                                    <td class="py-3 px-3 font-medium text-slate-700">
                                        {{ $item->grade_specification ?? 'Standard Bulk Commercial' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-bold {{ ($item->moisture_content_percent ?? 12) > 14 ? 'text-amber-600' : 'text-emerald-700' }}">
                                            {{ $item->moisture_content_percent ? $item->moisture_content_percent . '%' : '12.5%' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 block">&le;14% Safe</span>
                                    </td>
                                    <td class="py-3 px-3">{{ $item->quantity }} units</td>
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                        {{ number_format($item->weight / 1000, 1) }} MT
                                        <span class="text-[10px] font-sans text-slate-400 block">({{ number_format($item->weight, 0) }} kg)</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="bg-slate-100 px-2 py-0.5 rounded text-[10px] font-mono text-slate-700">
                                            {{ str_replace('_', ' ', $item->packaging_type ?? 'BULK_VESSEL_HOLD') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-slate-400">No itemized breakdown specified.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Shipment Details & Exceptions -->
        <div class="space-y-6">

            <!-- Core Shipment Specifications Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-bold text-slate-900">Consignment Details</h3>
                    <span class="text-[11px] font-bold text-sky-700">Grain Charter</span>
                </div>
                
                <div class="space-y-2 text-slate-600">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Customer:</span>
                        <span class="font-bold text-slate-900">{{ $shipment->customer ? $shipment->customer->name : 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Company:</span>
                        <span class="font-medium text-slate-800">{{ $shipment->customer ? $shipment->customer->company : 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Carrier Line:</span>
                        <span class="font-bold text-sky-700">{{ $shipment->carrier ? $shipment->carrier->name : 'Unassigned' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Transport Mode:</span>
                        <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-800 font-bold">{{ $shipment->carrier ? $shipment->carrier->transport_mode : 'OCEAN' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Commodity Type:</span>
                        <span class="font-bold text-slate-900">{{ $shipment->cargo_type }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Bulk Weight:</span>
                        <span class="font-black text-slate-900">{{ number_format($shipment->weight / 1000, 1) }} Metric Tons</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Stowage Volume:</span>
                        <span class="font-bold text-slate-900">{{ number_format($shipment->volume, 1) }} CBM</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Origin Port:</span>
                        <span class="font-medium text-slate-800">{{ $shipment->origin ? $shipment->origin->name : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Destination Port:</span>
                        <span class="font-medium text-slate-800">{{ $shipment->destination ? $shipment->destination->name : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Expected Departure:</span>
                        <span class="text-slate-800 font-mono">{{ $shipment->expected_departure->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Actual Departure:</span>
                        <span class="text-slate-800 font-mono">{{ $shipment->actual_departure ? $shipment->actual_departure->format('Y-m-d H:i') : 'Pending departure' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Actual Arrival:</span>
                        <span class="text-slate-800 font-mono">{{ $shipment->actual_arrival ? $shipment->actual_arrival->format('Y-m-d H:i') : 'En route' }}</span>
                    </div>
                </div>

                <!-- Financial Escrow & Freight Cost Summary -->
                <div class="mt-4 pt-3 border-t border-slate-100 space-y-2 bg-slate-50 p-3 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-semibold">Freight Charge:</span>
                        <span class="text-sm font-black text-slate-900 font-mono">${{ number_format($shipment->freight_charge_usd ?? 0, 2) }} USD</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Payment Status:</span>
                        <span class="font-bold text-xs {{ $shipment->payment_status === 'PAID' ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $shipment->payment_status }}
                        </span>
                    </div>
                    @if($shipment->payment_reference)
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="text-slate-400">Payment Ref:</span>
                            <span class="font-mono text-slate-700 font-bold">{{ $shipment->payment_reference }}</span>
                        </div>
                    @endif
                    @if($shipment->payment_status !== 'PAID')
                        <button type="button" @click="simulatePaymentModal = true" class="w-full mt-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-1.5 px-3 rounded text-center transition">
                            Pay Now via Sandbox Gateway &rarr;
                        </button>
                    @endif
                </div>

                @if($shipment->notes)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[11px] text-slate-400 block mb-0.5">Handling / Stowage Notes:</span>
                        <p class="text-slate-700 italic text-[11px]">{{ $shipment->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Exception Management Panel -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-bold text-slate-900">Disruption Exceptions</h3>
                    @if(Auth::user()->canManageShipments() && !$shipment->isTerminal())
                        <button type="button" @click="raiseExceptionModal = true" class="text-xs text-amber-700 font-bold hover:underline">
                            + Flag New
                        </button>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse($shipment->exceptions as $ex)
                        <div class="p-3 rounded-lg border text-xs {{ $ex->status === 'RESOLVED' ? 'bg-slate-50 border-slate-200' : 'bg-amber-50 border-amber-200' }}">
                            <div class="flex items-center justify-between font-bold mb-1">
                                <span class="{{ $ex->status === 'RESOLVED' ? 'text-slate-700' : 'text-amber-900' }}">{{ $ex->exception_type }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-black {{ $ex->status === 'RESOLVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-200 text-amber-900' }}">
                                    {{ $ex->status }}
                                </span>
                            </div>
                            <p class="text-slate-600 text-[11px]">{{ $ex->description }}</p>
                            <div class="mt-2 text-[10px] text-slate-400">
                                Detected: {{ $ex->detected_at->format('M d, H:i') }} &bull; Severity: <strong class="text-rose-600">{{ $ex->severity }}</strong>
                            </div>

                            @if($ex->status !== 'RESOLVED' && Auth::user()->canManageShipments())
                                <form action="{{ route('exceptions.update', $ex->id) }}" method="POST" class="mt-3 pt-2 border-t border-amber-200/50 space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="resolution_notes" placeholder="Resolution notes..." required class="w-full px-2 py-1 text-xs border border-amber-300 rounded bg-white">
                                    <div class="flex justify-end space-x-1">
                                        <button type="submit" name="status" value="INVESTIGATING" class="px-2 py-1 bg-amber-200 text-amber-900 text-[10px] font-bold rounded">
                                            Investigating
                                        </button>
                                        <button type="submit" name="status" value="RESOLVED" class="px-2 py-1 bg-emerald-600 text-white text-[10px] font-bold rounded">
                                            Resolve Exception
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-slate-400">
                            No disruptions or exceptions logged. Consignment on schedule.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: Real-Time Payment Gateway Simulation -->
    <div x-cloak x-show="simulatePaymentModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-slate-200" @click.away="simulatePaymentModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>CargoTrace Payment Gateway Sandbox</span>
                    </h3>
                    <p class="text-xs text-slate-500">Real-time instant freight settlement simulation for {{ $shipment->shipment_number }}</p>
                </div>
                <button type="button" @click="simulatePaymentModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Amount Banner -->
            <div class="bg-gradient-to-r from-slate-900 to-sky-950 text-white p-4 rounded-xl mb-4">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-sky-300 block">Total Freight Escrow Amount</span>
                        <div class="text-2xl font-black font-mono tracking-tight text-white">
                            ${{ number_format($shipment->freight_charge_usd ?? 45000, 2) }} <span class="text-xs font-normal text-sky-200">USD</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">IDR Equivalent</span>
                        <div class="text-xs font-mono font-bold text-slate-200">
                            Rp {{ number_format(($shipment->freight_charge_usd ?? 45000) * 16250, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-sky-200 border-t border-sky-800/60 pt-2 flex items-center justify-between">
                    <span>Cargo: <strong>{{ $shipment->cargo_type }}</strong> ({{ number_format($shipment->weight / 1000, 1) }} MT)</span>
                    <span>Carrier: <strong>{{ $shipment->carrier ? $shipment->carrier->name : 'Ocean Carrier' }}</strong></span>
                </div>
            </div>

            <!-- Payment Method Tabs -->
            <div class="grid grid-cols-3 gap-2 mb-4 text-xs font-bold">
                <button type="button" @click="paymentMethod = 'QRIS_REALTIME'" :class="paymentMethod === 'QRIS_REALTIME' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 px-2 rounded-lg text-center transition">
                    📱 QRIS Instant
                </button>
                <button type="button" @click="paymentMethod = 'VIRTUAL_ACCOUNT'" :class="paymentMethod === 'VIRTUAL_ACCOUNT' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 px-2 rounded-lg text-center transition">
                    🏦 Virtual Account
                </button>
                <button type="button" @click="paymentMethod = 'LETTER_OF_CREDIT'" :class="paymentMethod === 'LETTER_OF_CREDIT' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 px-2 rounded-lg text-center transition">
                    📄 Letter of Credit
                </button>
            </div>

            <form action="{{ route('shipments.simulatePayment', $shipment->id) }}" method="POST">
                @csrf
                <input type="hidden" name="payment_method" :value="paymentMethod">

                <!-- TAB 1: QRIS Interface -->
                <div x-show="paymentMethod === 'QRIS_REALTIME'" class="space-y-3">
                    <div class="border-2 border-slate-200 rounded-xl p-4 text-center bg-slate-50/50">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-3">
                            <span class="text-xs font-black tracking-wider text-rose-700">QRIS STANDAR PEMBAYARAN NASIONAL</span>
                            <span class="text-[10px] font-mono text-slate-500" x-text="'Expires in: ' + qrisTimer"></span>
                        </div>
                        
                        <!-- Realistic QR Mockup -->
                        <div class="inline-block p-3 bg-white border border-slate-300 rounded-lg shadow-sm">
                            <div class="w-40 h-40 bg-slate-900 rounded flex flex-col items-center justify-center text-white p-2 relative overflow-hidden">
                                <div class="absolute inset-0 opacity-15 flex flex-wrap gap-1 p-1">
                                    <template x-for="i in 100" :key="i">
                                        <div class="w-3 h-3 bg-white"></div>
                                    </template>
                                </div>
                                <div class="relative z-10 text-center">
                                    <div class="text-2xl font-black text-amber-400">🌾</div>
                                    <div class="text-[9px] font-bold mt-1 text-slate-200">CARGOTRACE ESCROW</div>
                                    <div class="text-[8px] font-mono text-sky-300 mt-0.5">SCAN REAL-TIME</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-slate-700">
                            Merchant: <strong>PT CARGOTRACE MARITIME LOGISTICS</strong><br>
                            <span class="text-[11px] text-slate-500">Scan with BCA, Mandiri Livin, GoPay, OVO, Dana or any QRIS mobile banking</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md transition flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simulate Instant QRIS Payment Success</span>
                    </button>
                </div>

                <!-- TAB 2: Virtual Account Interface -->
                <div x-show="paymentMethod === 'VIRTUAL_ACCOUNT'" class="space-y-3">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Select Bank</label>
                        <div class="grid grid-cols-4 gap-2 text-xs">
                            <button type="button" @click="selectedBank = 'BCA'" :class="selectedBank === 'BCA' ? 'border-sky-600 bg-sky-50 text-sky-800 font-bold' : 'border-slate-200 text-slate-600'" class="py-2 border rounded-lg text-center">BCA</button>
                            <button type="button" @click="selectedBank = 'Mandiri'" :class="selectedBank === 'Mandiri' ? 'border-sky-600 bg-sky-50 text-sky-800 font-bold' : 'border-slate-200 text-slate-600'" class="py-2 border rounded-lg text-center">Mandiri</button>
                            <button type="button" @click="selectedBank = 'BNI'" :class="selectedBank === 'BNI' ? 'border-sky-600 bg-sky-50 text-sky-800 font-bold' : 'border-slate-200 text-slate-600'" class="py-2 border rounded-lg text-center">BNI</button>
                            <button type="button" @click="selectedBank = 'BRI'" :class="selectedBank === 'BRI' ? 'border-sky-600 bg-sky-50 text-sky-800 font-bold' : 'border-slate-200 text-slate-600'" class="py-2 border rounded-lg text-center">BRI</button>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block" x-text="selectedBank + ' Virtual Account Number'"></span>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-base font-extrabold text-slate-900 tracking-wider">8809 1029 4432 0981</span>
                            <span class="text-[10px] bg-sky-100 text-sky-800 px-2 py-0.5 rounded font-bold">Copy</span>
                        </div>
                        <p class="text-[11px] text-slate-500 pt-1">Automated validation via webhook. Payment will be acknowledged within seconds.</p>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md transition flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simulate VA Payment Acknowledged</span>
                    </button>
                </div>

                <!-- TAB 3: Letter of Credit (L/C) Interface -->
                <div x-show="paymentMethod === 'LETTER_OF_CREDIT'" class="space-y-3">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <span class="font-bold text-slate-800">Swift MT700 Irrevocable Documentary Credit</span>
                            <span class="text-[10px] bg-purple-100 text-purple-800 px-2 py-0.5 rounded font-mono font-bold">L/C AT SIGHT</span>
                        </div>
                        <div class="space-y-1 text-slate-600">
                            <div>Issuing Bank: <strong>Citibank N.A. Jakarta / Standard Chartered</strong></div>
                            <div>Beneficiary: <strong>CargoTrace Global Grain Logistics Carrier Line</strong></div>
                            <div>Advising Reference: <span class="font-mono font-bold text-slate-900">LC-2026-CT-990812</span></div>
                            <div class="text-[11px] text-slate-500 pt-1">Standard banking collateral for international commodity grain movements (wheat, corn, soybeans, rice).</div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md transition flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Verify &amp; Confirm Documentary Credit (L/C)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Record Checkpoint Event -->
    <div x-cloak x-show="addEventModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="addEventModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                        <span class="text-sky-600">📍</span>
                        <span>Record Tracking Checkpoint</span>
                    </h3>
                    <p class="text-xs text-slate-500">Add operational tracking event to {{ $shipment->shipment_number }}</p>
                </div>
                <button type="button" @click="addEventModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Quick Location Autocomplete (Google Maps Style) -->
            <div class="mb-3 relative">
                <label class="block font-bold text-slate-700 mb-1">🔍 Search Location / Port (Google Maps Autocomplete)</label>
                <div class="relative">
                    <input type="text" 
                           x-model="cpSearchQuery" 
                           @input.debounce.300ms="doCpSearch()" 
                           placeholder="Type to search (e.g. Belawan, Sunda Strait, Singapore Port)..." 
                           class="w-full pl-8 pr-8 py-2 border-2 border-sky-400 focus:border-sky-600 rounded-lg outline-none text-xs">
                    <div class="absolute left-2.5 top-2.5 text-slate-400">
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div x-show="cpSearching" class="absolute right-2.5 top-2.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </div>
                </div>

                <div x-show="cpResults.length > 0" 
                     @click.away="cpResults = []" 
                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-300 rounded-lg shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-100">
                    <template x-for="(item, idx) in cpResults" :key="idx">
                        <div @click="selectCpPlace(item)" class="p-2 hover:bg-sky-50 cursor-pointer text-xs flex items-start space-x-2">
                            <span class="mt-0.5">📍</span>
                            <div>
                                <div class="font-bold text-slate-900" x-text="item.name"></div>
                                <div class="text-[10px] text-slate-500" x-text="item.address"></div>
                                <div class="text-[9px] font-mono text-sky-700" x-text="'[' + item.latitude + ', ' + item.longitude + ']'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <form action="{{ route('tracking.store', $shipment->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status Classification</label>
                    <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        <option value="CREATED">CREATED</option>
                        <option value="BOOKED">BOOKED</option>
                        <option value="PICKED_UP">PICKED_UP</option>
                        <option value="IN_TRANSIT" selected>IN_TRANSIT</option>
                        <option value="DELIVERED">DELIVERED</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Location Name / Maritime Fairway</label>
                    <input type="text" name="location_name" x-model="cpLocationName" required placeholder="e.g. Sunda Strait Channel, Berth 4" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Latitude (Auto-filled)</label>
                        <input type="number" step="any" name="latitude" x-model="cpLat" placeholder="-6.1234" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Longitude (Auto-filled)</label>
                        <input type="number" step="any" name="longitude" x-model="cpLng" placeholder="106.8765" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Event Timestamp</label>
                    <input type="datetime-local" name="event_time" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Operational Description</label>
                    <textarea name="description" rows="2" required placeholder="Describe checkpoint details..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addEventModal = false" class="px-3 py-2 bg-slate-100 text-slate-700 font-semibold rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-semibold rounded-lg shadow-sm">Save Checkpoint</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Raise Exception -->
    <div x-cloak x-show="raiseExceptionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="raiseExceptionModal = false">
            <h3 class="text-base font-bold text-rose-600 mb-1">Flag Disruption Exception</h3>
            <p class="text-xs text-slate-500 mb-4">Flags a delay or cargo disruption on {{ $shipment->shipment_number }}</p>

            <form action="{{ route('exceptions.store', $shipment->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Exception Category</label>
                    <select name="exception_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        <option value="CUSTOMS_HOLD">CUSTOMS_HOLD (Customs / Phyto-sanitary inspection)</option>
                        <option value="WEATHER_DELAY">WEATHER_DELAY (Typhoon / High swell / Gale storm)</option>
                        <option value="PORT_CONGESTION">PORT_CONGESTION (Grain silo / Terminal congestion)</option>
                        <option value="DOCUMENTATION_ISSUE">DOCUMENTATION_ISSUE (Bill of Lading discrepancy)</option>
                        <option value="DAMAGED">DAMAGED (Moisture / Cargo condition alert)</option>
                        <option value="MISROUTED">MISROUTED (Carrier navigational diversion)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Severity Level</label>
                    <select name="severity" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        <option value="LOW">LOW (+2h buffer)</option>
                        <option value="MEDIUM" selected>MEDIUM (+8h delay impact)</option>
                        <option value="HIGH">HIGH (+24h critical disruption)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Incident Description &amp; Details</label>
                    <textarea name="description" rows="3" required placeholder="Specify why the exception occurred and operational instructions..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="raiseExceptionModal = false" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-semibold rounded-lg shadow-sm transition">Flag Exception</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Status Transition -->
    <div x-cloak x-show="transitionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="transitionModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-1">Transition Lifecycle Status</h3>
            <p class="text-xs text-slate-500 mb-4">Validate state change from <strong class="text-sky-700">{{ $shipment->current_status }}</strong> to <strong class="text-emerald-700" x-text="selectedTargetStatus"></strong></p>

            <form action="{{ route('shipments.transition', $shipment->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="target_status" :value="selectedTargetStatus">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason / Operational Remark</label>
                    <input type="text" name="reason" placeholder="e.g. Vessel berthed at destination terminal, discharge commencing." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="transitionModal = false" class="px-3 py-2 bg-slate-100 text-slate-700 font-semibold rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-lg shadow-sm">Confirm Transition</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function showShipmentManager() {
        return {
            addEventModal: false,
            raiseExceptionModal: false,
            transitionModal: false,
            simulatePaymentModal: false,
            paymentMethod: 'QRIS_REALTIME',
            selectedTargetStatus: '',
            selectedBank: 'BCA',
            qrisTimer: '14:59',
            cpSearchQuery: '',
            cpResults: [],
            cpSearching: false,
            cpLocationName: '',
            cpLat: '',
            cpLng: '',
            async doCpSearch() {
                const q = this.cpSearchQuery.trim();
                if (q.length < 2) {
                    this.cpResults = [];
                    return;
                }
                this.cpSearching = true;
                try {
                    const res = await fetch(`/locations/search?q=${encodeURIComponent(q)}`);
                    this.cpResults = await res.json();
                } catch(e) {
                    console.error(e);
                } finally {
                    this.cpSearching = false;
                }
            },
            selectCpPlace(item) {
                this.cpSearchQuery = item.name;
                this.cpLocationName = item.name;
                this.cpLat = Number(item.latitude).toFixed(6);
                this.cpLng = Number(item.longitude).toFixed(6);
                this.cpResults = [];
            },
            init() {
                let sec = 899;
                setInterval(() => {
                    if (sec > 0) {
                        sec--;
                        const m = Math.floor(sec / 60).toString().padStart(2, '0');
                        const s = (sec % 60).toString().padStart(2, '0');
                        this.qrisTimer = `${m}:${s}`;
                    }
                }, 1000);
            }
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const points = {!! json_encode($mapPoints) !!};
        const corridor = {!! json_encode($corridorPath ?? []) !!};
        const vessel = {!! json_encode($vesselInfo ?? null) !!};

        if (points.length === 0 && corridor.length === 0) {
            document.getElementById('shipmentMap').innerHTML = '<div class="h-full flex items-center justify-center text-slate-400 text-xs">No GPS coordinates available for this lane.</div>';
            return;
        }

        const initialLat = vessel ? vessel.lat : (points[0] ? points[0].lat : -6.1);
        const initialLng = vessel ? vessel.lng : (points[0] ? points[0].lng : 106.8);

        // Initialize Leaflet map with single-world lock to prevent duplicate continents
        const map = L.map('shipmentMap', {
            zoomControl: true,
            attributionControl: true,
            minZoom: 2,
            maxZoom: 18,
            maxBounds: [[-85, -180], [85, 180]],
            maxBoundsViscosity: 1.0,
            worldCopyJump: false
        }).setView([initialLat, initialLng], 3);

        // Global Basemaps with Universal HD English Labels (100% Free, Crisp, No API Key Required)
        const esriStreetEnglish = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            minZoom: 2,
            maxZoom: 18,
            noWrap: true,
            bounds: [[-85, -180], [85, 180]],
            attribution: 'Tiles &copy; Esri &mdash; OpenStreetMap &amp; contributors, Delorme, NAVTEQ, TomTom'
        }).addTo(map);

        const esriTopoEnglish = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            minZoom: 2,
            maxZoom: 18,
            noWrap: true,
            bounds: [[-85, -180], [85, 180]],
            attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom'
        });

        const osmStandard = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            minZoom: 2,
            maxZoom: 18,
            noWrap: true,
            bounds: [[-85, -180], [85, 180]],
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
        });

        // Layer Switcher
        L.control.layers({
            "Global Navigation HD (English Labels)": esriStreetEnglish,
            "Topographic Relief (English)": esriTopoEnglish,
            "OpenStreetMap Standard": osmStandard
        }, null, { position: 'topright' }).addTo(map);

        const bounds = [];

        // 1. Draw Navigable Sea Lane Corridor with Antimeridian Split Safety
        if (corridor && corridor.length > 1) {
            let segments = [];
            let currentSegment = [];

            corridor.forEach((pt) => {
                const lat = pt[0];
                const lng = pt[1];
                bounds.push([lat, lng]);

                if (currentSegment.length > 0) {
                    const prevLng = currentSegment[currentSegment.length - 1][1];
                    // If longitude jump is > 180 degrees, split segment to avoid drawing lines across the globe
                    if (Math.abs(lng - prevLng) > 180) {
                        segments.push(currentSegment);
                        currentSegment = [];
                    }
                }
                currentSegment.push([lat, lng]);
            });

            if (currentSegment.length > 0) {
                segments.push(currentSegment);
            }

            // Draw each seamless segment
            segments.forEach(seg => {
                // Soft glowing maritime fairway
                L.polyline(seg, {
                    color: '#0284c7',
                    weight: 6,
                    opacity: 0.3,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(map);

                // Flowing nautical dash line
                L.polyline(seg, {
                    color: '#0284c7',
                    weight: 3,
                    opacity: 0.85,
                    dashArray: '8, 12'
                }).addTo(map);
            });
        }

        // 2. Draw Checkpoints and Ports
        points.forEach((pt) => {
            bounds.push([pt.lat, pt.lng]);

            let markerColor = '#0284c7';
            if (pt.type === 'origin') markerColor = '#2563eb';
            if (pt.type === 'destination') markerColor = '#10b981';
            if (pt.type === 'exception') markerColor = '#ef4444';

            const markerHtml = `
                <div style="background-color: ${markerColor}; width: 14px; height: 14px; border: 2.5px solid #ffffff; border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.35);"></div>
            `;
            const customIcon = L.divIcon({
                html: markerHtml,
                className: 'custom-map-pin',
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });

            const marker = L.marker([pt.lat, pt.lng], { icon: customIcon }).addTo(map);

            marker.bindPopup(`
                <div style="font-family: inherit; font-size: 11px; line-height: 1.4;">
                    <strong style="color: ${markerColor}; font-size: 12px;">${pt.status}</strong><br>
                    <strong>${pt.name}</strong><br>
                    <span style="color: #64748b;">${pt.city}</span><br>
                    <span style="color: #0284c7; font-weight: bold;">${pt.time}</span>
                </div>
            `);
        });

        // 3. Draw Active Vessel Marker with Real-Time Navigation Sailing Engine
        if (vessel && vessel.lat && vessel.lng) {
            let iconSymbol = vessel.transport_mode === 'AIR' ? '✈️' : '🚢';
            let pinColor = vessel.has_exception ? '#ef4444' : (vessel.status === 'DELIVERED' ? '#10b981' : '#0284c7');
            let heading = vessel.heading || 0;

            const vesselHtml = `
                <div style="position: relative; width: 46px; height: 46px; display: flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; width: 40px; height: 40px; border-radius: 50%; background: ${pinColor}; opacity: 0.25; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    <div id="vesselCompassCard" style="position: relative; width: 36px; height: 36px; background: ${pinColor}; border: 2.5px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; transform: rotate(${heading}deg); transition: transform 0.2s linear;">
                        <span style="font-size: 16px; display: inline-block;">${iconSymbol}</span>
                    </div>
                    <div id="vesselHeadingArrow" style="position: absolute; top: 1px; width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-bottom: 8px solid ${pinColor}; transform-origin: center 23px; transform: rotate(${heading}deg); transition: transform 0.2s linear;"></div>
                    ${vessel.has_exception ? '<span style="position: absolute; top: 2px; right: 2px; width: 12px; height: 12px; background: #dc2626; border: 2px solid white; border-radius: 50%; z-index: 20;"></span>' : ''}
                </div>
            `;

            const vesselIcon = L.divIcon({
                html: vesselHtml,
                className: 'vessel-heading-pin',
                iconSize: [46, 46],
                iconAnchor: [23, 23]
            });

            // Initial position along corridor
            let corridorWaypoints = (corridor && corridor.length > 1) ? corridor : [[vessel.lat, vessel.lng]];
            
            // Calculate segment distances for precise maritime interpolation
            let totalDist = 0;
            let segmentDists = [];
            for (let i = 0; i < corridorWaypoints.length - 1; i++) {
                const p1 = corridorWaypoints[i];
                const p2 = corridorWaypoints[i + 1];
                const d = Math.hypot(p2[0] - p1[0], p2[1] - p1[1]);
                segmentDists.push(d);
                totalDist += d;
            }

            function getCorridorPosition(prog) {
                if (corridorWaypoints.length < 2 || prog <= 0) {
                    return {
                        lat: corridorWaypoints[0][0],
                        lng: corridorWaypoints[0][1],
                        heading: heading,
                        sector: corridorWaypoints[0][2] || 'Origin'
                    };
                }
                if (prog >= 1) {
                    const last = corridorWaypoints[corridorWaypoints.length - 1];
                    return {
                        lat: last[0],
                        lng: last[1],
                        heading: heading,
                        sector: last[2] || 'Destination'
                    };
                }
                const target = prog * totalDist;
                let acc = 0;
                for (let i = 0; i < segmentDists.length; i++) {
                    const segD = segmentDists[i];
                    if (acc + segD >= target || i === segmentDists.length - 1) {
                        const frac = segD > 0 ? (target - acc) / segD : 0;
                        const p1 = corridorWaypoints[i];
                        const p2 = corridorWaypoints[i + 1];
                        const lat = p1[0] + frac * (p2[0] - p1[0]);
                        const lng = p1[1] + frac * (p2[1] - p1[1]);
                        
                        // Precise Rhumb line bearing
                        const φ1 = p1[0] * Math.PI / 180;
                        const φ2 = p2[0] * Math.PI / 180;
                        const Δλ = (p2[1] - p1[1]) * Math.PI / 180;
                        const y = Math.sin(Δλ) * Math.cos(φ2);
                        const x = Math.cos(φ1) * Math.sin(φ2) - Math.sin(φ1) * Math.cos(φ2) * Math.cos(Δλ);
                        const brg = (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;

                        return {
                            lat: lat,
                            lng: lng,
                            heading: brg,
                            sector: p1[2] || p2[2] || 'Oceanic Transit Fairway'
                        };
                    }
                    acc += segD;
                }
                return { lat: vessel.lat, lng: vessel.lng, heading: heading, sector: 'Transit' };
            }

            // Determine starting progress (0.0 - 1.0)
            let currentProg = (vessel.progress > 0) ? (vessel.progress > 1 ? vessel.progress / 100 : vessel.progress) : 0.38;
            if (vessel.status === 'DELIVERED') currentProg = 1.0;
            if (vessel.status === 'CREATED' || vessel.status === 'BOOKED') currentProg = 0.0;

            const initialPos = getCorridorPosition(currentProg);
            bounds.push([initialPos.lat, initialPos.lng]);

            const vesselMarker = L.marker([initialPos.lat, initialPos.lng], { icon: vesselIcon, zIndexOffset: 1000 }).addTo(map);

            vesselMarker.bindPopup(`
                <div style="font-family: inherit; font-size: 11px; line-height: 1.5; min-width: 210px;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Live AIS Vessel Tracking</span>
                        <span style="font-size: 9px; background: #e0f2fe; color: #0284c7; padding: 1px 5px; border-radius: 4px; font-weight: bold;">Real-Time</span>
                    </div>
                    <div><strong>Carrier:</strong> ${vessel.carrier_name}</div>
                    <div><strong>Active Fairway:</strong> <span id="popupLiveSector">${initialPos.sector}</span></div>
                    <div><strong>Position:</strong> <span id="popupLiveCoord" style="font-family: monospace;">[${initialPos.lat.toFixed(4)}, ${initialPos.lng.toFixed(4)}]</span></div>
                    <div><strong>Course / Bearing:</strong> <span id="popupLiveHeading" style="font-family: monospace; font-weight: bold; color: #0284c7;">${initialPos.heading.toFixed(1)}&deg; True</span></div>
                    <div><strong>Current Status:</strong> <span style="font-weight: bold; color: ${pinColor};">${vessel.status}</span></div>
                </div>
            `).openPopup();

            // Real-Time Maritime Navigation Simulation Engine
            let isSailing = (vessel.status === 'IN_TRANSIT');
            let speedMultiplier = 1; // 1x, 5x, 20x

            const elSpeed = document.getElementById('aisSpeedDisplay');
            const elHeading = document.getElementById('aisHeadingDisplay');
            const elSector = document.getElementById('aisSectorDisplay');
            const elProgress = document.getElementById('aisProgressDisplay');

            const btn1x = document.getElementById('btnSpeed1x');
            const btn5x = document.getElementById('btnSpeed5x');
            const btn20x = document.getElementById('btnSpeed20x');
            const btnPlay = document.getElementById('btnPlayPause');

            function setMultiplier(mult, activeBtn) {
                speedMultiplier = mult;
                [btn1x, btn5x, btn20x].forEach(b => {
                    if (b) {
                        b.classList.remove('bg-sky-600', 'text-white');
                        b.classList.add('bg-slate-800', 'text-slate-300');
                    }
                });
                if (activeBtn) {
                    activeBtn.classList.remove('bg-slate-800', 'text-slate-300');
                    activeBtn.classList.add('bg-sky-600', 'text-white');
                }
            }

            if (btn1x) btn1x.addEventListener('click', () => setMultiplier(1, btn1x));
            if (btn5x) btn5x.addEventListener('click', () => setMultiplier(5, btn5x));
            if (btn20x) btn20x.addEventListener('click', () => setMultiplier(20, btn20x));
            if (btnPlay) {
                btnPlay.addEventListener('click', () => {
                    isSailing = !isSailing;
                    btnPlay.textContent = isSailing ? '⏸️' : '▶️';
                });
            }

            let lastTime = performance.now();

            function sailLoop(currentTime) {
                if (isSailing && totalDist > 0) {
                    const dt = (currentTime - lastTime) / 1000;
                    lastTime = currentTime;

                    // Standard cruise progression: 1 complete oceanic crossing cycle in ~200s at 1x
                    const advanceRate = (1 / 200) * speedMultiplier;
                    currentProg += advanceRate * dt;

                    // Loop back to start for smooth continuous observation
                    if (currentProg > 1.0) {
                        currentProg = 0.05;
                    }

                    const curPos = getCorridorPosition(currentProg);
                    vesselMarker.setLatLng([curPos.lat, curPos.lng]);

                    // Dynamically orient vessel icon compass card & bearing arrow
                    const compassCard = document.getElementById('vesselCompassCard');
                    const headingArrow = document.getElementById('vesselHeadingArrow');
                    if (compassCard) compassCard.style.transform = `rotate(${curPos.heading}deg)`;
                    if (headingArrow) headingArrow.style.transform = `rotate(${curPos.heading}deg)`;

                    // Simulated realistic engine speed (14.2 - 15.4 knots)
                    const liveKts = (14.6 + Math.sin(currentTime / 1800) * 0.7).toFixed(1);

                    // Update Floating AIS HUD
                    if (elSpeed) elSpeed.textContent = liveKts + ' kts';
                    if (elHeading) elHeading.textContent = curPos.heading.toFixed(1) + '°';
                    if (elSector) elSector.textContent = curPos.sector;
                    if (elProgress) elProgress.textContent = (currentProg * 100).toFixed(1) + '%';

                    // Update popup if open
                    const popCoord = document.getElementById('popupLiveCoord');
                    const popHead = document.getElementById('popupLiveHeading');
                    const popSec = document.getElementById('popupLiveSector');
                    if (popCoord) popCoord.textContent = `[${curPos.lat.toFixed(4)}, ${curPos.lng.toFixed(4)}]`;
                    if (popHead) popHead.textContent = `${curPos.heading.toFixed(1)}° True`;
                    if (popSec) popSec.textContent = curPos.sector;
                } else {
                    lastTime = currentTime;
                }
                requestAnimationFrame(sailLoop);
            }

            requestAnimationFrame(sailLoop);
        }

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 6 });
        }
    });
</script>
@endpush
