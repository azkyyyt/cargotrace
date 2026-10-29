<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    protected EtaService $etaService;

    public function __construct(EtaService $etaService)
    {
        $this->etaService = $etaService;
    }

    /**
     * Raise a new shipment exception.
     */
    public function raiseException(Shipment $shipment, array $data, ?User $actor = null): ShipmentException
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $detectedAt = isset($data['detected_at']) ? Carbon::parse($data['detected_at']) : now();

            $exception = $shipment->exceptions()->create([
                'tracking_event_id' => $data['tracking_event_id'] ?? null,
                'exception_type' => $data['exception_type'],
                'severity' => $data['severity'] ?? 'MEDIUM',
                'status' => ShipmentException::STATUS_OPEN,
                'description' => $data['description'],
                'detected_at' => $detectedAt,
            ]);

            // Add corresponding tracking event to history
            $currentLoc = $shipment->currentLocation ?? $shipment->origin;
            $event = $shipment->trackingEvents()->create([
                'event_type' => 'EXCEPTION_FLAG',
                'status' => $shipment->current_status,
                'location_id' => $currentLoc ? $currentLoc->id : null,
                'location_name' => $currentLoc ? $currentLoc->name : 'Transit Point',
                'latitude' => $currentLoc ? $currentLoc->latitude : null,
                'longitude' => $currentLoc ? $currentLoc->longitude : null,
                'description' => "[EXCEPTION: {$exception->exception_type}] {$exception->description}",
                'event_time' => $detectedAt,
                'created_by' => $actor ? $actor->id : null,
            ]);

            $exception->tracking_event_id = $event->id;
            $exception->save();

            // If severity is HIGH or MEDIUM, bump estimated arrival and re-evaluate delay
            $addedHours = ($exception->severity === 'HIGH') ? 24 : (($exception->severity === 'MEDIUM') ? 8 : 2);
            $newEta = Carbon::parse($shipment->estimated_arrival)->addHours($addedHours);
            $this->etaService->applyEvaluation($shipment, $newEta, "Delay caused by exception: {$exception->exception_type} ({$exception->description})");
            $shipment->save();

            AuditService::log('EXCEPTION_RAISED', $exception, null, $exception->toArray(), $actor);

            // Notify customer and operators
            if ($shipment->customer) {
                NotificationService::send(
                    $shipment->customer,
                    "Disruption Reported: {$shipment->shipment_number}",
                    "Exception [{$exception->exception_type}] flagged on shipment {$shipment->shipment_number}. Our operations team is investigating.",
                    'danger',
                    "/shipments/{$shipment->id}"
                );
            }

            NotificationService::broadcastToOperatorsAndAdmins(
                "Urgent Exception: {$shipment->shipment_number}",
                "Exception [{$exception->exception_type}] with {$exception->severity} severity detected on {$shipment->shipment_number}.",
                'danger',
                "/shipments/{$shipment->id}"
            );

            return $exception;
        });
    }

    /**
     * Update exception status (e.g. to INVESTIGATING or RESOLVED).
     */
    public function updateExceptionStatus(ShipmentException $exception, string $newStatus, ?string $resolutionNotes = null, ?User $actor = null): ShipmentException
    {
        return DB::transaction(function () use ($exception, $newStatus, $resolutionNotes, $actor) {
            $oldValues = $exception->toArray();
            $exception->status = $newStatus;

            if ($resolutionNotes) {
                $exception->resolution_notes = $resolutionNotes;
            }

            if ($newStatus === ShipmentException::STATUS_RESOLVED) {
                $exception->resolved_at = now();
                $exception->resolved_by = $actor ? $actor->id : null;
            }

            $exception->save();

            // Also record a resolution tracking event
            if ($newStatus === ShipmentException::STATUS_RESOLVED) {
                $shipment = $exception->shipment;
                $currentLoc = $shipment->currentLocation ?? $shipment->origin;
                $shipment->trackingEvents()->create([
                    'event_type' => 'EXCEPTION_RESOLVED',
                    'status' => $shipment->current_status,
                    'location_id' => $currentLoc ? $currentLoc->id : null,
                    'location_name' => $currentLoc ? $currentLoc->name : 'Transit Point',
                    'latitude' => $currentLoc ? $currentLoc->latitude : null,
                    'longitude' => $currentLoc ? $currentLoc->longitude : null,
                    'description' => "[RESOLVED: {$exception->exception_type}] " . ($resolutionNotes ?: 'Exception resolved.'),
                    'event_time' => now(),
                    'created_by' => $actor ? $actor->id : null,
                ]);

                if ($shipment->customer) {
                    NotificationService::send(
                        $shipment->customer,
                        "Disruption Resolved: {$shipment->shipment_number}",
                        "The exception [{$exception->exception_type}] on your shipment has been successfully resolved.",
                        'success',
                        "/shipments/{$shipment->id}"
                    );
                }
            }

            AuditService::log('EXCEPTION_UPDATED', $exception, $oldValues, $exception->toArray(), $actor);

            return $exception;
        });
    }
}
