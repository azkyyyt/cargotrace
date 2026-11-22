@extends('layouts.app')

@section('title', 'Port & Hub Locations')

@section('content')
<div class="space-y-6" x-data="{ 
    addLocationModal: false,
    searchQuery: '',
    searchResults: [],
    isSearching: false,
    form: {
        code: '',
        type: 'PORT',
        name: '',
        city: '',
        country: '',
        latitude: '',
        longitude: '',
        address: ''
    },
    pickerMap: null,
    pickerMarker: null,
    initPickerMap() {
        this.$nextTick(() => {
            const container = document.getElementById('locationPickerMap');
            if (!container) return;
            if (this.pickerMap) {
                setTimeout(() => this.pickerMap.invalidateSize(), 200);
                return;
            }
            this.pickerMap = L.map('locationPickerMap', {
                minZoom: 2,
                maxZoom: 18,
                maxBounds: [[-85, -180], [85, 180]],
                maxBoundsViscosity: 1.0,
                worldCopyJump: false
            }).setView([-6.2088, 106.8456], 3);

            // Esri HD English Basemap
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                minZoom: 2,
                maxZoom: 18,
                noWrap: true,
                bounds: [[-85, -180], [85, 180]],
                attribution: 'Tiles &copy; Esri &mdash; OpenStreetMap &amp; contributors'
            }).addTo(this.pickerMap);

            this.pickerMap.on('click', (e) => {
                this.updatePin(e.latlng.lat, e.latlng.lng);
            });

            setTimeout(() => this.pickerMap.invalidateSize(), 250);
        });
    },
    async doSearch() {
        const q = this.searchQuery.trim();
        if (q.length < 2) {
            this.searchResults = [];
            return;
        }
        this.isSearching = true;
        try {
            const res = await fetch(`/locations/search?q=${encodeURIComponent(q)}`);
            this.searchResults = await res.json();
        } catch (e) {
            console.error('Location search error:', e);
        } finally {
            this.isSearching = false;
        }
    },
    selectPlace(item) {
        this.searchQuery = item.name;
        this.searchResults = [];
        this.form.name = item.name;
        this.form.city = item.city;
        this.form.country = item.country;
        this.form.address = item.address;
        this.form.latitude = Number(item.latitude).toFixed(6);
        this.form.longitude = Number(item.longitude).toFixed(6);

        // Auto-generate code if empty (e.g. BLWN from Belawan)
        if (!this.form.code) {
            const raw = (item.city || item.name).replace(/[^a-zA-Z]/g, '').toUpperCase();
            this.form.code = raw.substring(0, 4) || 'NODE';
        }

        this.updatePin(item.latitude, item.longitude, true);
    },
    updatePin(lat, lng, fly = false) {
        this.form.latitude = Number(lat).toFixed(6);
        this.form.longitude = Number(lng).toFixed(6);

        if (!this.pickerMarker) {
            const pinIcon = L.divIcon({
                html: `<div style='background-color: #f59e0b; width: 18px; height: 18px; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 3px 8px rgba(0,0,0,0.5);'></div>`,
                className: 'custom-picker-pin',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });
            this.pickerMarker = L.marker([lat, lng], { draggable: true, icon: pinIcon }).addTo(this.pickerMap);
            this.pickerMarker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.updatePin(pos.lat, pos.lng);
            });
        } else {
            this.pickerMarker.setLatLng([lat, lng]);
        }

        if (fly && this.pickerMap) {
            this.pickerMap.flyTo([lat, lng], 12);
        }
    }
}" x-init="$watch('addLocationModal', value => { if (value) initPickerMap(); })">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Ports, Airports &amp; Logistics Hubs</h1>
            <p class="text-xs text-slate-500 mt-1">Geographic nodes and terminals used for grain routes &bull; Google Maps-style location search integrated</p>
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

    <!-- Modal: Add Location with Google Maps-Style Places Autocomplete & Interactive Leaflet Picker -->
    <div x-cloak x-show="addLocationModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 border border-slate-200" @click.away="addLocationModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                        <span class="text-amber-500">📍</span>
                        <span>Add Terminal / Port Location</span>
                    </h3>
                    <p class="text-xs text-slate-500">Search like Google Maps or drop a pin directly on the interactive map</p>
                </div>
                <button type="button" @click="addLocationModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- 1. Google Maps-Style Autocomplete Search Bar -->
            <div class="mb-4 relative">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    🔍 Search Location / Port / City (Autocomplete)
                </label>
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           @input.debounce.300ms="doSearch()" 
                           placeholder="Type to search (e.g. Belawan, Port of Medan, Santos, Tanjung Priok, Rotterdam)..." 
                           class="w-full pl-9 pr-9 py-2.5 border-2 border-sky-400 focus:border-sky-600 rounded-xl text-xs font-medium outline-none shadow-sm transition">
                    <div class="absolute left-3 top-3 text-slate-400">
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div x-show="isSearching" class="absolute right-3 top-3">
                        <svg class="animate-spin h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </div>
                </div>

                <!-- Autocomplete Dropdown -->
                <div x-show="searchResults.length > 0" 
                     x-transition 
                     @click.away="searchResults = []" 
                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-300 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-slate-100">
                    <template x-for="(item, idx) in searchResults" :key="idx">
                        <div @click="selectPlace(item)" class="p-2.5 hover:bg-sky-50 cursor-pointer transition flex items-start space-x-2.5 text-xs">
                            <span class="text-base mt-0.5">📍</span>
                            <div class="flex-1">
                                <div class="font-bold text-slate-900" x-text="item.name"></div>
                                <div class="text-[11px] text-slate-500" x-text="item.address"></div>
                                <div class="text-[10px] font-mono text-sky-700 mt-0.5" x-text="'[' + item.latitude + ', ' + item.longitude + '] &bull; ' + item.source"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- 2. Interactive Leaflet Location Picker Map -->
            <div class="space-y-1 mb-4">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-slate-700">Interactive Location Picker Map</span>
                    <span class="text-sky-700 font-semibold" x-show="form.latitude && form.longitude" x-text="'📍 [' + form.latitude + ', ' + form.longitude + '] Pinned'"></span>
                </div>
                <div id="locationPickerMap" class="w-full h-44 rounded-xl border border-slate-300 shadow-inner z-10" style="background: #a5bfdd;"></div>
                <p class="text-[10px] text-slate-400 italic">💡 Click anywhere on the map or drag the pin to fine-tune the exact berth, dock, or terminal coordinates.</p>
            </div>

            <!-- 3. Form Input Fields (Auto-Populated from Selection or Manual) -->
            <form action="{{ route('locations.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Node Code *</label>
                        <input type="text" name="code" x-model="form.code" required placeholder="e.g. BLWN, IDTPP" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Facility Type *</label>
                        <select name="type" x-model="form.type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                            <option value="PORT">PORT (Seaport / Silo Terminal)</option>
                            <option value="AIRPORT">AIRPORT (Air Cargo Hub)</option>
                            <option value="WAREHOUSE">WAREHOUSE (Grain Storage)</option>
                            <option value="CUSTOMS">CUSTOMS (Border Post)</option>
                            <option value="HUB">HUB (Distribution Centre)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Facility Name *</label>
                    <input type="text" name="name" x-model="form.name" required placeholder="e.g. Pelabuhan Belawan / Port of Medan" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" x-model="form.city" required placeholder="Medan" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Country *</label>
                        <input type="text" name="country" x-model="form.country" required placeholder="Indonesia" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            <span>Latitude</span>
                            <span class="text-[10px] text-emerald-600 font-normal" x-show="form.latitude">&bull; Auto-Pinned</span>
                        </label>
                        <input type="number" step="any" name="latitude" x-model="form.latitude" required placeholder="Select from search or map" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            <span>Longitude</span>
                            <span class="text-[10px] text-emerald-600 font-normal" x-show="form.longitude">&bull; Auto-Pinned</span>
                        </label>
                        <input type="number" step="any" name="longitude" x-model="form.longitude" required placeholder="Select from search or map" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Terminal / Berth Address</label>
                    <textarea name="address" x-model="form.address" rows="2" placeholder="Terminal street address or berth description" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addLocationModal = false" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg shadow-sm transition">Save Terminal Location</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
