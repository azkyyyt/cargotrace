<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrackingChronologyValidationTest extends TestCase
{
    use RefreshDatabase;

    protected TrackingService $trackingService;
    protected ShipmentService $shipmentService;
    protected User $operator;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trackingService = app(TrackingService::class);
        $this->shipmentService = app(ShipmentService::class);

        $this->operator = User::create([
            'name' => 'Tracking Operator',
            'email' => 'trackop@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
        ]);

        $this->carrier = Carrier::create(['code' => 'TC', 'name' => 'Track Carrier', 'transport_mode' => 'SEA', 'is_active' => true]);
        $this->origin = Location::create(['code' => 'TO', 'name' => 'Origin', 'city' => 'Jakarta', 'country' => 'ID', 'type' => 'PORT', 'latitude' => -6.1, 'longitude' => 106.8]);
        $this->destination = Location::create(['code' => 'TD', 'name' => 'Dest', 'city' => 'Tokyo', 'country' => 'JP', 'type' => 'PORT', 'latitude' => 35.6, 'longitude' => 139.7]);
    }

    public function test_chronological_tracking_event_sequence_is_enforced(): void
    {
        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Soybeans',
            'weight' => 12000,
            'volume' => 15000,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(10),
        ], $this->operator);

        // Attempt adding an event timestamped 2 days earlier than initial CREATED event
        $this->expectException(ValidationException::class);
        $this->trackingService->addEvent($shipment, [
            'status' => 'BOOKED',
            'location_name' => 'Backdated Port',
            'description' => 'Invalid chronological timestamp test',
            'event_time' => now()->subDays(2),
        ], $this->operator);
    }

    public function test_cannot_record_tracking_events_on_terminal_shipment(): void
    {
        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Wheat',
            'weight' => 10000,
            'volume' => 12000,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
        ], $this->operator);

        $shipment = $this->shipmentService->transitionStatus($shipment, 'BOOKED', $this->operator);
        $shipment = $this->shipmentService->transitionStatus($shipment, 'PICKED_UP', $this->operator);
        $shipment = $this->shipmentService->transitionStatus($shipment, 'IN_TRANSIT', $this->operator);
        $shipment = $this->shipmentService->transitionStatus($shipment, 'DELIVERED', $this->operator);

        $this->expectException(ValidationException::class);
        $this->trackingService->addEvent($shipment, [
            'status' => 'IN_TRANSIT',
            'location_name' => 'Post-Delivery Rogue Checkpoint',
            'description' => 'Should be blocked after DELIVERED terminal state',
            'event_time' => now()->addMinutes(5),
        ], $this->operator);
    }

    public function test_historical_events_remain_immutable_when_advancing_status(): void
    {
        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Rice',
            'weight' => 5000,
            'volume' => 6500,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(4),
        ], $this->operator);

        $this->trackingService->addEvent($shipment, [
            'status' => 'BOOKED',
            'location_name' => 'Port Booking Desk',
            'description' => 'Space allocated on vessel',
            'event_time' => now()->addMinutes(1),
        ], $this->operator);

        $this->trackingService->addEvent($shipment, [
            'status' => 'PICKED_UP',
            'location_name' => 'Berth 4 Terminal',
            'description' => 'Cargo loaded into holds',
            'event_time' => now()->addMinutes(2),
        ], $this->operator);

        // Original CREATED event + BOOKED + PICKED_UP = 3 chronological immutable records
        $timeline = $this->trackingService->getTimeline($shipment);
        $this->assertCount(3, $timeline);
        $this->assertEquals('CREATED', $timeline[0]->status);
        $this->assertEquals('BOOKED', $timeline[1]->status);
        $this->assertEquals('PICKED_UP', $timeline[2]->status);
    }
}
