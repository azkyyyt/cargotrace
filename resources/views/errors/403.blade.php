@extends('layouts.app')

@section('title', '403 - Akses Ditolak')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white p-8 rounded-2xl border border-slate-200 shadow-xl text-center space-y-5">
        <div class="mx-auto w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center text-3xl shadow-inner border border-rose-100">
            🛡️
        </div>

        <div class="space-y-2">
            <span class="text-xs font-black uppercase tracking-widest text-rose-600 bg-rose-100/60 px-2.5 py-1 rounded-full font-mono">
                Error 403 &bull; Forbidden
            </span>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Akses Terbatas (Hak Akses Diperlukan)</h2>
            <p class="text-xs text-slate-500 leading-relaxed">
                {{ $exception->getMessage() ?: 'Halaman ini memerlukan hak akses otorisasi Administrator atau Logistics Operator untuk melakukan tindakan operasional.' }}
            </p>
        </div>

        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-2 justify-center">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-lg transition shadow-sm">
                &larr; Kembali ke Dashboard
            </a>
            <a href="{{ route('shipments.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                Daftar Pengiriman
            </a>
        </div>
    </div>
</div>
@endsection
