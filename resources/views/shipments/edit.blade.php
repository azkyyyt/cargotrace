@extends('layouts.app')

@section('title', 'Edit Shipment ' . $shipment->shipment_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight font-mono">Edit {{ $shipment->shipment_number }}</h1>
            <p class="text-xs text-slate-500 mt-1">Update consignment parameters and dates</p>
        </div>
        <a href="{{ route('shipments.show', $shipment->id) }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-lg transition">
            &larr; Back to Details
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('shipments.update', $shipment->id) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Carrier -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Carrier Line *</label>
                    <select name="carrier_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        @foreach($carriers as $car)
                            <option value="{{ $car->id }}" {{ $shipment->carrier_id == $car->id ? 'selected' : '' }}>
                                {{ $car->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Origin -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Origin Port/Hub *</label>
                    <select name="origin_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $shipment->origin_id == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Destination -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Destination Port/Hub *</label>
                    <select name="destination_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $shipment->destination_id == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Cargo Type -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Cargo Type</label>
                    <input type="text" name="cargo_type" value="{{ old('cargo_type', $shipment->cargo_type) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <!-- Weight -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Weight (kg)</label>
                    <input type="number" step="0.01" name="weight" value="{{ old('weight', $shipment->weight) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <!-- Volume -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Volume (CBM)</label>
                    <input type="number" step="0.01" name="volume" value="{{ old('volume', $shipment->volume) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Departure -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Expected Departure</label>
                    <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', $shipment->expected_departure->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <!-- Expected Arrival -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Expected Arrival</label>
                    <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', $shipment->expected_arrival->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <!-- Estimated Arrival -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Calculated ETA</label>
                    <input type="datetime-local" name="estimated_arrival" value="{{ old('estimated_arrival', $shipment->estimated_arrival->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Handling Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">{{ old('notes', $shipment->notes) }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('shipments.show', $shipment->id) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg shadow-sm transition">
                    Update Shipment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
