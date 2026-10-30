@extends('layouts.app')

@section('title', 'Port & Hub Locations')

@section('content')
<div class="space-y-6" x-data="{ addLocationModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Ports, Airports &amp; Logistics Hubs</h1>
            <p class="text-xs text-slate-500 mt-1">Geographic nodes and terminals used for route mapping &bull; Leaflet &amp; Nominatim integrated</p>
        </div>
        @if(Auth::user()->isAdmin())
            <button type="button" @click="addLocationModal = true" class="inline-flex items-center space-x-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Node Location</span>
            </button>
        @endif
    </div>

    <!-- Locations Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Code</th>
                        <th class="py-3 px-4">Node Name</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">City &amp; Country</th>
                        <th class="py-3 px-4">GPS Coordinates (Lat / Lng)</th>
                        <th class="py-3 px-4">Outbound / Inbound</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($locations as $loc)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-sky-700">{{ $loc->code }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $loc->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $loc->address ?: 'Standard terminal facilities' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $typeColor = match($loc->type) {
                                        'PORT' => 'bg-blue-100 text-blue-800',
                                        'AIRPORT' => 'bg-sky-100 text-sky-800',
                                        'CUSTOMS' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $typeColor }}">
                                    {{ $loc->type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">
                                {{ $loc->city }}, {{ $loc->country }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                {{ number_format($loc->latitude, 4) }}, {{ number_format($loc->longitude, 4) }}
                            </td>
                            <td class="py-3.5 px-4 font-medium">
                                <span class="text-sky-700">{{ $loc->outbound_shipments_count }} out</span> &bull; 
                                <span class="text-emerald-700">{{ $loc->inbound_shipments_count }} in</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">No hub locations registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $locations->links() }}
        </div>
    </div>

    <!-- Modal: Add Location with Auto Geocoding -->
    <div x-cloak x-show="addLocationModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="addLocationModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-1">Add Terminal / Port Location</h3>
            <p class="text-xs text-slate-500 mb-4">Leave coordinates blank to automatically resolve with OpenStreetMap Nominatim!</p>

            <form action="{{ route('locations.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Node Code *</label>
                        <input type="text" name="code" required placeholder="e.g. USNYC, SGSIN" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Type *</label>
                        <select name="type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                            <option value="PORT">PORT (Seaport)</option>
                            <option value="AIRPORT">AIRPORT (Air Cargo)</option>
                            <option value="WAREHOUSE">WAREHOUSE (Bonded)</option>
                            <option value="CUSTOMS">CUSTOMS (Border Post)</option>
                            <option value="HUB">HUB (Distribution)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Facility Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Port of New York and New Jersey" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" required placeholder="New York" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Country *</label>
                        <input type="text" name="country" required placeholder="United States" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Latitude (Optional)</label>
                        <input type="number" step="any" name="latitude" placeholder="Auto-geocoded" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Longitude (Optional)</label>
                        <input type="number" step="any" name="longitude" placeholder="Auto-geocoded" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Terminal Address</label>
                    <textarea name="address" rows="2" placeholder="Terminal street address or berth description" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addLocationModal = false" class="px-3 py-2 bg-slate-100 text-slate-700 font-semibold rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg shadow-sm">Save &amp; Resolve Coordinates</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
