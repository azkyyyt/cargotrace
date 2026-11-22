<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrainBookingAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Budi Santoso',
            'company' => 'PT Pangan Perkasa',
        ]);

        $this->carrier = Carrier::create([
            'name' => 'Maersk Bulk Logistics',
            'code' => 'MAE',
            'transport_mode' => 'SEA',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'name' => 'Port of Santos',
            'code' => 'BRSSZ',
            'city' => 'Santos',
            'country' => 'Brazil',
            'latitude' => -23.9678,
            'longitude' => -46.2956,
        ]);

        $this->destination = Location::create([
            'name' => 'Tanjung Priok',
            'code' => 'IDTPP',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'latitude' => -6.1038,
            'longitude' => 106.8837,
        ]);
    }

    public function test_customer_can_book_bulk_grain_consignment(): void
    {
        $response = $this->actingAs($this->customer)->post(route('shipments.bookGrain'), [
            'cargo_type' => 'Corn',
            'weight' => 25000,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'carrier_id' => $this->carrier->id,
            'grade_specification' => 'Grade 2 Yellow Corn',
            'payment_method' => 'QRIS_REALTIME',
            'notes' => 'Bulk corn charter booking test',
        ]);

        $response->assertRedirect();
        
        $shipment = Shipment::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals('Corn', $shipment->cargo_type);
        $this->assertEquals(25000.0, (float) $shipment->weight); // 25,000 MT
        $this->assertEquals(Shipment::STATUS_CREATED, $shipment->current_status);
        $this->assertEquals('PENDING', $shipment->payment_status);
        $this->assertGreaterThan(0, $shipment->freight_charge_usd);
    }

    public function test_customer_can_simulate_instant_qris_payment(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-2026-TEST01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Wheat',
            'weight' => 10000000,
            'volume' => 13000,
            'expected_departure' => now()->addDays(2),
            'expected_arrival' => now()->addDays(16),
            'estimated_arrival' => now()->addDays(16),
            'current_status' => Shipment::STATUS_CREATED,
            'payment_status' => 'UNPAID',
            'freight_charge_usd' => 450000.00,
        ]);

        $response = $this->actingAs($this->customer)->post(route('shipments.simulatePayment', $shipment->id), [
            'payment_method' => 'QRIS_REALTIME',
        ]);

        $response->assertRedirect(route('shipments.show', $shipment->id));

        $shipment->refresh();
        $this->assertEquals('PAID', $shipment->payment_status);
        $this->assertEquals('QRIS_REALTIME', $shipment->payment_method);
        $this->assertEquals(Shipment::STATUS_BOOKED, $shipment->current_status);
        $this->assertStringStartsWith('QRIS-', $shipment->payment_reference);

        // Tracking event should be logged
        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $shipment->id,
            'status' => 'BOOKED',
        ]);
    }

    public function test_weather_check_triggers_deterministic_evaluation(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-2026-TEST02',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Soybeans',
            'weight' => 50000000,
            'volume' => 65000,
            'expected_departure' => now()->subDays(5),
            'expected_arrival' => now()->addDays(10),
            'estimated_arrival' => now()->addDays(10),
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'payment_status' => 'PAID',
            'freight_charge_usd' => 1200000.00,
        ]);

        $response = $this->actingAs($this->customer)->post(route('shipments.weatherCheck', $shipment->id));
        $response->assertRedirect(route('shipments.show', $shipment->id));
    }
}
