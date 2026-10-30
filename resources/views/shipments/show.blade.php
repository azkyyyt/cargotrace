@extends('layouts.app')

@section('title', 'Shipment ' . $shipment->shipment_number)

@section('content')
<div class="space-y-6" x-data="{ addEventModal: false, raiseExceptionModal: false, transitionModal: false, selectedTargetStatus: '' }">

    <!-- Top Breadcrumb & Status Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('shipments.index') }}" class="hover:underline">Shipments</a>
                <span>&rsaquo;</span>
                <span class="font-mono font-bold text-slate-800">{{ $shipment->shipment_number }}</span>
            </div>
            <div class="flex items-center space-x-3">
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
            </div>
        </div>

        <!-- Operator / Admin Action Buttons -->
        @if(Auth::user()->canManageShipments() && !$shipment->isTerminal())
            <div class="flex flex-wrap items-center gap-2">
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
            </div>
        @endif
    </div>

    <!-- Status Lifecycle Stepper (Slide 6 Requirement) -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Shipment Lifecycle Progression</span>
            @if(Auth::user()->canManageShipments() && !$shipment->isTerminal())
                <span class="text-[11px] text-sky-600 font-semibold">Click next step below to advance</span>
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

    <!-- ETA, Delay Evaluation & Weather Status Row (Slide 8 & 9) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- ETA & Delay Analysis Card (Slide 8 Requirement) -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">ETA &amp; Delay Calculation</h3>
                @if($shipment->delay_status === 'DELAYED')
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                        DELAY DETECTED ({{ $shipment->delay_severity }})
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                        ON TIME
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium block">Expected Arrival</span>
                    <span class="font-bold text-slate-800">{{ $shipment->expected_arrival->format('M d, Y H:i') }}</span>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium block">Calculated ETA</span>
                    <span class="font-bold {{ $shipment->delay_status === 'DELAYED' ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $shipment->estimated_arrival->format('M d, Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="p-3 rounded-lg border {{ $shipment->delay_status === 'DELAYED' ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900' }} text-xs">
                <div class="font-bold flex items-center justify-between">
                    <span>Variance: {{ $shipment->delay_duration_hours }} Hours</span>
                    <span class="text-[10px] opacity-75 font-normal">Threshold: {{ env('GROUP_DELAY_THRESHOLD_HOURS', 18) }}h</span>
                </div>
                <p class="text-[11px] mt-1">{{ $shipment->delay_reason ?: 'Consignment is navigating on schedule within normal operational buffers.' }}</p>
            </div>
        </div>

        <!-- External Live Weather Integration (Slide 9 Requirement) -->
        <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Live Contextual Weather Integration (External API)</h3>
                <span class="text-[10px] text-slate-400">Open-Meteo API</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <!-- Origin Weather -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                        <span>Origin: {{ $shipment->origin ? $shipment->origin->city : 'N/A' }}</span>
                        <span class="text-sky-600 font-extrabold text-sm">{{ $originWeather['temperature'] ?? '--' }}&deg;C</span>
                    </div>
                    <div class="text-[11px] text-slate-600">{{ $originWeather['condition'] ?? 'Observing conditions' }}</div>
                    <div class="text-[10px] text-slate-400 mt-1">Wind speed: {{ $originWeather['windspeed'] ?? '--' }} km/h &bull; {{ $originWeather['source'] ?? 'Service' }}</div>
                </div>

                <!-- Destination Weather -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                        <span>Destination: {{ $shipment->destination ? $shipment->destination->city : 'N/A' }}</span>
                        <span class="text-sky-600 font-extrabold text-sm">{{ $destinationWeather['temperature'] ?? '--' }}&deg;C</span>
                    </div>
                    <div class="text-[11px] text-slate-600">{{ $destinationWeather['condition'] ?? 'Observing conditions' }}</div>
                    <div class="text-[10px] text-slate-400 mt-1">Wind speed: {{ $destinationWeather['windspeed'] ?? '--' }} km/h &bull; {{ $destinationWeather['source'] ?? 'Service' }}</div>
                </div>
            </div>

            <p class="text-[10px] text-slate-400 italic">
                * Note (Slide 9 compliance): Weather conditions provide operational environmental visibility and do not replace deterministic delay calculations.
            </p>
        </div>
    </div>

    <!-- Leaflet Interactive Map View (Slide 9 Requirement) -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Interactive Route &amp; Checkpoint Map</h3>
                <p class="text-xs text-slate-500">Leaflet.js + OpenStreetMap &bull; Origin, Intermediate Checkpoints &amp; Destination Waypoints</p>
            </div>
            <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded font-mono">
                {{ count($mapPoints) }} Map Waypoints
            </span>
        </div>

        <div id="shipmentMap" class="w-full h-80 sm:h-96 rounded-lg border border-slate-200 shadow-inner z-10"></div>
    </div>

    <!-- Consignment Specs, Items, Timeline & Exceptions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Tracking Timeline & Cargo Items -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Chronological Tracking Timeline (Slide 7 Requirement) -->
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

                <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                    @forelse($shipment->trackingEvents as $evt)
                        @php
                            $isEx = $evt->event_type === 'EXCEPTION_FLAG';
                            $isRes = $evt->event_type === 'EXCEPTION_RESOLVED';
                            $dotColor = $isEx ? 'bg-rose-500 ring-rose-200' : ($isRes ? 'bg-emerald-500 ring-emerald-200' : 'bg-sky-500 ring-sky-200');
                        @endphp
                        <div class="relative group text-xs">
                            <!-- Dot -->
                            <div class="absolute -left-6 top-1 w-3 h-3 rounded-full {{ $dotColor }} ring-4"></div>
                            
                            <div class="bg-slate-50 p-3 rounded-lg border {{ $isEx ? 'border-rose-200 bg-rose-50/50' : 'border-slate-100' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-[11px] text-slate-500 mb-1">
                                    <span class="font-bold text-slate-900">{{ $evt->location_name }}</span>
                                    <span>{{ $evt->event_time->format('M d, Y - H:i') }}</span>
                                </div>
                                <p class="text-slate-700 font-medium">{{ $evt->description }}</p>
                                <div class="mt-2 flex items-center justify-between text-[10px] text-slate-400">
                                    <span class="bg-slate-200 px-1.5 py-0.5 rounded font-mono">{{ $evt->status }}</span>
                                    <span>Recorded by: {{ $evt->creator ? $evt->creator->name : 'System Dispatch' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No events recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Cargo Items Breakdown (Slide 11 Requirement) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Manifest &amp; Cargo Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="py-2 px-3">Item Description</th>
                                <th class="py-2 px-3">Package</th>
                                <th class="py-2 px-3">Quantity</th>
                                <th class="py-2 px-3">Weight</th>
                                <th class="py-2 px-3">Volume</th>
                                <th class="py-2 px-3">HS Code</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($shipment->items as $item)
                                <tr>
                                    <td class="py-2.5 px-3 font-medium text-slate-900">{{ $item->item_description }}</td>
                                    <td class="py-2.5 px-3">{{ $item->package_type }}</td>
                                    <td class="py-2.5 px-3">{{ $item->quantity }} units</td>
                                    <td class="py-2.5 px-3">{{ number_format($item->weight, 1) }} kg</td>
                                    <td class="py-2.5 px-3">{{ $item->volume }} CBM</td>
                                    <td class="py-2.5 px-3 font-mono text-slate-500">{{ $item->hs_code ?: '-' }}</td>
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

            <!-- Core Shipment Specifications -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3 text-xs">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Consignment Details</h3>
                
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
                        <span class="text-slate-400">Carrier:</span>
                        <span class="font-bold text-sky-700">{{ $shipment->carrier ? $shipment->carrier->name : 'Unassigned' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Transport Mode:</span>
                        <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-800">{{ $shipment->carrier ? $shipment->carrier->transport_mode : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Cargo Type:</span>
                        <span class="font-medium text-slate-800">{{ $shipment->cargo_type }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Weight:</span>
                        <span class="font-bold text-slate-900">{{ number_format($shipment->weight, 0) }} kg</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Volume:</span>
                        <span class="font-bold text-slate-900">{{ $shipment->volume }} CBM</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Origin Port/Hub:</span>
                        <span class="font-medium text-slate-800">{{ $shipment->origin ? $shipment->origin->name : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Destination Port/Hub:</span>
                        <span class="font-medium text-slate-800">{{ $shipment->destination ? $shipment->destination->name : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Expected Departure:</span>
                        <span class="text-slate-800">{{ $shipment->expected_departure->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Actual Departure:</span>
                        <span class="text-slate-800">{{ $shipment->actual_departure ? $shipment->actual_departure->format('Y-m-d H:i') : 'Pending' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Actual Arrival:</span>
                        <span class="text-slate-800">{{ $shipment->actual_arrival ? $shipment->actual_arrival->format('Y-m-d H:i') : 'In progress' }}</span>
                    </div>
                </div>

                @if($shipment->notes)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[11px] text-slate-400 block mb-0.5">Handling Notes:</span>
                        <p class="text-slate-700 italic text-[11px]">{{ $shipment->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Exception Management Panel (Slide 8 Requirement) -->
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

                            @if($ex->status === 'RESOLVED' && $ex->resolution_notes)
                                <div class="mt-2 text-[11px] text-emerald-700 bg-emerald-50 p-2 rounded border border-emerald-200">
                                    <strong>Resolution:</strong> {{ $ex->resolution_notes }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">No active exceptions logged for this consignment.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 1: Add Tracking Event Checkpoint -->
    <div x-cloak x-show="addEventModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="addEventModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-1">Record Tracking Checkpoint</h3>
            <p class="text-xs text-slate-500 mb-4">Adds an immutable event to {{ $shipment->shipment_number }}</p>

            <form action="{{ route('tracking.store', $shipment->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status / Phase</label>
                    <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $st)
                            <option value="{{ $st }}" {{ $shipment->current_status === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Select Facility or Hub</label>
                    <select name="location_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        <option value="">-- Choose Registered Location --</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->city }}, {{ $loc->country }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Checkpoint Location Name (or Custom)</label>
                    <input type="text" name="location_name" placeholder="e.g. Malacca Strait Waypoint, PSA Terminal 3" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Latitude</label>
                        <input type="number" step="any" name="latitude" placeholder="e.g. 1.2902" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Longitude</label>
                        <input type="number" step="any" name="longitude" placeholder="e.g. 103.8519" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Event Description</label>
                    <textarea name="description" rows="3" required placeholder="Describe status update, vessel movement, or cargo inspection..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addEventModal = false" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-semibold rounded-lg shadow-sm transition">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Raise Exception -->
    <div x-cloak x-show="raiseExceptionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="raiseExceptionModal = false">
            <h3 class="text-base font-bold text-rose-600 mb-1">Flag Disruption Exception</h3>
            <p class="text-xs text-slate-500 mb-4">Flags a delay or cargo disruption on {{ $shipment->shipment_number }}</p>

            <form action="{{ route('exceptions.store', $shipment->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Exception Category (Slide 8)</label>
                    <select name="exception_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        <option value="CUSTOMS_HOLD">CUSTOMS_HOLD (Customs / Inspection)</option>
                        <option value="WEATHER_DELAY">WEATHER_DELAY (Typhoon / Gale / Sea)</option>
                        <option value="PORT_CONGESTION">PORT_CONGESTION (Berth / Terminal Congestion)</option>
                        <option value="DOCUMENTATION_ISSUE">DOCUMENTATION_ISSUE (Paperwork discrepancy)</option>
                        <option value="DAMAGED">DAMAGED (Cargo condition alert)</option>
                        <option value="MISROUTED">MISROUTED (Carrier routing error)</option>
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

    <!-- Modal 3: Status Transition -->
    <div x-cloak x-show="transitionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="transitionModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-1">Transition Lifecycle Status</h3>
            <p class="text-xs text-slate-500 mb-4">Validate state change from <strong class="text-sky-700">{{ $shipment->current_status }}</strong> to <strong class="text-emerald-700" x-text="selectedTargetStatus"></strong></p>

            <form action="{{ route('shipments.transition', $shipment->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="target_status" :value="selectedTargetStatus">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason / Operational Remark</label>
                    <input type="text" name="reason" placeholder="e.g. Vessel berthed at destination terminal, customs passed." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
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
    document.addEventListener('DOMContentLoaded', function () {
        const points = {!! json_encode($mapPoints) !!};

        if (points.length === 0) {
            document.getElementById('shipmentMap').innerHTML = '<div class="h-full flex items-center justify-center text-slate-400 text-xs">No GPS coordinates available for this lane.</div>';
            return;
        }

        // Initialize Leaflet map centered at first point
        const map = L.map('shipmentMap').setView([points[0].lat, points[0].lng], 4);

        // Standard OpenStreetMap tiles with attribution (Slide 9 compliance)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const latLngs = [];

        points.forEach((pt, index) => {
            latLngs.push([pt.lat, pt.lng]);

            // Custom color icon
            let markerColor = '#0284c7'; // Blue
            if (pt.type === 'origin') markerColor = '#2563eb';
            if (pt.type === 'destination') markerColor = '#10b981'; // Green
            if (pt.type === 'exception') markerColor = '#ef4444'; // Red

            const markerHtml = `
                <div style="background-color: ${markerColor}; width: 14px; height: 14px; border: 2px solid #ffffff; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>
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
                    <strong style="color: ${markerColor};">${pt.status}</strong><br>
                    <strong>${pt.name}</strong><br>
                    <span style="color: #64748b;">${pt.city}</span><br>
                    <span style="color: #0284c7; font-weight: bold;">${pt.time}</span>
                </div>
            `);

            // Open popup for latest point or destination
            if (index === points.length - 1) {
                marker.openPopup();
            }
        });

        // Draw connecting polyline path
        if (latLngs.length > 1) {
            L.polyline(latLngs, {
                color: '#0284c7',
                weight: 3,
                opacity: 0.8,
                dashArray: '6, 6'
            }).addTo(map);

            map.fitBounds(latLngs, { padding: [40, 40] });
        }
    });
</script>
@endpush
