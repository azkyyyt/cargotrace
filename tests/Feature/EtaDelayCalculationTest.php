<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Services\EtaService;
use Carbon\Carbon;
use Tests\TestCase;

class EtaDelayCalculationTest extends TestCase
{
    protected EtaService $etaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etaService = app(EtaService::class);
    }

    public function test_evaluates_on_time_when_eta_matches_or_is_earlier_than_expected(): void
    {
        $expected = Carbon::parse('2026-11-10 12:00:00');
        $estimated = Carbon::parse('2026-11-10 10:00:00'); // 2 hours early

        $shipment = new Shipment([
            'expected_arrival' => $expected,
            'estimated_arrival' => $estimated,
        ]);

        $eval = $this->etaService->evaluateEtaAndDelay($shipment);

        $this->assertEquals('ON_TIME', $eval['delay_status']);
        $this->assertEquals(0, $eval['delay_duration_hours']);
        $this->assertEquals('NONE', $eval['delay_severity']);
    }

    public function test_evaluates_low_severity_delay_under_6_hours(): void
    {
        $expected = Carbon::parse('2026-11-10 12:00:00');
        $estimated = Carbon::parse('2026-11-10 16:00:00'); // 4 hours late

        $shipment = new Shipment([
            'expected_arrival' => $expected,
            'estimated_arrival' => $estimated,
        ]);

        $eval = $this->etaService->evaluateEtaAndDelay($shipment);

        $this->assertEquals('DELAYED', $eval['delay_status']);
        $this->assertEquals(4, $eval['delay_duration_hours']);
        $this->assertEquals('LOW', $eval['delay_severity']);
    }

    public function test_evaluates_medium_severity_delay_between_6_and_24_hours(): void
    {
        $expected = Carbon::parse('2026-11-10 12:00:00');
        $estimated = Carbon::parse('2026-11-11 02:00:00'); // 14 hours late

        $shipment = new Shipment([
            'expected_arrival' => $expected,
            'estimated_arrival' => $estimated,
        ]);

        $eval = $this->etaService->evaluateEtaAndDelay($shipment);

        $this->assertEquals('DELAYED', $eval['delay_status']);
        $this->assertEquals(14, $eval['delay_duration_hours']);
        $this->assertEquals('MEDIUM', $eval['delay_severity']);
    }

    public function test_evaluates_high_severity_delay_over_24_hours(): void
    {
        $expected = Carbon::parse('2026-11-10 12:00:00');
        $estimated = Carbon::parse('2026-11-12 12:00:00'); // 48 hours late

        $shipment = new Shipment([
            'expected_arrival' => $expected,
            'estimated_arrival' => $estimated,
        ]);

        $eval = $this->etaService->evaluateEtaAndDelay($shipment);

        $this->assertEquals('DELAYED', $eval['delay_status']);
        $this->assertEquals(48, $eval['delay_duration_hours']);
        $this->assertEquals('HIGH', $eval['delay_severity']);
    }
}
