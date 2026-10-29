@extends('layouts.app')

@section('title', 'Disruption Exceptions')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Disruption Exceptions</h1>
            <p class="text-xs text-slate-500 mt-1">Manage, investigate, and resolve supply chain bottlenecks (Slide 8)</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('exceptions.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                    <option value="">All Statuses</option>
                    <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>OPEN</option>
                    <option value="INVESTIGATING" {{ request('status') === 'INVESTIGATING' ? 'selected' : '' }}>INVESTIGATING</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-500 uppercase tracking-wider mb-1">Category</label>
                <select name="exception_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                    <option value="">All Categories</option>
                    @foreach(['CUSTOMS_HOLD', 'WEATHER_DELAY', 'PORT_CONGESTION', 'DAMAGED', 'MISROUTED', 'DOCUMENTATION_ISSUE'] as $type)
                        <option value="{{ $type }}" {{ request('exception_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-500 uppercase tracking-wider mb-1">Severity</label>
                <select name="severity" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                    <option value="">All Severities</option>
                    <option value="LOW" {{ request('severity') === 'LOW' ? 'selected' : '' }}>LOW</option>
                    <option value="MEDIUM" {{ request('severity') === 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                    <option value="HIGH" {{ request('severity') === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-4 rounded-lg shadow-sm transition">
                    Filter
                </button>
                @if(request()->hasAny(['status', 'exception_type', 'severity']))
                    <a href="{{ route('exceptions.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 px-3 rounded-lg font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Exceptions Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Shipment</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Severity</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Detected At</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($exceptions as $ex)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-sky-700">
                                @if($ex->shipment)
                                    <a href="{{ route('shipments.show', $ex->shipment_id) }}" class="hover:underline">
                                        {{ $ex->shipment->shipment_number }}
                                    </a>
                                    <div class="text-[10px] font-sans font-normal text-slate-400">
                                        Customer: {{ $ex->shipment->customer ? $ex->shipment->customer->name : '-' }}
                                    </div>
                                @else
                                    #{{ $ex->shipment_id }}
                                @endif
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                {{ $ex->exception_type }}
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $sevClass = match($ex->severity) {
                                        'HIGH' => 'bg-rose-100 text-rose-800 border-rose-200',
                                        'MEDIUM' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        default => 'bg-sky-100 text-sky-800 border-sky-200',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $sevClass }}">
                                    {{ $ex->severity }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $statClass = match($ex->status) {
                                        'RESOLVED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'INVESTIGATING' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        default => 'bg-rose-100 text-rose-800 border-rose-200',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $statClass }}">
                                    {{ $ex->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                {{ $ex->detected_at->format('M d, Y H:i') }}
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-slate-800">{{ $ex->description }}</p>
                                @if($ex->resolution_notes)
                                    <div class="text-[10px] text-emerald-700 mt-1 font-medium">
                                        Resolved: {{ $ex->resolution_notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($ex->shipment)
                                    <a href="{{ route('shipments.show', $ex->shipment_id) }}" class="text-xs bg-sky-50 text-sky-700 hover:bg-sky-100 font-semibold px-2.5 py-1 rounded transition">
                                        Resolve in Timeline &rarr;
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">
                                No exceptions found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $exceptions->links() }}
        </div>
    </div>
</div>
@endsection
