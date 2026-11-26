@extends('layouts.app')

@section('title', 'Carrier Management')

@section('content')
<div class="space-y-6" x-data="carrierManager()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Carriers &amp; Logistics Operators</h1>
            <p class="text-xs text-slate-500 mt-1">Manage global shipping lines, air cargo fleets, and rail networks (Admin Only)</p>
        </div>
        @if(Auth::user()->isAdmin())
            <button type="button" @click="openAddModal()" class="inline-flex items-center space-x-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Carrier</span>
            </button>
        @endif
    </div>

    <!-- Carriers Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Code</th>
                        <th class="py-3 px-4">Carrier Name</th>
                        <th class="py-3 px-4">Mode</th>
                        <th class="py-3 px-4">Contact</th>
                        <th class="py-3 px-4">Active Shipments</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($carriers as $car)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-sky-700">{{ $car->code }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $car->name }}</div>
                                @if($car->website)
                                    <a href="{{ $car->website }}" target="_blank" class="text-[10px] text-sky-600 hover:underline">{{ $car->website }}</a>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="bg-slate-100 px-2 py-0.5 rounded font-mono font-semibold">{{ $car->transport_mode }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div>{{ $car->contact_email ?: '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $car->contact_phone ?: '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                {{ $car->shipments_count }} shipments
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $car->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $car->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                @if(Auth::user()->isAdmin())
                                    <button type="button" @click="openEditModal({{ json_encode(['id' => $car->id, 'code' => $car->code, 'name' => $car->name, 'transport_mode' => $car->transport_mode, 'contact_email' => $car->contact_email, 'contact_phone' => $car->contact_phone, 'website' => $car->website]) }})" class="text-xs bg-sky-50 hover:bg-sky-100 text-sky-700 font-semibold px-2.5 py-1 rounded transition">
                                        Edit
                                    </button>
                                    <form action="{{ route('carriers.toggle', $car->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded transition">
                                            {{ $car->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    @if($car->shipments_count === 0)
                                        <form action="{{ route('carriers.destroy', $car->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete carrier {{ $car->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 px-2 py-1 rounded transition">Delete</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">No carriers registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $carriers->links() }}
        </div>
    </div>

    <!-- Modal: Add / Edit Carrier -->
    <div x-cloak x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="modalOpen = false">
            <h3 class="text-base font-bold text-slate-900 mb-1" x-text="isEditing ? 'Edit Logistics Carrier' : 'Add Logistics Carrier'"></h3>
            <p class="text-xs text-slate-500 mb-4">Register or update shipping provider master data</p>

            <form :action="isEditing ? '/carriers/' + form.id : '{{ route('carriers.store') }}'" method="POST" class="space-y-3 text-xs">
                @csrf
                <template x-if="isEditing">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Carrier Code *</label>
                        <input type="text" name="code" x-model="form.code" required placeholder="e.g. HLC, CMA" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Mode *</label>
                        <select name="transport_mode" x-model="form.transport_mode" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                            <option value="SEA">SEA (Ocean Container)</option>
                            <option value="AIR">AIR (Air Cargo)</option>
                            <option value="ROAD">ROAD (Truck Freight)</option>
                            <option value="RAIL">RAIL (Rail Cargo)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Full Carrier Name *</label>
                    <input type="text" name="name" x-model="form.name" required placeholder="e.g. Hapag-Lloyd Shipping" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Email</label>
                        <input type="email" name="contact_email" x-model="form.contact_email" placeholder="ops@carrier.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Phone</label>
                        <input type="text" name="contact_phone" x-model="form.contact_phone" placeholder="+62 21-xxx" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Website URL</label>
                    <input type="url" name="website" x-model="form.website" placeholder="https://www.carrier.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-3 py-2 bg-slate-100 text-slate-700 font-semibold rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg shadow-sm" x-text="isEditing ? 'Update Carrier' : 'Save Carrier'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function carrierManager() {
        return {
            modalOpen: false,
            isEditing: false,
            form: {
                id: null,
                code: '',
                name: '',
                transport_mode: 'SEA',
                contact_email: '',
                contact_phone: '',
                website: ''
            },
            openAddModal() {
                this.isEditing = false;
                this.form = { id: null, code: '', name: '', transport_mode: 'SEA', contact_email: '', contact_phone: '', website: '' };
                this.modalOpen = true;
            },
            openEditModal(c) {
                this.isEditing = true;
                this.form = {
                    id: c.id,
                    code: c.code,
                    name: c.name,
                    transport_mode: c.transport_mode,
                    contact_email: c.contact_email || '',
                    contact_phone: c.contact_phone || '',
                    website: c.website || ''
                };
                this.modalOpen = true;
            }
        };
    }
</script>
@endpush
