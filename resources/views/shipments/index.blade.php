@extends('layouts.app')

@section('title', 'Shipments Directory')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Shipments Directory</h1>
            <p class="text-xs text-slate-500 mt-1">Search, monitor and track global consignments across active trade lanes</p>
        </div>
        @if(Auth::user()->canManageShipments())
            <a href="{{ route('shipments.create') }}" class="inline-flex items-center space-x-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Book New Shipment</span>
            </a>
        @endif
    </div>

    <!-- Filter & Search Toolbar (Slide 10 Requirement) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('shipments.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Keyword -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Ref / Cargo</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="CT-2026-..., electronics, etc." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500 outline-none">
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                        <option value="">All Lifecycle Statuses</option>
                        @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Carrier Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Carrier</label>
                    <select name="carrier_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $c)
                            <option value="{{ $c->id }}" {{ request('carrier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->transport_mode }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Status Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Delay State</label>
                    <select name="delay_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                        <option value="">All Delay Statuses</option>
                        <option value="ON_TIME" {{ request('delay_status') === 'ON_TIME' ? 'selected' : '' }}>On-Time Only</option>
                        <option value="DELAYED" {{ request('delay_status') === 'DELAYED' ? 'selected' : '' }}>Delayed Only</option>
                    </select>
                </div>
            </div>

            <!-- Date Range & Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-100 items-end">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Departure From</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Arrival Until</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs outline-none">
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-4 rounded-lg text-xs transition shadow-sm">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['search', 'status', 'carrier_id', 'delay_status', 'start_date', 'end_date']))
                        <a href="{{ route('shipments.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 px-3 rounded-lg text-xs font-medium transition">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Shipments Data Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Shipment Number</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Lane (Origin &rarr; Destination)</th>
                        <th class="py-3 px-4">Carrier</th>
                        <th class="py-3 px-4">Cargo / Weight</th>
                        <th class="py-3 px-4">Current Status</th>
                        <th class="py-3 px-4">Delay State</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-sky-700">
                                <a href="{{ route('shipments.show', $s->id) }}" class="hover:underline">
                                    {{ $s->shipment_number }}
                                </a>
                                <div class="text-[10px] font-sans font-normal text-slate-400">Created: {{ $s->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-900">{{ $s->customer ? $s->customer->name : 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $s->customer ? $s->customer->company : '' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-900">
                                    {{ $s->origin ? $s->origin->name : '?' }} ({{ $s->origin ? $s->origin->city : '' }})
                                </div>
                                <div class="text-[11px] text-slate-500 font-semibold">
                                    &darr; {{ $s->destination ? $s->destination->name : '?' }} ({{ $s->destination ? $s->destination->city : '' }})
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800">{{ $s->carrier ? $s->carrier->name : 'Unassigned' }}</div>
                                <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-mono">{{ $s->carrier ? $s->carrier->transport_mode : '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-900">{{ Str::limit($s->cargo_type, 24) }}</div>
                                <div class="text-[10px] text-slate-500">{{ number_format($s->weight, 0) }} kg &bull; {{ $s->volume }} CBM</div>
                            </td>
                            <td class="py-3.5 px-4">
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
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $s->current_status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($s->delay_status === 'DELAYED')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>+{{ $s->delay_duration_hours }}h ({{ $s->delay_severity }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>ON TIME</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('shipments.show', $s->id) }}" class="inline-block bg-sky-50 hover:bg-sky-100 text-sky-700 px-2.5 py-1 rounded font-medium transition">
                                    Details &rarr;
                                </a>
                                @if(Auth::user()->canManageShipments() && !$s->isTerminal())
                                    <a href="{{ route('shipments.edit', $s->id) }}" class="inline-block bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-1 rounded transition">
                                        Edit
                                    </a>
                                @endif
                                @if(Auth::user()->isAdmin())
                                    <form action="{{ route('shipments.destroy', $s->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete shipment {{ $s->shipment_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                No shipments found matching the specified search or filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Server-side Pagination Links -->
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $shipments->links() }}
        </div>
    </div>
</div>
@endsection
