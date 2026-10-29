@extends('layouts.app')

@section('title', 'Sign In - CargoTrace')

@section('content')
<div class="max-w-md mx-auto my-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">CargoTrace Platform</h1>
        <p class="text-sm text-slate-500 mt-1">Global Shipment Visibility &amp; Tracking Platform</p>
    </div>

    <!-- Quick Demo Access Banner (Portfolio Feature) -->
    <div class="bg-gradient-to-r from-slate-900 to-sky-950 text-white rounded-xl p-4 mb-6 shadow-md border border-slate-800">
        <div class="flex items-center space-x-2 text-xs font-bold text-sky-400 mb-2 uppercase tracking-wider">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Examiner / Evaluator 1-Click Demo Login</span>
        </div>
        <p class="text-xs text-slate-300 mb-3">Instant role-based authorization test accounts (no typing needed):</p>
        <div class="grid grid-cols-3 gap-2 text-center text-xs">
            <a href="{{ route('demo.login', 'admin') }}" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold py-2 px-2 rounded-lg transition shadow-sm">
                👑 Admin
                <div class="text-[9px] font-normal text-slate-800">Full Control</div>
            </a>
            <a href="{{ route('demo.login', 'operator') }}" class="bg-sky-500 hover:bg-sky-400 text-white font-bold py-2 px-2 rounded-lg transition shadow-sm">
                🚚 Operator
                <div class="text-[9px] font-normal text-sky-100">Shipments &amp; Logs</div>
            </a>
            <a href="{{ route('demo.login', 'customer') }}" class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-2 px-2 rounded-lg transition shadow-sm">
                🏢 Customer
                <div class="text-[9px] font-normal text-emerald-100">Budi Santoso</div>
            </a>
        </div>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Standard Account Login</h2>
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', 'admin@cargotrace.test') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" id="password" value="password" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center space-x-2 text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>Remember me</span>
                </label>
                <span class="text-slate-400">Default password: <code class="text-sky-700 font-bold">password</code></span>
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 rounded-lg text-sm shadow-sm transition">
                Sign In to Platform
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Don't have an account? <a href="{{ route('register') }}" class="font-semibold text-sky-600 hover:text-sky-500">Register as Customer</a>
        </div>
    </div>
</div>
@endsection
