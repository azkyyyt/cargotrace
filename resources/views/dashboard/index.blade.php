@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Welcome -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Operations Dashboard</h1>
            <p class="text-xs text-slate-500 mt-1">
                Real-time shipment visibility &bull; Threshold reference: <span class="font-bold text-sky-700">{{ env('GROUP_DELAY_THRESHOLD_HOURS', 18) }}h</span>
            </p>
        </div>
        @if(Auth::user()->canManageShipments())
            <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Shipment</span>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <p class="text-[10px] text-slate-400 mt-2">Reflects shipments exceeding expected arrival</p>
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
            <p class="text-[10px] text-slate-400 mt-2">Configured Group Threshold: {{ env('GROUP_DELAY_THRESHOLD_HOURS', 18) }} Hours</p>
        </div>
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
    });
</script>
@endpush
