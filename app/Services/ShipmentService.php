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

            // Compute baseline accounting metrics if not provided
            $weight = (float) ($data['weight'] ?? 0);
            if (empty($data['commodity_price_usd'])) {
                $unitRate = match (strtolower($data['cargo_type'] ?? '')) {
                    'rice' => 580.00,
                    'wheat' => 275.00,
                    'corn' => 215.00,
                    'soybeans' => 445.00,
                    default => 250.00,
                };
                $data['commodity_price_usd'] = round($weight * $unitRate, 2);
            }
            $data['freight_charge_usd'] = $data['freight_charge_usd'] ?? round($weight * 45.00, 2);
            $data['vessel_fee_usd'] = $data['vessel_fee_usd'] ?? round($weight * 28.00, 2);
            $data['gross_amount_usd'] = $data['gross_amount_usd'] ?? round($data['commodity_price_usd'] + $data['freight_charge_usd'], 2);
            $data['net_revenue_usd'] = $data['net_revenue_usd'] ?? max(0, round($data['gross_amount_usd'] - $data['vessel_fee_usd'], 2));
            $data['payment_status'] = $data['payment_status'] ?? 'PAID';
            if ($data['payment_status'] === 'PAID' && empty($data['paid_at'])) {
                $data['paid_at'] = now();
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
            $statusChanged = isset($data['current_status']) && $data['current_status'] !== $shipment->current_status;
            if ($statusChanged) {
                $this->validateStatusTransition($shipment, $data['current_status']);
            }

            $shipment->fill($data);

            if ($statusChanged) {
                if ($shipment->current_status === Shipment::STATUS_IN_TRANSIT && !$shipment->actual_departure) {
                    $shipment->actual_departure = now();
                }
                if ($shipment->current_status === Shipment::STATUS_DELIVERED && !$shipment->actual_arrival) {
                    $shipment->actual_arrival = now();
                    $shipment->current_location_id = $shipment->destination_id;
                }
            }

            // Re-evaluate delay if ETA or expected dates updated
            if (isset($data['estimated_arrival']) || isset($data['expected_arrival'])) {
                $this->etaService->applyEvaluation($shipment);
            }

            $shipment->save();

            if ($statusChanged) {
                $location = ($shipment->current_status === Shipment::STATUS_DELIVERED) ? $shipment->destination : ($shipment->currentLocation ?? $shipment->origin);
                $shipment->trackingEvents()->create([
                    'event_type' => 'STATUS_UPDATE',
                    'status' => $shipment->current_status,
                    'location_id' => $location?->id,
                    'location_name' => $location?->name ?? 'Operational Checkpoint',
                    'latitude' => $location?->latitude,
                    'longitude' => $location?->longitude,
                    'description' => "Shipment status updated to {$shipment->current_status}.",
                    'event_time' => now(),
                    'created_by' => $actor?->id,
                ]);
            }

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
     * Customer Grain Cargo Booking with Real-Time Simulated Payment Gateway
     */
    public function createGrainBooking(array $data, User $customer): Shipment
    {
        return DB::transaction(function () use ($data, $customer) {
            $cargo = ucfirst(strtolower($data['cargo_type'] ?? 'Rice'));
            $prefix = match (strtoupper($cargo)) {
                'WHEAT' => 'CT-2026-WHT',
                'CORN' => 'CT-2026-CRN',
                'SOYBEANS' => 'CT-2026-SOY',
                'RICE' => 'CT-2026-RIC',
                default => 'CT-2026-GRN',
            };

            $unitPrice = match (strtoupper($cargo)) {
                'RICE' => 580.00,
                'WHEAT' => 275.00,
                'CORN' => 215.00,
                'SOYBEANS' => 445.00,
                default => 250.00,
            };

            $hsCode = match (strtoupper($cargo)) {
                'RICE' => '1006.30.90',
                'WHEAT' => '1001.99.10',
                'CORN' => '1005.90.00',
                'SOYBEANS' => '1201.90.00',
                default => '1005.90.00',
            };

            $shipmentNumber = $prefix . strtoupper(bin2hex(random_bytes(3)));
            $departure = now()->addDays(2);
            $arrival = $departure->copy()->addDays(18);

            $weight = (float) ($data['weight'] ?? 500.0);
            $commodityPrice = round($weight * $unitPrice, 2);
            $freightCharge = round($weight * 45.00, 2); // Freight rate billed
            $vesselFee = round($weight * 28.00, 2); // Vessel charter cost
            $grossAmount = round($commodityPrice + $freightCharge, 2);
            $netRevenue = max(0, round($grossAmount - $vesselFee, 2));

            $shipment = new Shipment([
                'shipment_number' => $shipmentNumber,
                'customer_id' => $customer->id,
                'carrier_id' => $data['carrier_id'],
                'origin_id' => $data['origin_id'],
                'destination_id' => $data['destination_id'],
                'current_location_id' => $data['origin_id'],
                'cargo_type' => $cargo,
                'weight' => $weight,
                'volume' => round($weight * 1.35, 2),
                'expected_departure' => $departure,
                'expected_arrival' => $arrival,
                'estimated_arrival' => $arrival,
                'current_status' => Shipment::STATUS_CREATED,
                'delay_status' => 'ON_TIME',
                'delay_duration_hours' => 0,
                'delay_severity' => 'NONE',
                'delay_reason' => 'Awaiting customer payment settlement before vessel allocation.',
                'payment_status' => 'PENDING',
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => null,
                'paid_at' => null,
                'commodity_price_usd' => $commodityPrice,
                'freight_charge_usd' => $freightCharge,
                'vessel_fee_usd' => $vesselFee,
                'gross_amount_usd' => $grossAmount,
                'net_revenue_usd' => $netRevenue,
                'notes' => $data['notes'] ?? "Customer {$cargo} purchase contract. Vessel tracking unlocks after payment.",
            ]);

            $shipment->save();

            // Create detailed commodity manifest item
            $shipment->items()->create([
                'item_description' => "{$cargo} Bulk Purchase Order",
                'grade_specification' => $data['grade_specification'] ?? 'Export Grade A Standard',
                'quantity' => (int) max(1, round($weight)),
                'weight' => $weight,
                'volume' => round($weight * 1.35, 2),
                'moisture_content_percent' => 13.0,
                'hs_code' => $hsCode,
                'package_type' => 'BULK_VESSEL_HOLD',
            ]);

            // Initial tracking checkpoint (Awaiting payment)
            $shipment->trackingEvents()->create([
                'event_type' => 'STATUS_UPDATE',
                'status' => Shipment::STATUS_CREATED,
                'location_id' => $shipment->origin_id,
                'location_name' => $shipment->origin ? $shipment->origin->name : 'Origin Port Terminal',
                'latitude' => $shipment->origin ? $shipment->origin->latitude : null,
                'longitude' => $shipment->origin ? $shipment->origin->longitude : null,
                'description' => "Purchase order placed by {$customer->name}. Pending Payment Gateway verification.",
                'event_time' => now(),
                'created_by' => $customer->id,
            ]);

            AuditService::log('COMMODITY_ORDER_CREATED', $shipment, null, $shipment->toArray(), $customer);

            return $shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'items', 'trackingEvents']);
        });
    }

    /**
     * Process Payment Gateway Settlement
     * Verifies simulated QRIS / VA / L/C payment, sets paid_at, books vessel hold, and unlocks live map tracking.
     */
    public function processSimulatedPayment(Shipment $shipment, string $paymentMethod, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $paymentMethod, $actor) {
            $prefix = match ($paymentMethod) {
                'QRIS_REALTIME' => 'QRIS',
                'VIRTUAL_ACCOUNT' => 'VA',
                'LETTER_OF_CREDIT' => 'LC',
                default => 'PAY',
            };

            $shipment->payment_status = 'PAID';
            $shipment->payment_method = $paymentMethod;
            $shipment->payment_reference = $prefix . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $shipment->paid_at = now();

            if ($shipment->current_status === Shipment::STATUS_CREATED) {
                $shipment->current_status = Shipment::STATUS_BOOKED;
                $shipment->delay_reason = 'Payment settled. Vessel berth allocated on ocean schedule.';
            }

            $shipment->save();

            $shipment->trackingEvents()->create([
                'event_type' => 'CHECKPOINT',
                'status' => Shipment::STATUS_BOOKED,
                'location_id' => $shipment->origin_id,
                'location_name' => $shipment->origin ? $shipment->origin->name : 'Export Port Terminal',
                'latitude' => $shipment->origin ? $shipment->origin->latitude : null,
                'longitude' => $shipment->origin ? $shipment->origin->longitude : null,
                'description' => "Payment verified via {$paymentMethod} (Ref: {$shipment->payment_reference}). Vessel cargo space confirmed and live tracking unlocked.",
                'event_time' => now(),
                'created_by' => $actor ? $actor->id : $shipment->customer_id,
            ]);

            AuditService::log('PAYMENT_SETTLED', $shipment, ['payment_status' => 'PENDING'], ['payment_status' => 'PAID', 'ref' => $shipment->payment_reference, 'gross_usd' => $shipment->gross_amount_usd], $actor);

            if ($shipment->customer) {
                NotificationService::send(
                    $shipment->customer,
                    "Payment Confirmed: {$shipment->shipment_number}",
                    "Payment settled via {$paymentMethod}. Live vessel tracking for {$shipment->shipment_number} is now unlocked.",
                    'success',
                    "/shipments/{$shipment->id}"
                );
            }

            return $shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'trackingEvents']);
        });
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
