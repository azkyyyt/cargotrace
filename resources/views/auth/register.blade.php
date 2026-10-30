@extends('layouts.app')

@section('title', 'Register - CargoTrace')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Customer Account</h1>
        <p class="text-sm text-slate-500 mt-1">Track your international consignments in real time</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>

            <div>
                <label for="company" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Company / Organization</label>
                <input type="text" name="company" id="company" value="{{ old('company') }}" placeholder="e.g. PT Export Jaya" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="+62 812-xxxx-xxxx" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" id="password" required minlength="8" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>

            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2.5 rounded-lg text-sm shadow-sm transition">
                Complete Registration
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Already have an account? <a href="{{ route('login') }}" class="font-semibold text-sky-600 hover:text-sky-500">Sign In</a>
        </div>
    </div>
</div>
@endsection
