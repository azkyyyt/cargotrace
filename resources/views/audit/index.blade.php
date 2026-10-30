@extends('layouts.app')

@section('title', 'System Audit Trail')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Security &amp; Operations Audit Trail</h1>
            <p class="text-xs text-slate-500 mt-1">Immutable security log of changes, status transitions, and user actions (Admin Only &bull; Slide 10)</p>
        </div>
    </div>

    <!-- Search/Filter -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Action</label>
                <input type="text" name="action" value="{{ request('action') }}" placeholder="CREATED, STATUS_CHANGE, etc." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
            </div>
            <div>
                <label class="block font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Entity Type</label>
                <input type="text" name="auditable_type" value="{{ request('auditable_type') }}" placeholder="Shipment, Carrier, Exception..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-4 rounded-lg shadow-sm transition">
                    Filter Logs
                </button>
                @if(request()->hasAny(['action', 'auditable_type']))
                    <a href="{{ route('audit.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 px-3 rounded-lg font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Actor</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Target Entity</th>
                        <th class="py-3 px-4">IP Address / Client</th>
                        <th class="py-3 px-4">Changes / Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($log->user)
                                    <div class="font-bold text-slate-900">{{ $log->user->name }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase font-semibold">{{ $log->user->role }}</div>
                                @else
                                    <span class="text-slate-400 italic">System / Anonymous</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="bg-slate-800 text-amber-400 font-mono font-bold px-2 py-0.5 rounded text-[10px]">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px]">
                                <span class="text-sky-700 font-semibold">{{ class_basename($log->auditable_type) }}</span>
                                <span class="text-slate-400">#{{ $log->auditable_id }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[10px]">
                                <div>{{ $log->ip_address }}</div>
                                <div class="truncate max-w-[150px] text-slate-400" title="{{ $log->user_agent }}">{{ $log->user_agent }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @if($log->new_values)
                                    <details class="cursor-pointer">
                                        <summary class="text-sky-600 hover:underline font-medium text-[11px]">View JSON payload</summary>
                                        <pre class="mt-1 p-2 bg-slate-900 text-emerald-400 rounded text-[10px] overflow-x-auto max-w-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection
