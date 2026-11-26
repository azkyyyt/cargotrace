@extends('layouts.app')

@section('title', 'Create New Consignment')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="grainConsignmentForm()">

    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('shipments.index') }}" class="hover:underline">Shipments</a>
                <span>&rsaquo;</span>
                <span class="font-bold text-slate-800">New Consignment</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Grain Consignment</h1>
            <p class="text-xs text-slate-500 mt-0.5">Register an international bulk grain shipment on maritime corridors</p>
        </div>
        <a href="{{ route('shipments.index') }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-lg transition inline-flex items-center space-x-1">
            <span>&larr;</span>
            <span>Back to Shipments</span>
        </a>
    </div>

    <!-- Quick Cargo Lane Presets -->
    <div class="bg-slate-900 text-white rounded-lg p-5 shadow-sm border border-slate-800">
        <div class="flex items-center space-x-2 text-sky-400 font-semibold text-xs uppercase tracking-wider mb-2.5">
            <span>Quick Trade Lane Presets (Click to Pre-Fill):</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
            <!-- Preset 1: Corn Brazil -> Indonesia -->
            <button type="button" 
                    @click="applyPreset('Corn', 28000, 36400, 48.50, 'BRSSZ', 'IDTPP', 'Brazilian Yellow Corn Grade #2 (Moisture 13.5% Max)')" 
                    class="p-3 bg-slate-800/80 hover:bg-slate-700/80 rounded-xl text-left border border-slate-700 transition hover:border-amber-400 group">
                <div class="font-bold text-amber-400 flex items-center space-x-1">
                    <span>🌽</span>
                    <span>Corn (Jagung)</span>
                </div>
                <div class="text-[11px] text-slate-200 mt-1">Santos, BR &rarr; Jakarta</div>
                <div class="text-[10px] text-slate-400">28,000 MT &bull; Panamax</div>
            </button>

            <!-- Preset 2: Wheat Australia -> Indonesia -->
            <button type="button" 
                    @click="applyPreset('Wheat', 32000, 41600, 32.00, 'AUFRE', 'IDTPP', 'Australian Premium White (APW) Wheat (Moisture 11.5% Max)')" 
                    class="p-3 bg-slate-800/80 hover:bg-slate-700/80 rounded-xl text-left border border-slate-700 transition hover:border-sky-400 group">
                <div class="font-bold text-sky-400 flex items-center space-x-1">
                    <span>🌾</span>
                    <span>Wheat (Gandum)</span>
                </div>
                <div class="text-[11px] text-slate-200 mt-1">Fremantle, AU &rarr; Jakarta</div>
                <div class="text-[10px] text-slate-400">32,000 MT &bull; Bulk Hold</div>
            </button>

            <!-- Preset 3: Soybeans USA -> China -->
            <button type="button" 
                    @click="applyPreset('Soybeans', 54000, 70200, 52.00, 'USMSY', 'CNTAO', 'US No. 2 Yellow Soybeans (Protein 34% Min, Moisture 13.0%)')" 
                    class="p-3 bg-slate-800/80 hover:bg-slate-700/80 rounded-xl text-left border border-slate-700 transition hover:border-emerald-400 group">
                <div class="font-bold text-emerald-400 flex items-center space-x-1">
                    <span>🌱</span>
                    <span>Soybeans (Kedelai)</span>
                </div>
                <div class="text-[11px] text-slate-200 mt-1">New Orleans &rarr; Qingdao</div>
                <div class="text-[10px] text-slate-400">54,000 MT &bull; Capesize</div>
            </button>

            <!-- Preset 4: Rice Thailand -> Indonesia -->
            <button type="button" 
                    @click="applyPreset('Rice', 12500, 16250, 28.00, 'THBKK', 'IDSUB', 'Thai Hom Mali Jasmine Rice (Jumbo Bags 1000kg)')" 
                    class="p-3 bg-slate-800/80 hover:bg-slate-700/80 rounded-xl text-left border border-slate-700 transition hover:border-rose-400 group">
                <div class="font-bold text-rose-400 flex items-center space-x-1">
                    <span>🍚</span>
                    <span>Rice (Beras)</span>
                </div>
                <div class="text-[11px] text-slate-200 mt-1">Bangkok &rarr; Surabaya</div>
                <div class="text-[10px] text-slate-400">12,500 MT &bull; Jumbo Bags</div>
            </button>
        </div>
    </div>

    <!-- Main Creation Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-5 text-xs">
            @csrf

            <!-- Shipment Ref & Customer -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                        <span>Shipment Reference Number</span>
                        <span class="text-[10px] text-slate-400 font-normal">(Optional, Auto-Generated)</span>
                    </label>
                    <input type="text" name="shipment_number" value="{{ old('shipment_number') }}" placeholder="Leave blank for auto: CT-{{ date('Y') }}-..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono focus:ring-2 focus:ring-sky-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Customer / Consignee *</label>
                    @if(Auth::user()->isCustomer())
                        <div class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-slate-800 flex items-center justify-between">
                            <span class="truncate">{{ Auth::user()->name }} ({{ Auth::user()->company ?: 'Consignee' }})</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold shrink-0 ml-2">Your Account</span>
                        </div>
                        <input type="hidden" name="customer_id" value="{{ Auth::user()->id }}">
                    @else
                        <select name="customer_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500 font-medium">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id') == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->name }} ({{ $cust->company ?: 'Consignee' }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <!-- Carrier & Commodity -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Maritime Carrier Line *</label>
                    <select name="carrier_id" x-model="carrierId" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500 font-medium">
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}" {{ old('carrier_id') == $car->id ? 'selected' : '' }}>
                                {{ $car->name }} ({{ $car->transport_mode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Cargo Type *</label>
                    <input type="text" name="cargo_type" x-model="cargoType" list="cargoSuggestions" required placeholder="e.g. Corn, Wheat, Soybeans, Electronics" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500 font-bold text-slate-900">
                    <datalist id="cargoSuggestions">
                        <option value="Corn">
                        <option value="Wheat">
                        <option value="Soybeans">
                        <option value="Rice">
                        <option value="Electronic Parts">
                        <option value="Medical Equipment">
                        <option value="Industrial Machinery">
                    </datalist>
                </div>
            </div>

            <!-- Route Ports (Origin & Destination) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Origin Port of Loading *</label>
                    <select name="origin_id" x-model="originId" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">
                                {{ $loc->name }} ({{ $loc->city }}, {{ $loc->country }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Destination Port of Discharge *</label>
                    <select name="destination_id" x-model="destinationId" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">
                                {{ $loc->name }} ({{ $loc->city }}, {{ $loc->country }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Weight & Volume -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Cargo Weight (MT) *</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="weight" x-model.number="weightVal" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono font-bold focus:ring-2 focus:ring-sky-500">
                        <span class="absolute right-3 top-2 text-slate-400 font-mono text-[11px]" x-text="Number(weightVal).toLocaleString() + ' MT'"></span>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Volume (CBM) *</label>
                    <input type="number" step="0.1" name="volume" x-model.number="volumeVal" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono font-bold focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <!-- Departure & Arrival Dates -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Expected Departure *</label>
                    <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', now()->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Expected Arrival *</label>
                    <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', now()->addDays(14)->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>
            </div>

            <!-- Cargo Item Manifest Specification -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Manifest Item Description &amp; Quality Grade</label>
                    <input type="text" name="item_description" x-model="itemDesc" placeholder="e.g. US No. 2 Yellow Soybeans, Moisture 13% Max" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Quantity (Holds / Lots)</label>
                    <input type="number" name="item_quantity" value="1" min="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white font-mono">
                </div>
            </div>

            <!-- Special Handling Instructions / Notes -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Stowage &amp; Handling Instructions</label>
                <textarea name="notes" rows="2" placeholder="e.g. Maintain seal integrity; keep hatches battened during ocean transit..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500"></textarea>
            </div>

            <!-- Form Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('shipments.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-black rounded-lg shadow-sm transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Create Consignment</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function grainConsignmentForm() {
        return {
            cargoType: 'Corn',
            weightVal: 25000,
            volumeVal: 32500,
            originId: '{{ $locations->where('code', 'BRSSZ')->first()?->id ?? ($locations->first()?->id ?? '') }}',
            destinationId: '{{ $locations->where('code', 'IDTPP')->first()?->id ?? ($locations->skip(1)->first()?->id ?? '') }}',
            carrierId: '{{ $carriers->first()?->id ?? '' }}',
            itemDesc: 'Brazilian Yellow Corn Grade #2 (Moisture 13.5% Max)',
            ratePerTon: 48.50,
            get totalFreight() {
                return (this.weightVal * this.ratePerTon).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
            },
            applyPreset(cargo, weight, volume, rate, originCode, destCode, desc) {
                this.cargoType = cargo;
                this.weightVal = weight;
                this.volumeVal = volume;
                this.ratePerTon = rate;
                this.itemDesc = desc;

                const locs = {!! json_encode($locations->map(function($l) { return ['id' => $l->id, 'code' => $l->code]; })) !!};
                const o = locs.find(function(l) { return l.code === originCode; });
                const d = locs.find(function(l) { return l.code === destCode; });
                if (o) this.originId = o.id;
                if (d) this.destinationId = d.id;
            }
        };
    }
</script>
@endpush
