<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShipmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected ShipmentService $shipmentService;
    protected User $operator;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shipmentService = app(ShipmentService::class);

        $this->operator = User::create([
            'name' => 'Operator Test',
            'email' => 'op@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
        ]);

        $this->carrier = Carrier::create([
            'code' => 'TEST_CARRIER',
            'name' => 'Test Ocean Line',
            'transport_mode' => 'SEA',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'code' => 'ORIGIN_LOC',
            'name' => 'Origin Hub',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'type' => 'PORT',
            'latitude' => -6.1,
            'longitude' => 106.8,
        ]);

        $this->destination = Location::create([
            'code' => 'DEST_LOC',
            'name' => 'Destination Hub',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'type' => 'PORT',
            'latitude' => 51.9,
            'longitude' => 4.3,
        ]);
    }

    public function test_valid_shipment_lifecycle_progression(): void
    {
        // 1. Create shipment (Status: CREATED)
        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Electronic Parts',
            'weight' => 500,
            'volume' => 2.5,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
        ], $this->operator);

        $this->assertEquals(Shipment::STATUS_CREATED, $shipment->current_status);

        // 2. CREATED -> BOOKED
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_BOOKED, $this->operator);
        $this->assertEquals(Shipment::STATUS_BOOKED, $shipment->current_status);

        // 3. BOOKED -> PICKED_UP
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_PICKED_UP, $this->operator);
        $this->assertEquals(Shipment::STATUS_PICKED_UP, $shipment->current_status);

        // 4. PICKED_UP -> IN_TRANSIT
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_IN_TRANSIT, $this->operator);
        $this->assertEquals(Shipment::STATUS_IN_TRANSIT, $shipment->current_status);
        $this->assertNotNull($shipment->actual_departure);

        // 5. IN_TRANSIT -> DELIVERED
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_DELIVERED, $this->operator);
        $this->assertEquals(Shipment::STATUS_DELIVERED, $shipment->current_status);
        $this->assertNotNull($shipment->actual_arrival);
    }

    public function test_invalid_status_jump_is_strictly_rejected(): void
    {
        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Raw Materials',
            'weight' => 100,
            'volume' => 1.0,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(3),
        ], $this->operator);

        $this->expectException(ValidationException::class);
        // Direct transition from CREATED to DELIVERED is forbidden
        $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_DELIVERED, $this->operator);
    }

    public function test_terminal_delivered_status_cannot_be_reverted(): void
    {
        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Machinery',
            'weight' => 200,
            'volume' => 1.5,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(2),
        ], $this->operator);

        // Advance to delivered
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_BOOKED, $this->operator);
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_PICKED_UP, $this->operator);
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_IN_TRANSIT, $this->operator);
        $shipment = $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_DELIVERED, $this->operator);

        $this->expectException(ValidationException::class);
        // Attempt reversal from DELIVERED back to CREATED or IN_TRANSIT
        $this->shipmentService->transitionStatus($shipment, Shipment::STATUS_CREATED, $this->operator);
    }
}
