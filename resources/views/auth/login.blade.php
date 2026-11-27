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
    <div class="bg-slate-900 text-white rounded-lg p-4 mb-6 shadow-sm border border-slate-800">
        <div class="flex items-center space-x-2 text-xs font-semibold text-sky-400 mb-2 uppercase tracking-wider">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Quick Evaluator Role Sign-In</span>
        </div>
        <p class="text-xs text-slate-300 mb-3">Instant role-based test accounts:</p>
        <div class="grid grid-cols-3 gap-2 text-center text-xs">
            <a href="{{ route('demo.login', 'admin') }}" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold py-2 px-2 rounded-md transition">
                Admin
                <div class="text-[10px] font-normal text-slate-800">Full Control</div>
            </a>
            <a href="{{ route('demo.login', 'operator') }}" class="bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2 px-2 rounded-md transition">
                Operator
                <div class="text-[10px] font-normal text-sky-100">Shipments &amp; Ops</div>
            </a>
            <a href="{{ route('demo.login', 'customer') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2 px-2 rounded-md transition">
                Customer
                <div class="text-[10px] font-normal text-emerald-100">Budi Santoso</div>
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
                <div class="relative">
                    <input type="password" name="password" id="password" value="password" required class="w-full px-3 py-2 pr-10 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                    <button type="button" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 focus:outline-none transition">
                        <svg id="eye-icon-open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eye-icon-closed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('toggle-password');
        const eyeOpen = document.getElementById('eye-icon-open');
        const eyeClosed = document.getElementById('eye-icon-closed');

        if (passwordInput && toggleButton && eyeOpen && eyeClosed) {
            toggleButton.addEventListener('click', function () {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                eyeOpen.classList.toggle('hidden', isHidden);
                eyeClosed.classList.toggle('hidden', !isHidden);
                toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
            });
        }
    });
</script>
@endsection
