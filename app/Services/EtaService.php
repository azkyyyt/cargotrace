<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Shipment;
use Carbon\Carbon;

class EtaService
{
    /**
     * Determine delay calculation for a shipment based on estimated vs expected arrival.
     *
     * @param Shipment $shipment
     * @param Carbon|null $newEstimatedArrival
     * @param string|null $reason
     * @return array
     */
    public function evaluateEtaAndDelay(Shipment $shipment, ?Carbon $newEstimatedArrival = null, ?string $reason = null): array
    {
        $estimated = $newEstimatedArrival ?? Carbon::parse($shipment->estimated_arrival);
        $expected = Carbon::parse($shipment->expected_arrival);

        $delayHours = 0;
        $isDelayed = false;
        $severity = 'NONE';
        $status = 'ON_TIME';

        // If estimated arrival is after expected arrival
        if ($estimated->gt($expected)) {
            $delayHours = (int) $expected->diffInHours($estimated);
            $isDelayed = $delayHours > 0;
        }

        if ($isDelayed) {
            $status = 'DELAYED';
            $lowThreshold = (int) Setting::getValue('delay_threshold_low_hours', config('services.cargo.delay_threshold_low', 6));
            $medThreshold = (int) Setting::getValue('delay_threshold_medium_hours', config('services.cargo.delay_threshold_medium', 24));
            $groupThreshold = (int) Setting::getValue('group_delay_threshold_hours', config('services.cargo.group_threshold', 18));

            if ($delayHours <= $lowThreshold) {
                $severity = 'LOW';
            } elseif ($delayHours <= $medThreshold) {
                $severity = 'MEDIUM';
            } else {
                $severity = 'HIGH';
            }

            $effectiveReason = $reason ?: "Estimated arrival ({$estimated->format('Y-m-d H:i')}) exceeds expected arrival ({$expected->format('Y-m-d H:i')}) by {$delayHours}h [Rules: Low <= {$lowThreshold}h, Med <= {$medThreshold}h | Group Threshold: {$groupThreshold}h].";
        } else {
            $effectiveReason = $reason ?: 'Shipment running on schedule.';
        }

        return [
            'estimated_arrival' => $estimated,
            'expected_arrival' => $expected,
            'delay_status' => $status,
            'delay_duration_hours' => $delayHours,
            'delay_severity' => $severity,
            'delay_reason' => $effectiveReason,
        ];
    }

    /**
     * Apply delay calculation directly onto a shipment.
     */
    public function applyEvaluation(Shipment $shipment, ?Carbon $newEstimatedArrival = null, ?string $reason = null): Shipment
    {
        $eval = $this->evaluateEtaAndDelay($shipment, $newEstimatedArrival, $reason);

        $shipment->estimated_arrival = $eval['estimated_arrival'];
        $shipment->delay_status = $eval['delay_status'];
        $shipment->delay_duration_hours = $eval['delay_duration_hours'];
        $shipment->delay_severity = $eval['delay_severity'];
        $shipment->delay_reason = $eval['delay_reason'];

        return $shipment;
    }
}
