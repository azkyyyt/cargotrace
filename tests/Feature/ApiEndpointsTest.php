<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;
    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'name' => 'Operator Api',
            'email' => 'opapi@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
        ]);

        $this->customer = User::create([
            'name' => 'Customer Api',
            'email' => 'custapi@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->carrier = Carrier::create([
            'code' => 'API_CAR',
            'name' => 'API Carrier Line',
            'transport_mode' => 'SEA',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'code' => 'LOC_A', 'name' => 'Port A', 'city' => 'Jakarta', 'country' => 'ID',
            'type' => 'PORT', 'latitude' => -6.1, 'longitude' => 106.8
        ]);

        $this->destination = Location::create([
            'code' => 'LOC_B', 'name' => 'Port B', 'city' => 'Singapore', 'country' => 'SG',
            'type' => 'PORT', 'latitude' => 1.3, 'longitude' => 103.8
        ]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-API-001',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'High Tech Goods',
            'weight' => 1000,
            'volume' => 5.0,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
            'current_status' => 'IN_TRANSIT',
        ]);
    }

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->getJson('/api/shipments');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_retrieve_paginated_shipments(): void
    {
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/shipments');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'shipment_number', 'cargo_type', 'current_status']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ]);
    }

    public function test_operator_can_record_tracking_event_via_api(): void
    {
        Sanctum::actingAs($this->operator);

        $response = $this->postJson("/api/shipments/{$this->shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'location_id' => $this->origin->id,
            'location_name' => 'Transit Checkpoint Alpha',
            'description' => 'Container inspected and loaded on board.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tracking event registered successfully.'
            ]);

        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'description' => 'Container inspected and loaded on board.',
        ]);
    }

    public function test_operator_can_raise_and_resolve_exception_via_api(): void
    {
        Sanctum::actingAs($this->operator);

        // 1. Raise Exception
        $resCreate = $this->postJson("/api/shipments/{$this->shipment->id}/exceptions", [
            'exception_type' => 'CUSTOMS_HOLD',
            'severity' => 'HIGH',
            'description' => 'Port inspection delay.',
        ]);

        $resCreate->assertStatus(201);
        $exceptionId = $resCreate->json('data.id');

        // 2. Resolve Exception
        $resUpdate = $this->patchJson("/api/exceptions/{$exceptionId}", [
            'status' => 'RESOLVED',
            'resolution_notes' => 'Cleared after document verification.',
        ]);

        $resUpdate->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'RESOLVED',
                    'resolution_notes' => 'Cleared after document verification.'
                ]
            ]);
    }
}
