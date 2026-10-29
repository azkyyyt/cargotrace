<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customerA;
    protected User $customerB;
    protected Shipment $shipmentOfA;
    protected Carrier $carrier;
    protected Location $loc1;
    protected Location $loc2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->customerA = User::create([
            'name' => 'Customer Alice',
            'email' => 'alice@test.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->customerB = User::create([
            'name' => 'Customer Bob',
            'email' => 'bob@test.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->carrier = Carrier::create([
            'code' => 'CAR_AUTH',
            'name' => 'Auth Carrier',
            'transport_mode' => 'AIR',
            'is_active' => true,
        ]);

        $this->loc1 = Location::create([
            'code' => 'L1', 'name' => 'Hub 1', 'city' => 'Jakarta', 'country' => 'ID',
            'type' => 'PORT', 'latitude' => -6.2, 'longitude' => 106.8
        ]);

        $this->loc2 = Location::create([
            'code' => 'L2', 'name' => 'Hub 2', 'city' => 'Singapore', 'country' => 'SG',
            'type' => 'PORT', 'latitude' => 1.3, 'longitude' => 103.8
        ]);

        $this->shipmentOfA = Shipment::create([
            'shipment_number' => 'CT-ALICE-01',
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->loc1->id,
            'destination_id' => $this->loc2->id,
            'cargo_type' => 'Private Consignment',
            'weight' => 200,
            'volume' => 1.2,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(2),
            'estimated_arrival' => now()->addDays(2),
            'current_status' => 'BOOKED',
        ]);
    }

    public function test_customer_can_view_their_own_shipment(): void
    {
        $response = $this->actingAs($this->customerA)->get("/shipments/{$this->shipmentOfA->id}");
        $response->assertStatus(200);
        $response->assertSee('CT-ALICE-01');
    }

    public function test_customer_cannot_view_another_customers_shipment(): void
    {
        // Customer B attempts to view Customer A's shipment
        $response = $this->actingAs($this->customerB)->get("/shipments/{$this->shipmentOfA->id}");
        $response->assertStatus(403);
    }

    public function test_customer_cannot_delete_shipment(): void
    {
        $response = $this->actingAs($this->customerA)->delete("/shipments/{$this->shipmentOfA->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('shipments', ['id' => $this->shipmentOfA->id]);
    }

    public function test_admin_can_delete_shipment(): void
    {
        $response = $this->actingAs($this->admin)->delete("/shipments/{$this->shipmentOfA->id}");
        $response->assertRedirect('/shipments');
        $this->assertSoftDeleted('shipments', ['id' => $this->shipmentOfA->id]);
    }
}
