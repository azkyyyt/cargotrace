<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    protected EtaService $etaService;

    public function __construct(EtaService $etaService)
    {
        $this->etaService = $etaService;
    }

    /**
     * Create a new shipment with items, initial tracking event, and audit log.
     */
    public function createShipment(array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($data, $actor) {
            // Auto generate shipment number if not provided
            if (empty($data['shipment_number'])) {
                $data['shipment_number'] = 'CT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            }

            // Defaults
            $data['current_status'] = $data['current_status'] ?? Shipment::STATUS_CREATED;
            if (empty($data['estimated_arrival'])) {
                $data['estimated_arrival'] = $data['expected_arrival'];
            }

            $shipment = new Shipment($data);
            $this->etaService->applyEvaluation($shipment);
            $shipment->save();

            // Create items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $shipment->items()->create([
                        'item_description' => $item['item_description'] ?? 'Standard Cargo Item',
                        'quantity' => $item['quantity'] ?? 1,
                        'weight' => $item['weight'] ?? 10.0,
                        'volume' => $item['volume'] ?? 0.5,
                        'hs_code' => $item['hs_code'] ?? null,
                        'package_type' => $item['package_type'] ?? 'BOX',
                    ]);
                }
            } else {
                // Ensure at least one item entry
                $shipment->items()->create([
                    'item_description' => $shipment->cargo_type . ' Package',
                    'quantity' => 1,
                    'weight' => $shipment->weight,
                    'volume' => $shipment->volume,
                    'package_type' => 'PALLET',
                ]);
            }

            // Create initial tracking event
            $shipment->trackingEvents()->create([
                'event_type' => 'STATUS_UPDATE',
                'status' => $shipment->current_status,
                'location_id' => $shipment->origin_id,
                'location_name' => $shipment->origin ? $shipment->origin->name : 'Origin Facility',
                'latitude' => $shipment->origin ? $shipment->origin->latitude : null,
                'longitude' => $shipment->origin ? $shipment->origin->longitude : null,
                'description' => "Shipment created in system with status {$shipment->current_status}.",
                'event_time' => now(),
                'created_by' => $actor ? $actor->id : null,
            ]);

            AuditService::log('CREATED', $shipment, null, $shipment->toArray(), $actor);

            // Notify customer
            if ($shipment->customer) {
                NotificationService::send(
                    $shipment->customer,
                    "Shipment Created: {$shipment->shipment_number}",
                    "Your shipment {$shipment->shipment_number} ({$shipment->cargo_type}) has been created.",
                    'info',
                    "/shipments/{$shipment->id}"
                );
            }

            return $shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'items', 'trackingEvents']);
        });
    }

    /**
     * Update an existing shipment.
     */
    public function updateShipment(Shipment $shipment, array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $oldValues = $shipment->toArray();

            // Validate status change if requested
            if (isset($data['current_status']) && $data['current_status'] !== $shipment->current_status) {
                $this->validateStatusTransition($shipment, $data['current_status']);
            }

            $shipment->fill($data);

            // Re-evaluate delay if ETA or expected dates updated
            if (isset($data['estimated_arrival']) || isset($data['expected_arrival'])) {
                $this->etaService->applyEvaluation($shipment);
            }

            $shipment->save();

            AuditService::log('UPDATED', $shipment, $oldValues, $shipment->toArray(), $actor);

            return $shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'items', 'trackingEvents']);
        });
    }

    /**
     * Transition shipment status via lifecycle state machine.
     */
    public function transitionStatus(Shipment $shipment, string $targetStatus, ?User $actor = null, ?string $reason = null): Shipment
    {
        $this->validateStatusTransition($shipment, $targetStatus);

        return DB::transaction(function () use ($shipment, $targetStatus, $actor, $reason) {
            $oldValues = $shipment->toArray();
            $shipment->current_status = $targetStatus;

            if ($targetStatus === Shipment::STATUS_IN_TRANSIT && !$shipment->actual_departure) {
                $shipment->actual_departure = now();
            }

            if ($targetStatus === Shipment::STATUS_DELIVERED && !$shipment->actual_arrival) {
                $shipment->actual_arrival = now();
                $shipment->current_location_id = $shipment->destination_id;
            }

            $shipment->save();

            // Append tracking event
            $location = ($targetStatus === Shipment::STATUS_DELIVERED) ? $shipment->destination : ($shipment->currentLocation ?? $shipment->origin);
            $shipment->trackingEvents()->create([
                'event_type' => 'STATUS_UPDATE',
                'status' => $targetStatus,
                'location_id' => $location ? $location->id : null,
                'location_name' => $location ? $location->name : 'En Route',
                'latitude' => $location ? $location->latitude : null,
                'longitude' => $location ? $location->longitude : null,
                'description' => $reason ?: "Shipment status changed to {$targetStatus}.",
                'event_time' => now(),
                'created_by' => $actor ? $actor->id : null,
            ]);

            AuditService::log('STATUS_CHANGE', $shipment, $oldValues, ['current_status' => $targetStatus, 'reason' => $reason], $actor);

            // Notify customer
            if ($shipment->customer) {
                $type = $targetStatus === Shipment::STATUS_DELIVERED ? 'success' : 'info';
                NotificationService::send(
                    $shipment->customer,
                    "Shipment Status Update: {$shipment->shipment_number}",
                    "Your shipment is now {$targetStatus}. " . ($reason ?? ''),
                    $type,
                    "/shipments/{$shipment->id}"
                );
            }

            return $shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'trackingEvents']);
        });
    }

    /**
     * Validate status transition rules according to business requirements.
     */
    public function validateStatusTransition(Shipment $shipment, string $targetStatus): void
    {
        if ($shipment->current_status === $targetStatus) {
            return;
        }

        // DELIVERED or CANCELLED is terminal: strictly forbidden to revert
        if ($shipment->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition from terminal status {$shipment->current_status}. Reversal is forbidden by business rules."]
            ]);
        }

        if (!$shipment->canTransitionTo($targetStatus)) {
            $allowed = implode(', ', Shipment::STATUS_FLOW[$shipment->current_status] ?? ['none']);
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from '{$shipment->current_status}' to '{$targetStatus}'. Allowed next states are: [{$allowed}]."]
            ]);
        }
    }

    /**
     * Delete shipment (soft delete).
     */
    public function deleteShipment(Shipment $shipment, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($shipment, $actor) {
            AuditService::log('DELETED', $shipment, $shipment->toArray(), null, $actor);
            return $shipment->delete();
        });
    }
}
