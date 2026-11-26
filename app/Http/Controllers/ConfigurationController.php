<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Shipment;
use App\Services\AuditService;
use App\Services\EtaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfigurationController extends Controller
{
    protected EtaService $etaService;

    public function __construct(EtaService $etaService)
    {
        $this->etaService = $etaService;
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Forbidden. System configuration is restricted to administrators.');
        }

        $config = [
            'group_delay_threshold_hours' => Setting::getValue('group_delay_threshold_hours', config('services.cargo.group_threshold', 18)),
            'delay_threshold_low_hours' => Setting::getValue('delay_threshold_low_hours', config('services.cargo.delay_threshold_low', 6)),
            'delay_threshold_medium_hours' => Setting::getValue('delay_threshold_medium_hours', config('services.cargo.delay_threshold_medium', 24)),
            'active_group_scenario' => Setting::getValue('active_group_scenario', 'Group B (Asia -> North America / International)'),
        ];

        return view('admin.settings.index', compact('config'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Forbidden.');
        }

        $validated = $request->validate([
            'group_delay_threshold_hours' => ['required', 'integer', 'min:1', 'max:240'],
            'delay_threshold_low_hours' => ['required', 'integer', 'min:1', 'max:48'],
            'delay_threshold_medium_hours' => ['required', 'integer', 'min:2', 'max:120', 'gt:delay_threshold_low_hours'],
            'active_group_scenario' => ['required', 'string', 'max:150'],
            'recalculate_shipments' => ['nullable', 'boolean'],
        ]);

        $oldConfig = [
            'group_delay_threshold_hours' => Setting::getValue('group_delay_threshold_hours', 18),
            'delay_threshold_low_hours' => Setting::getValue('delay_threshold_low_hours', 6),
            'delay_threshold_medium_hours' => Setting::getValue('delay_threshold_medium_hours', 24),
            'active_group_scenario' => Setting::getValue('active_group_scenario', 'Group B'),
        ];

        Setting::setValue(
            'group_delay_threshold_hours',
            $validated['group_delay_threshold_hours'],
            'Group Delay Threshold (Hours)',
            'Primary delay threshold parameter configured per scenario group (12h/18h/24h).'
        );
        Setting::setValue(
            'delay_threshold_low_hours',
            $validated['delay_threshold_low_hours'],
            'Low Severity Delay Upper Limit (Hours)',
            'Delays up to this duration are classified as LOW severity.'
        );
        Setting::setValue(
            'delay_threshold_medium_hours',
            $validated['delay_threshold_medium_hours'],
            'Medium Severity Delay Upper Limit (Hours)',
            'Delays above Low limit up to this duration are classified as MEDIUM; beyond this is HIGH.'
        );
        Setting::setValue(
            'active_group_scenario',
            $validated['active_group_scenario'],
            'Active Group Scenario',
            'Active corridor & evaluation scenario descriptor.'
        );

        $recalculatedCount = 0;
        if ($request->boolean('recalculate_shipments')) {
            $activeShipments = Shipment::whereNotIn('current_status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED])->get();
            foreach ($activeShipments as $shipment) {
                $this->etaService->applyEvaluation($shipment);
                $shipment->save();
                $recalculatedCount++;
            }
        }

        AuditService::log('SYSTEM_CONFIG_UPDATED', $user, $oldConfig, $validated, $user);

        $msg = 'System delay thresholds and group scenario parameters updated successfully.';
        if ($recalculatedCount > 0) {
            $msg .= " Re-evaluated {$recalculatedCount} active shipments.";
        }

        return redirect()->route('settings.index')->with('success', $msg);
    }
}
