@extends('layouts.app')

@section('title', 'Order Grain Consignment')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    cargo: 'Corn',
    weight: 25000,
    ratePerTon: 48.50,
    origin: '',
    paymentMethod: 'QRIS_REALTIME',
    get totalFreight() {
        return (this.weight * this.ratePerTon).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    }
}">
    <div class="flex items-center justify-between">
        <div>
            <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wide">
                <span>🌾</span>
                <span>Global Grain Trade Portal</span>
            </span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Order Grain Cargo Consignment</h1>
            <p class="text-xs text-slate-500 mt-0.5">International bulk dry grain booking with real-time simulated payment gateway verification</p>
        </div>
        <a href="{{ route('shipments.index') }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-lg transition">
            &larr; Back to Shipments
        </a>
    </div>

    <!-- Quick Scenario Presets Bar -->
    <div class="bg-gradient-to-r from-slate-900 to-sky-950 text-white rounded-xl p-4 shadow-sm border border-slate-800">
        <p class="text-xs font-bold text-amber-400 uppercase tracking-wider mb-2">⚡ 1-Click Fast Demonstration Presets:</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <button type="button" @click="cargo = 'Corn'; weight = 28000; ratePerTon = 48.50" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <span class="font-bold text-amber-400">🌽 Brazil &rarr; Indonesia</span>
                <p class="text-[10px] text-slate-300">28,000 MT Yellow Corn</p>
            </button>
            <button type="button" @click="cargo = 'Wheat'; weight = 32000; ratePerTon = 30.00" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <span class="font-bold text-sky-400">🌾 Australia &rarr; Indonesia</span>
                <p class="text-[10px] text-slate-300">32,000 MT APW Wheat</p>
            </button>
            <button type="button" @click="cargo = 'Soybeans'; weight = 54000; ratePerTon = 53.00" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <span class="font-bold text-emerald-400">🌱 US Gulf &rarr; China</span>
                <p class="text-[10px] text-slate-300">54,000 MT Soybeans</p>
            </button>
            <button type="button" @click="cargo = 'Rice'; weight = 12500; ratePerTon = 30.00" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <span class="font-bold text-rose-400">🍚 Thailand &rarr; Indonesia</span>
                <p class="text-[10px] text-slate-300">12,500 MT Fragrant Rice</p>
            </button>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('shipments.bookGrain') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <!-- Commodity Selection & Weight -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Grain Commodity *</label>
                    <select name="cargo_type" x-model="cargo" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-amber-500 font-bold text-slate-800">
                        <option value="Corn">🌽 Corn (Jagung)</option>
                        <option value="Wheat">🌾 Wheat (Gandum)</option>
                        <option value="Soybeans">🌱 Soybeans (Kedelai)</option>
                        <option value="Rice">🍚 Rice (Beras)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tonnage (Metric Tons) *</label>
                    <input type="number" step="100" name="weight" x-model.number="weight" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-amber-500 font-mono font-bold">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Carrier Line *</label>
                    <select name="carrier_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-amber-500">
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}">{{ $car->name }} ({{ $car->transport_mode }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Route Origin & Destination -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Origin Port of Loading *</label>
                    <select name="origin_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-amber-500">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $loc->code === 'BRSSZ' ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->city }}, {{ $loc->country }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Destination Port of Discharge *</label>
                    <select name="destination_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-amber-500">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $loc->code === 'IDTPP' ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->city }}, {{ $loc->country }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Quality Spec & Payment Method -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Grain Grade &amp; Specification</label>
                    <input type="text" name="grade_specification" placeholder="e.g. Export Standard Non-GMO #2, Moisture 13.5% Max" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Payment Method (Real-Time Simulator) *</label>
                    <select name="payment_method" x-model="paymentMethod" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-amber-500 font-semibold">
                        <option value="QRIS_REALTIME">📱 QRIS Real-Time Simulator</option>
                        <option value="VIRTUAL_ACCOUNT">🏦 Virtual Account (Bank Mandiri / BCA / BNI)</option>
                        <option value="LETTER_OF_CREDIT">📜 Letter of Credit (L/C - Trade Finance)</option>
                        <option value="CREDIT_CARD">💳 International Corporate Card</option>
                    </select>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Stowage &amp; Handling Instructions</label>
                <textarea name="notes" rows="2" placeholder="e.g. Clean hold certificate required, fumigation prior to sailing..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>

            <!-- Estimated Freight Calculation Summary -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estimated Ocean Freight Charge</p>
                    <p class="text-xl font-black text-slate-900 mt-0.5" x-text="totalFreight"></p>
                    <p class="text-[10px] text-slate-400">Calculated at bulk carrier average rate per MT</p>
                </div>
                <div class="text-right text-[11px] text-slate-500">
                    <span class="inline-block px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 font-bold">Simulated Sandbox Gateway</span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('shipments.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-lg shadow transition">
                    Place Grain Order &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Cargo Type -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Commodity / Cargo Type *</label>
                    <input type="text" name="cargo_type" value="{{ old('cargo_type') }}" placeholder="e.g. Semiconductor Components" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Weight -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Total Weight (kg) *</label>
                    <input type="number" step="0.01" name="weight" value="{{ old('weight', 1500) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Volume -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Total Volume (CBM) *</label>
                    <input type="number" step="0.01" name="volume" value="{{ old('volume', 8.5) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Departure Date -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Expected Departure *</label>
                    <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', now()->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Arrival Date -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Expected Arrival *</label>
                    <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', now()->addDays(5)->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Special Handling Instructions / Notes</label>
                <textarea name="notes" rows="2" placeholder="e.g. Keep dry, temperature range 15-25C..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('shipments.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg shadow-sm transition">
                    Create Shipment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
