@extends('layouts.app')

@section('title', 'External API Integrations')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">External API Services Workbench</h1>
        <p class="text-xs text-slate-500 mt-1">Live integration testing with OpenStreetMap Nominatim (Geocoding) &amp; Open-Meteo (Weather Forecast) &bull; Slide 9</p>
    </div>

    <!-- Interactive Test Form -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-3">Live Geocode &amp; Weather Query</h3>
        <form method="GET" action="{{ route('integrations.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="location" value="{{ $geoQuery }}" required placeholder="Enter port, city or landmark (e.g. Port of Rotterdam, Tokyo, Hamburg)" class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-xs outline-none focus:ring-2 focus:ring-sky-500">
            <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white font-bold px-5 py-2 rounded-lg text-xs transition shadow-sm">
                Query APIs
            </button>
        </form>
    </div>

    @if($geoResult)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Geocoding Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        <span>Geocoding Service (Nominatim)</span>
                    </h4>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $geoResult['success'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $geoResult['success'] ? 'HTTP 200 OK' : 'FALLBACK APPLIED' }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Resolved Location Name:</span>
                        <span class="font-bold text-slate-800">{{ $geoResult['name'] }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Latitude</span>
                            <span class="font-mono font-bold text-sky-700">{{ $geoResult['latitude'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Longitude</span>
                            <span class="font-mono font-bold text-sky-700">{{ $geoResult['longitude'] }}</span>
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-400">
                        Source provider: <strong>{{ $geoResult['source'] }}</strong>
                    </div>
                </div>
            </div>

            <!-- Weather Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                        <span>Live Weather API (Open-Meteo)</span>
                    </h4>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $weatherResult && $weatherResult['success'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $weatherResult && $weatherResult['success'] ? 'HTTP 200 OK' : 'CACHED' }}
                    </span>
                </div>

                @if($weatherResult)
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-slate-400 block text-[10px]">Observed Condition:</span>
                                <span class="font-bold text-slate-800 text-sm">{{ $weatherResult['condition'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block text-[10px]">Ambient Temp:</span>
                                <span class="text-2xl font-black text-sky-600">{{ $weatherResult['temperature'] }}&deg;C</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100 flex items-center justify-between">
                            <span>Surface Wind Velocity</span>
                            <span class="font-mono font-bold text-slate-700">{{ $weatherResult['windspeed'] }} km/h</span>
                        </div>

                        <div class="text-[10px] text-slate-400">
                            Source provider: <strong>{{ $weatherResult['source'] }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Technical Specification Callout (Slide 9 & 15) -->
    <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 text-xs text-slate-600 space-y-2">
        <h4 class="font-bold text-slate-800">Architecture &amp; Resilience Guarantees</h4>
        <ul class="list-disc list-inside space-y-1 text-[11px] text-slate-500">
            <li>Requests are dispatched via <code>Illuminate\Support\Facades\Http</code> with rigorous 4-second timeout boundaries.</li>
            <li>Connection timeouts, network unreachability, and malformed responses are captured by try/catch and fallback gracefully without throwing 500 errors.</li>
            <li>Zero hardcoded API secrets: keys and endpoint roots reside strictly in <code>.env</code> / <code>config/services.php</code>.</li>
        </ul>
    </div>
</div>
@endsection
