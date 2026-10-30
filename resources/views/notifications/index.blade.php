@extends('layouts.app')

@section('title', 'Notifications Center')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Notification Center</h1>
            <p class="text-xs text-slate-500 mt-1">Real-time event alerts and supply chain updates</p>
        </div>
        <form action="{{ route('notifications.readAll') }}" method="POST">
            @csrf
            <button type="submit" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-2 rounded-lg transition">
                Mark All as Read
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
        @forelse($notifications as $n)
            @php
                $bgColor = $n->is_read ? 'bg-white' : 'bg-sky-50/40';
                $badgeBorder = match($n->type) {
                    'danger' => 'border-rose-500 text-rose-600',
                    'warning' => 'border-amber-500 text-amber-600',
                    'success' => 'border-emerald-500 text-emerald-600',
                    default => 'border-sky-500 text-sky-600',
                };
            @endphp
            <div class="p-4 {{ $bgColor }} hover:bg-slate-50 transition flex items-start justify-between gap-4">
                <div class="flex items-start space-x-3">
                    <div class="mt-1 h-2.5 w-2.5 rounded-full {{ $n->is_read ? 'bg-slate-300' : 'bg-sky-500' }}"></div>
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-slate-900">{{ $n->title }}</span>
                            <span class="text-[9px] uppercase font-mono font-bold px-1.5 py-0.2 border rounded {{ $badgeBorder }}">{{ $n->type }}</span>
                        </div>
                        <p class="text-xs text-slate-600">{{ $n->message }}</p>
                        <div class="text-[10px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                </div>

                <div class="flex items-center space-x-2 flex-shrink-0">
                    @if($n->link)
                        <a href="{{ $n->link }}" class="text-xs bg-sky-50 hover:bg-sky-100 text-sky-700 font-medium px-2.5 py-1 rounded transition">
                            View Consignment &rarr;
                        </a>
                    @endif
                    @if(!$n->is_read)
                        <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                            @csrf
                            <button type="submit" title="Mark as Read" class="text-slate-400 hover:text-slate-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 text-xs">
                No notifications to display.
            </div>
        @endforelse
    </div>

    <div class="p-2">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
