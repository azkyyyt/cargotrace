@extends('layouts.app')

@section('title', 'Book New Shipment')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Book New Consignment</h1>
            <p class="text-xs text-slate-500 mt-1">Initiates shipment lifecycle state from CREATED &bull; Validated in backend</p>
        </div>
        <a href="{{ route('shipments.index') }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-lg transition">
            &larr; Back to Shipments
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Shipment Number -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Shipment Reference (Optional)</label>
                    <input type="text" name="shipment_number" value="{{ old('shipment_number') }}" placeholder="Auto-generated e.g. CT-2026-XXXX" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Customer Account -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Customer / Consignee *</label>
                    <select name="customer_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500">
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->company ?: 'Individual' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Carrier -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Carrier Line *</label>
                    <select name="carrier_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500">
                        <option value="">-- Choose Carrier --</option>
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}" {{ old('carrier_id') == $car->id ? 'selected' : '' }}>
                                {{ $car->name }} [{{ $car->transport_mode }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Origin -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Origin Port/Hub *</label>
                    <select name="origin_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500">
                        <option value="">-- Choose Origin --</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('origin_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->city }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Destination -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Destination Port/Hub *</label>
                    <select name="destination_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white focus:ring-2 focus:ring-sky-500">
                        <option value="">-- Choose Destination --</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('destination_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->city }})
                            </option>
                        @endforeach
                    </select>
                </div>
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
