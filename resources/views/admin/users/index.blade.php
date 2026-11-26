@extends('layouts.app')

@section('title', 'User & Role Management')

@section('content')
<div class="space-y-6" x-data="userManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">User &amp; Role Management</h1>
            <p class="text-xs text-slate-500 mt-1">Manage system administrators, logistics operators, and customer accounts (RBAC Control)</p>
        </div>
        <button type="button" @click="openCreateModal()" class="inline-flex items-center space-x-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
            <span>+ Add User Account</span>
        </button>
    </div>

    <!-- Search & Role Filter -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row gap-3 text-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or company..." class="flex-1 px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
            <select name="role" class="px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>Logistics Operator</option>
                <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer</option>
            </select>
            <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white font-semibold px-4 py-2 rounded-lg transition">Filter</button>
            <a href="{{ route('users.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 rounded-lg transition text-center">Reset</a>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                    <tr>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Company &amp; Contact</th>
                        <th class="py-3 px-4 text-center">Shipments</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $u->name }}</div>
                                <div class="text-slate-500 font-mono text-[11px]">{{ $u->email }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $roleStyle = match($u->role) {
                                        'admin' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'operator' => 'bg-sky-100 text-sky-800 border-sky-200',
                                        default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $roleStyle }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800">{{ $u->company ?: '-' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $u->phone ?: 'No phone' }}</div>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-slate-700">
                                {{ $u->shipments_count }}
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <button type="button"
                                        @click="openEditModal({{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'company' => $u->company, 'phone' => $u->phone]) }})"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold transition">
                                    Edit
                                </button>
                                @if((int)$u->id !== (int)Auth::id())
                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove user {{ $u->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded font-semibold transition">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">No users found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Create / Edit User Modal -->
    <div x-cloak x-show="modalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200" @click.away="modalOpen = false">
            <h3 class="text-base font-bold text-slate-900 mb-1" x-text="isEditing ? 'Edit User Account' : 'Create New User'"></h3>
            <p class="text-xs text-slate-500 mb-4">Assign exact role permissions for RBAC enforcement.</p>

            <form :action="isEditing ? '/users/' + form.id : '{{ route('users.store') }}'" method="POST" class="space-y-3 text-xs">
                @csrf
                <template x-if="isEditing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" name="email" x-model="form.email" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">System Role *</label>
                    <select name="role" x-model="form.role" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="customer">Customer (View Own Shipments &amp; Tracking)</option>
                        <option value="operator">Logistics Operator (Create/Update Shipments, Tracking &amp; Exceptions)</option>
                        <option value="admin">Administrator (Users, System Config, Carriers, Locations &amp; Audit)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Company</label>
                        <input type="text" name="company" x-model="form.company" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" x-model="form.phone" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">
                        <span x-text="isEditing ? 'New Password (leave blank to keep current)' : 'Password (min 8 chars) *'"></span>
                    </label>
                    <input type="password" name="password" :required="!isEditing" minlength="8" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-3 py-2 bg-slate-100 text-slate-700 rounded-lg font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg font-semibold shadow-sm" x-text="isEditing ? 'Update User' : 'Create User'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function userManager() {
        return {
            modalOpen: false,
            isEditing: false,
            form: {
                id: null,
                name: '',
                email: '',
                role: 'customer',
                company: '',
                phone: ''
            },
            openCreateModal() {
                this.isEditing = false;
                this.form = { id: null, name: '', email: '', role: 'customer', company: '', phone: '' };
                this.modalOpen = true;
            },
            openEditModal(user) {
                this.isEditing = true;
                this.form = {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    role: user.role,
                    company: user.company || '',
                    phone: user.phone || ''
                };
                this.modalOpen = true;
            }
        };
    }
</script>
@endpush
