@extends('layouts.app')

@section('title', 'System Configuration & Delay Thresholds')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Configuration</h1>
        <p class="text-xs text-slate-500 mt-1">Configure deterministic ETA delay rules and group scenario thresholds (Administrator Only)</p>
    </div>

    <!-- Preset Scenarios Reference (Slide 23) -->
    <div class="bg-slate-900 text-white rounded-xl p-5 shadow-sm border border-slate-800 space-y-3">
        <div class="text-xs font-bold uppercase tracking-wider text-amber-400">Group Scenario Quick Templates (Slide 23)</div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <button type="button" onclick="applyScenario(12, 6, 24, 'Group A (Southeast Asia -> Europe | 12h Threshold)')" class="p-3 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <div class="font-bold text-sky-400">Group A Scenario</div>
                <div class="text-[11px] text-slate-300 mt-0.5">Threshold: 12 Hours</div>
                <div class="text-[10px] text-slate-400">Focus: Customs Hold / On-Time Rate</div>
            </button>
            <button type="button" onclick="applyScenario(18, 6, 24, 'Group B (Asia -> North America | 18h Threshold)')" class="p-3 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <div class="font-bold text-amber-400">Group B Scenario</div>
                <div class="text-[11px] text-slate-300 mt-0.5">Threshold: 18 Hours</div>
                <div class="text-[10px] text-slate-400">Focus: Weather Delay / Avg Duration</div>
            </button>
            <button type="button" onclick="applyScenario(24, 6, 24, 'Group C (Southeast Asia -> Australia | 24h Threshold)')" class="p-3 bg-slate-800 hover:bg-slate-700 rounded-lg text-left border border-slate-700 transition">
                <div class="font-bold text-emerald-400">Group C Scenario</div>
                <div class="text-[11px] text-slate-300 mt-0.5">Threshold: 24 Hours</div>
                <div class="text-[10px] text-slate-400">Focus: Documentation Issue / Resolution</div>
            </button>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Active Group Scenario Label *</label>
                <input type="text" id="active_group_scenario" name="active_group_scenario" value="{{ old('active_group_scenario', $config['active_group_scenario']) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Group Delay Threshold (Hours) *</label>
                    <input type="number" id="group_delay_threshold_hours" name="group_delay_threshold_hours" value="{{ old('group_delay_threshold_hours', $config['group_delay_threshold_hours']) }}" min="1" max="240" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500 font-mono font-bold">
                    <p class="text-[10px] text-slate-400 mt-1">Benchmark threshold per group (e.g., 12, 18, 24h).</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Low Severity Max (Hours) *</label>
                    <input type="number" id="delay_threshold_low_hours" name="delay_threshold_low_hours" value="{{ old('delay_threshold_low_hours', $config['delay_threshold_low_hours']) }}" min="1" max="48" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500 font-mono font-bold">
                    <p class="text-[10px] text-slate-400 mt-1">Default: 6h (0 &ndash; 6h = LOW).</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Medium Severity Max (Hours) *</label>
                    <input type="number" id="delay_threshold_medium_hours" name="delay_threshold_medium_hours" value="{{ old('delay_threshold_medium_hours', $config['delay_threshold_medium_hours']) }}" min="2" max="120" required class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-sky-500 font-mono font-bold">
                    <p class="text-[10px] text-slate-400 mt-1">Default: 24h (&gt;Low &ndash; 24h = MEDIUM; &gt;24h = HIGH).</p>
                </div>
            </div>

            <div class="p-3 bg-sky-50 border border-sky-200 rounded-lg flex items-start space-x-2.5">
                <input type="checkbox" id="recalculate_shipments" name="recalculate_shipments" value="1" checked class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <label for="recalculate_shipments" class="text-slate-700">
                    <strong class="block text-slate-900">Recalculate all active shipments immediately</strong>
                    <span class="text-[11px] text-slate-500">Automatically re-evaluates delay severity and reasons across non-terminal shipments using the new parameters.</span>
                </label>
            </div>

            <div class="flex justify-end pt-3 border-t border-slate-100">
                <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg shadow-sm transition">
                    Save System Configuration
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function applyScenario(groupHrs, lowHrs, medHrs, label) {
        document.getElementById('group_delay_threshold_hours').value = groupHrs;
        document.getElementById('delay_threshold_low_hours').value = lowHrs;
        document.getElementById('delay_threshold_medium_hours').value = medHrs;
        document.getElementById('active_group_scenario').value = label;
    }
</script>
@endpush
