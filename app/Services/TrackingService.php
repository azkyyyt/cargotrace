<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    protected ShipmentService $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * Add a historical tracking event to a shipment.
     */
    public function addEvent(Shipment $shipment, array $data, ?User $actor = null): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $eventTime = isset($data['event_time']) ? Carbon::parse($data['event_time']) : now();

            // Validate that event timestamp is not unreasonably in the future
            if ($eventTime->gt(now()->addHours(2))) {
                throw ValidationException::withMessages([
                    'event_time' => ['Tracking event timestamp cannot be in the future.']
                ]);
            }

            // Extract location info
            $locationId = $data['location_id'] ?? null;
            $locationName = $data['location_name'] ?? 'Transit Hub';
            $lat = $data['latitude'] ?? null;
            $lng = $data['longitude'] ?? null;

            if ($locationId && (!$lat || !$lng)) {
                $loc = \App\Models\Location::find($locationId);
                if ($loc) {
                    $locationName = $loc->name . ', ' . $loc->city;
                    $lat = $loc->latitude;
                    $lng = $loc->longitude;
                }
            }

            $eventStatus = $data['status'] ?? $shipment->current_status;

            // If new status implies a valid lifecycle advance, transition shipment status
            if ($eventStatus !== $shipment->current_status && in_array($eventStatus, array_keys(Shipment::STATUS_FLOW), true)) {
                $this->shipmentService->validateStatusTransition($shipment, $eventStatus);
                $shipment->current_status = $eventStatus;
            }

            // Update current location on shipment
            if ($locationId) {
                $shipment->current_location_id = $locationId;
            }
            $shipment->save();

            // Save historical tracking event (never overwriting old ones)
            $event = $shipment->trackingEvents()->create([
                'event_type' => $data['event_type'] ?? 'CHECKPOINT',
                'status' => $eventStatus,
                'location_id' => $locationId,
                'location_name' => $locationName,
                'latitude' => $lat,
                'longitude' => $lng,
                'description' => $data['description'] ?? "Tracking update: {$eventStatus} at {$locationName}",
                'event_time' => $eventTime,
                'created_by' => $actor ? $actor->id : null,
            ]);

            AuditService::log('TRACKING_EVENT_ADDED', $event, null, $event->toArray(), $actor);

            // Notify customer of significant milestone
            if ($shipment->customer) {
                NotificationService::send(
                    $shipment->customer,
                    "Tracking Event: {$shipment->shipment_number}",
                    "New checkpoint: {$locationName} - {$event->description}",
                    'info',
                    "/shipments/{$shipment->id}"
                );
            }

            return $event;
        });
    }

    /**
     * Get sorted timeline for shipment.
     */
    public function getTimeline(Shipment $shipment)
    {
        return $shipment->trackingEvents()
            ->with(['location', 'creator'])
            ->orderBy('event_time', 'asc')
            ->get();
    }
}
