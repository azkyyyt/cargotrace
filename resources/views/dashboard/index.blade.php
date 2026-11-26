@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Welcome -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Operations Dashboard</h1>
            <p class="text-xs text-slate-500 mt-1">
                Real-time shipment visibility &bull; Scenario: <span class="font-bold text-slate-700">{{ $activeScenario }}</span> &bull; Threshold: <span class="font-bold text-sky-700">{{ $groupThreshold }}h</span>
            </p>
        </div>
        @if(Auth::user()->canManageShipments())
            <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Create Shipment</span>
            </a>
        @endif
    </div>

    <!-- Active Exceptions Critical Banner (If Any) -->
    @if($openExceptionsCount > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-amber-500 text-white rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">{{ $openExceptionsCount }} Active Disruption Exception(s) Detected</h3>
                    <p class="text-xs text-amber-700 mt-0.5">Consignments are experiencing customs hold, documentation or weather delays.</p>
                </div>
            </div>
            <a href="{{ route('exceptions.index') }}" class="text-xs font-bold text-amber-800 hover:text-amber-900 bg-amber-100 px-3 py-1.5 rounded-lg transition">
                View Exceptions &rarr;
            </a>
        </div>
    @endif

    <!-- Metric Cards Grid (Dynamic Database Driven) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Shipments -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Shipments</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalShipments }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Across all global corridors</p>
            </div>
            <div class="p-2.5 bg-slate-100 text-slate-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>

        <!-- In Transit -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-sky-600">In Transit</p>
                <p class="text-2xl font-black text-sky-700 mt-1">{{ $inTransitCount }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Active on sea / air corridors</p>
            </div>
            <div class="p-2.5 bg-sky-50 text-sky-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
        </div>

        <!-- Delivered -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Delivered</p>
                <p class="text-2xl font-black text-emerald-700 mt-1">{{ $deliveredCount }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Safely completed &amp; signed</p>
            </div>
            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Delayed -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Delayed</p>
                <p class="text-2xl font-black text-rose-700 mt-1">{{ $delayedCount }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Avg duration: {{ $avgDelayDuration }}h</p>
            </div>
            <div class="p-2.5 bg-rose-50 text-rose-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Open Exceptions -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between col-span-2 lg:col-span-1">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Open Exceptions</p>
                <p class="text-2xl font-black text-amber-700 mt-1">{{ $openExceptionsCount }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ $resolvedExceptionsCount }} resolved to date</p>
            </div>
            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>

    <!-- Operational KPI Indicators Row (Slide 10 & 23) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- On-time Delivery Rate -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                <span>On-Time Delivery Rate (KPI)</span>
                <span class="text-emerald-600 font-bold">{{ $onTimeDeliveryRate }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: {{ $onTimeDeliveryRate }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Target benchmark: &ge; 90.0%</p>
        </div>

        <!-- Delay Rate -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                <span>Delay Occurrence Rate</span>
                <span class="text-rose-600 font-bold">{{ $delayRate }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-rose-500 h-2.5 rounded-full" style="width: {{ $delayRate }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Shipments exceeding expected arrival</p>
        </div>

        <!-- Exception Rate -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                <span>Exception Occurrence Rate</span>
                <span class="text-indigo-600 font-bold">{{ $exceptionRate }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-indigo-500 h-2.5 rounded-full" style="width: {{ $exceptionRate }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Avg Resolution: {{ $avgResolutionHours }}h ({{ $resolvedExceptionsCount }} resolved)</p>
        </div>

        <!-- Average Delay Duration -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                <span>Average Delay Duration</span>
                <span class="text-amber-600 font-bold">{{ $avgDelayDuration }} Hours</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="bg-amber-500 h-2.5 rounded-full" style="width: {{ min(100, ($avgDelayDuration / 48) * 100) }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Configured Group Threshold: {{ $groupThreshold }} Hours</p>
        </div>
    </div>

    <!-- Active Trade Corridors Reference Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-sky-950 to-slate-900 border border-slate-800 rounded-2xl p-5 text-white shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 text-amber-400 font-bold text-xs uppercase tracking-wider mb-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Maritime Freight Operations &amp; Lane Visibility</span>
                </div>
                <h2 class="text-lg font-black tracking-tight text-white">Active International Bulk Cargo Corridors</h2>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl">
                    Tracking end-to-end consignment lifecycles, deterministic ETA deviations, and disruption exceptions across synthetic multi-regional demonstration routes.
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(Auth::user()->canManageShipments())
                    <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 bg-sky-500 hover:bg-sky-400 text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-md transition">
                        <span>+ Create Shipment</span>
                    </a>
                @elseif(Auth::user()->isAdmin())
                    <a href="{{ route('settings.index') }}" class="inline-flex items-center space-x-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow-md transition">
                        <span>Configure Thresholds</span>
                    </a>
                @else
                    <a href="{{ route('shipments.index') }}" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow-md transition">
                        <span>View My Shipments &rarr;</span>
                    </a>
                @endif
            </div>
        </div>
        <!-- 4 Core Commodities Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-800/80 text-xs">
            <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50 flex items-center space-x-2.5">
                <span class="text-xl">🌽</span>
                <div>
                    <p class="font-bold text-slate-200">Corn (Jagung)</p>
                    <p class="text-[10px] text-slate-400">Santos, BR &rarr; Jakarta</p>
                </div>
            </div>
            <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50 flex items-center space-x-2.5">
                <span class="text-xl">🌾</span>
                <div>
                    <p class="font-bold text-slate-200">Wheat (Gandum)</p>
                    <p class="text-[10px] text-slate-400">Fremantle, AU &rarr; Jakarta</p>
                </div>
            </div>
            <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50 flex items-center space-x-2.5">
                <span class="text-xl">🌱</span>
                <div>
                    <p class="font-bold text-slate-200">Soybeans (Kedelai)</p>
                    <p class="text-[10px] text-slate-400">New Orleans, US &rarr; Qingdao</p>
                </div>
            </div>
            <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50 flex items-center space-x-2.5">
                <span class="text-xl">🍚</span>
                <div>
                    <p class="font-bold text-slate-200">Rice (Beras)</p>
                    <p class="text-[10px] text-slate-400">Bangkok, TH &rarr; Surabaya</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Global Fleet Visibility Map (OpenStreetMap + Leaflet.js) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Live Fleet Visibility &amp; Global Corridors Map</h3>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Real-time OpenStreetMap tracking of active vessels, flights, and maritime shipping lanes (Slide 7 &amp; 9)</p>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-500 inline-block"></span><span class="text-slate-600 text-[11px]">Normal Vessel</span></span>
                <span class="inline-flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span><span class="text-slate-600 text-[11px]">Exception / Disruption</span></span>
                <span class="inline-flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span><span class="text-slate-600 text-[11px]">Delivered</span></span>
            </div>
        </div>

        <!-- Leaflet Map Container -->
        <div id="fleetMap" class="w-full h-96 rounded-xl border border-slate-300 shadow-inner z-10" style="background: #a5bfdd;"></div>
    </div>

    <!-- Chart.js Visualizations (Slide 10 & 14) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Status Distribution -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center justify-between">
                <span>Shipments by Lifecycle Status</span>
                <span class="text-[11px] font-normal text-slate-400">Database Live Feed</span>
            </h3>
            <div class="relative h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Shipments by Carrier -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center justify-between">
                <span>Shipment Volume by Carrier</span>
                <span class="text-[11px] font-normal text-slate-400">All Active Carriers</span>
            </h3>
            <div class="relative h-64">
                <canvas id="carrierChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Shipments Table & Live Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Shipments Table (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-800">Operational Consignments</h3>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-500">View All Shipments &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Shipment</th>
                            <th class="py-2.5 px-3">Route</th>
                            <th class="py-2.5 px-3">Carrier</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Delay Risk</th>
                            <th class="py-2.5 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($recentShipments as $s)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-3 font-mono font-bold text-sky-700">
                                    <a href="{{ route('shipments.show', $s->id) }}" class="hover:underline">
                                        {{ $s->shipment_number }}
                                    </a>
                                    <div class="text-[10px] font-sans font-normal text-slate-400">{{ Str::limit($s->cargo_type, 22) }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-medium text-slate-900">{{ $s->origin ? $s->origin->city : '-' }} &rarr; {{ $s->destination ? $s->destination->city : '-' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $s->expected_departure->format('M d') }} - {{ $s->expected_arrival->format('M d') }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="font-medium">{{ $s->carrier ? $s->carrier->name : '-' }}</span>
                                </td>
                                <td class="py-3 px-3">
                                    @php
                                        $badgeClass = match($s->current_status) {
                                            'DELIVERED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'IN_TRANSIT' => 'bg-sky-100 text-sky-800 border-sky-200',
                                            'PICKED_UP' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                            'BOOKED' => 'bg-purple-100 text-purple-800 border-purple-200',
                                            'CANCELLED' => 'bg-slate-200 text-slate-800 border-slate-300',
                                            default => 'bg-amber-100 text-amber-800 border-amber-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                        {{ $s->current_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    @if($s->delay_status === 'DELAYED')
                                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            <span>+{{ $s->delay_duration_hours }}h</span>
                                            <span>({{ $s->delay_severity }})</span>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            ON TIME
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="{{ route('shipments.show', $s->id) }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded font-medium transition">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-slate-400">No shipments registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Live Milestones Feed (1 Col) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center justify-between">
                <span>Recent Tracking Milestones</span>
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </h3>

            <div class="space-y-4">
                @forelse($recentEvents as $evt)
                    <div class="relative pl-5 border-l-2 {{ $evt->event_type === 'EXCEPTION_FLAG' ? 'border-rose-400' : 'border-sky-400' }} text-xs">
                        <div class="absolute -left-1.5 top-0.5 w-2.5 h-2.5 rounded-full {{ $evt->event_type === 'EXCEPTION_FLAG' ? 'bg-rose-500' : 'bg-sky-500' }}"></div>
                        <div class="flex items-center justify-between text-slate-400 text-[10px]">
                            <span>{{ $evt->event_time->diffForHumans() }}</span>
                            <span class="font-mono font-bold text-sky-700">{{ $evt->shipment ? $evt->shipment->shipment_number : '' }}</span>
                        </div>
                        <div class="font-bold text-slate-800 mt-0.5">{{ $evt->location_name }}</div>
                        <p class="text-slate-600 text-[11px] mt-0.5">{{ $evt->description }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">No recent events.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($statusCounts)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($statusCounts)) !!},
                    backgroundColor: [
                        '#94a3b8', // CREATED
                        '#c084fc', // BOOKED
                        '#818cf8', // PICKED_UP
                        '#38bdf8', // IN_TRANSIT
                        '#34d399', // DELIVERED
                        '#f87171', // CANCELLED
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    }
                }
            }
        });

        // Carrier Bar Chart
        const carrierCtx = document.getElementById('carrierChart').getContext('2d');
        new Chart(carrierCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($carrierLabels) !!},
                datasets: [{
                    label: 'Consignments',
                    data: {!! json_encode($carrierData) !!},
                    backgroundColor: '#0284c7',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Initialize Live Global Fleet Visibility Map (OpenStreetMap + Leaflet.js per PPT Slide 9)
        const fleetData = {!! json_encode($activeFleetShipments) !!};
        const fleetMap = L.map('fleetMap', {
            zoomControl: true,
            attributionControl: true,
            minZoom: 2,
            maxZoom: 18,
            maxBounds: [[-85, -180], [85, 180]], // Restrict panning so world map doesn't repeat infinitely
            maxBoundsViscosity: 1.0,
            worldCopyJump: false
        }).setView([15, 60], 2);

        // Global Basemaps with Universal English Labels (No API Key Required, 100% Free & Crisp HD)
        const esriStreetEnglish = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            minZoom: 2,
            maxZoom: 18,
            noWrap: true, // Prevents duplicate world repeating side-by-side
            bounds: [[-85, -180], [85, 180]],
            attribution: 'Tiles &copy; Esri &mdash; OpenStreetMap &amp; contributors, Delorme, NAVTEQ, TomTom'
        }).addTo(fleetMap);

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

        // Layer Switcher for English HD Navigation Map, Topographic, and OSM Standard
        L.control.layers({
            "Global Navigation HD (English Labels)": esriStreetEnglish,
            "Topographic Relief (English)": esriTopoEnglish,
            "OpenStreetMap Standard": osmStandard
        }, null, { position: 'topright' }).addTo(fleetMap);

        const bounds = [];
        const animatedVessels = [];

        fleetData.forEach(shipment => {
            // Draw Navigable Maritime Sea Corridor with Antimeridian Split Protection
            if (shipment.corridor && shipment.corridor.length > 1) {
                const polyColor = shipment.has_exception ? '#ef4444' : (shipment.status === 'DELIVERED' ? '#10b981' : '#0284c7');
                
                let segments = [];
                let currentSegment = [];
                shipment.corridor.forEach((pt) => {
                    const lat = pt[0];
                    const lng = pt[1];
                    if (currentSegment.length > 0) {
                        const prevLng = currentSegment[currentSegment.length - 1][1];
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

                segments.forEach(seg => {
                    // Soft glow corridor base line
                    L.polyline(seg, {
                        color: polyColor,
                        weight: 5,
                        opacity: 0.35,
                        lineCap: 'round',
                        lineJoin: 'round'
                    }).addTo(fleetMap);

                    // Animated flowing dashed route (current flow effect)
                    L.polyline(seg, {
                        color: polyColor,
                        weight: 3,
                        opacity: 0.9,
                        dashArray: '8, 12',
                        className: 'flowing-corridor-dash'
                    }).addTo(fleetMap);
                });
            }

            // Vessel / Flight Marker with heading rotation and oceanic ripple radar
            if (shipment.lat && shipment.lng) {
                bounds.push([shipment.lat, shipment.lng]);

                let pinColor = '#0284c7';
                let iconSymbol = '🚢';
                if (shipment.transport_mode === 'AIR') iconSymbol = '✈️';
                if (shipment.status === 'DELIVERED') pinColor = '#10b981';
                if (shipment.has_exception) pinColor = '#ef4444';

                const heading = shipment.heading || 0;

                const iconHtml = `
                    <div style="position: relative; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <!-- Animated oceanic radar pulse -->
                        <div style="position: absolute; width: 36px; height: 36px; border-radius: 50%; background: ${pinColor}; opacity: 0.25; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                        <!-- Oriented ship compass card -->
                        <div style="position: relative; width: 34px; height: 34px; background: ${pinColor}; border: 2.5px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; transform: rotate(${heading}deg); transition: transform 0.5s ease;">
                            <span style="font-size: 15px; display: inline-block; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4));">${iconSymbol}</span>
                        </div>
                        <!-- Bearing arrow indicator -->
                        <div style="position: absolute; top: 1px; width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-bottom: 7px solid ${pinColor}; transform-origin: center 21px; transform: rotate(${heading}deg);"></div>
                        <!-- Disruption badge if exception present -->
                        ${shipment.has_exception ? '<span style="position: absolute; top: 2px; right: 2px; width: 11px; height: 11px; background: #dc2626; border: 2px solid white; border-radius: 50%; z-index: 20;"></span>' : ''}
                    </div>
                `;

                const markerIcon = L.divIcon({
                    html: iconHtml,
                    className: 'fleet-vessel-marker',
                    iconSize: [44, 44],
                    iconAnchor: [22, 22]
                });

                const m = L.marker([shipment.lat, shipment.lng], { icon: markerIcon }).addTo(fleetMap);

                m.bindPopup(`
                    <div style="font-family: inherit; font-size: 11px; line-height: 1.5; min-width: 210px;">
                        <div style="font-weight: 800; color: #0f172a; font-size: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <span>${shipment.number}</span>
                            <span style="font-size: 9px; font-weight: 600; color: #0284c7; background: #e0f2fe; padding: 1px 5px; border-radius: 4px;">HD Satellite/Voyager</span>
                        </div>
                        <div><strong>Carrier:</strong> ${shipment.carrier} (${shipment.transport_mode})</div>
                        <div><strong>Cargo:</strong> ${shipment.cargo}</div>
                        <div><strong>Sea Corridor:</strong> ${shipment.origin} &rarr; ${shipment.destination}</div>
                        <div><strong>Live Position:</strong> ${shipment.current_location}</div>
                        <div><strong>Navigational Heading:</strong> <span style="font-family: monospace; font-weight: bold; color: #0369a1;">${shipment.heading}&deg; Course</span></div>
                        <div style="margin-top: 4px;">
                            <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 9px; background: ${shipment.has_exception ? '#fee2e2; color: #991b1b;' : (shipment.delay_status === 'DELAYED' ? '#fef3c7; color: #92400e;' : '#dcfce7; color: #166534;')}">
                                ${shipment.status} &bull; ${shipment.delay_status === 'DELAYED' ? '+' + shipment.delay_hours + 'h' : 'ON TIME'}
                            </span>
                        </div>
                        <div style="margin-top: 6px; text-align: right;">
                            <a href="/shipments/${shipment.id}" style="color: #0284c7; font-weight: bold; text-decoration: underline;">Open Timeline &rarr;</a>
                        </div>
                    </div>
                `);

                // Register for real-time continuous sea corridor sailing
                if (shipment.status === 'IN_TRANSIT' && shipment.corridor && shipment.corridor.length > 1) {
                    let totalD = 0;
                    let segD = [];
                    for (let i = 0; i < shipment.corridor.length - 1; i++) {
                        const d = Math.hypot(shipment.corridor[i+1][0] - shipment.corridor[i][0], shipment.corridor[i+1][1] - shipment.corridor[i][1]);
                        segD.push(d);
                        totalD += d;
                    }
                    let startProg = (shipment.progress > 0) ? (shipment.progress > 1 ? shipment.progress / 100 : shipment.progress) : 0.35;
                    animatedVessels.push({
                        marker: m,
                        corridor: shipment.corridor,
                        totalDist: totalD,
                        segDists: segD,
                        progress: startProg,
                        heading: heading,
                        speedFactor: 1.0 + (Math.random() * 0.4 - 0.2)
                    });
                }
            }
        });

        // Real-Time Fleet Cruise Animation along Navigational Corridors
        let lastFleetTime = performance.now();
        function fleetSailLoop(now) {
            const dt = (now - lastFleetTime) / 1000;
            lastFleetTime = now;

            animatedVessels.forEach(v => {
                if (v.totalDist > 0) {
                    // Continuous realistic cruise along shipping fairway
                    v.progress += (1 / 240) * v.speedFactor * dt;
                    if (v.progress > 1.0) v.progress = 0.05;

                    const target = v.progress * v.totalDist;
                    let acc = 0;
                    for (let i = 0; i < v.segDists.length; i++) {
                        const sd = v.segDists[i];
                        if (acc + sd >= target || i === v.segDists.length - 1) {
                            const frac = sd > 0 ? (target - acc) / sd : 0;
                            const p1 = v.corridor[i];
                            const p2 = v.corridor[i+1];
                            const curLat = p1[0] + frac * (p2[0] - p1[0]);
                            const curLng = p1[1] + frac * (p2[1] - p1[1]);
                            v.marker.setLatLng([curLat, curLng]);
                            break;
                        }
                        acc += sd;
                    }
                }
            });
            requestAnimationFrame(fleetSailLoop);
        }
        requestAnimationFrame(fleetSailLoop);

        if (bounds.length > 0) {
            fleetMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 5 });
        }
    });
</script>
@endpush
