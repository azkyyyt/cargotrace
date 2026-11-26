<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
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

        $this->operator = User::create([
            'name' => 'Logistics Operator',
            'email' => 'operator@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
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
            'transport_mode' => 'SEA',
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
            'cargo_type' => 'Wheat Bulk',
            'weight' => 25000,
            'volume' => 30000,
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
        $response = $this->actingAs($this->customerB)->get("/shipments/{$this->shipmentOfA->id}");
        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_edit_or_delete_shipment(): void
    {
        $this->actingAs($this->customerA)->get('/shipments-create')->assertStatus(403);

        $this->actingAs($this->customerA)->post('/shipments', [
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->loc1->id,
            'destination_id' => $this->loc2->id,
            'cargo_type' => 'Wheat',
            'weight' => 1000,
            'volume' => 1200,
            'expected_departure' => now()->format('Y-m-d H:i'),
            'expected_arrival' => now()->addDays(5)->format('Y-m-d H:i'),
        ])->assertStatus(403);

        $this->actingAs($this->customerA)->get("/shipments/{$this->shipmentOfA->id}/edit")->assertStatus(403);
        $this->actingAs($this->customerA)->delete("/shipments/{$this->shipmentOfA->id}")->assertStatus(403);
        $this->assertDatabaseHas('shipments', ['id' => $this->shipmentOfA->id]);
    }

    public function test_operator_can_create_and_update_operational_shipments(): void
    {
        $this->actingAs($this->operator)->get('/shipments-create')->assertStatus(200);

        $response = $this->actingAs($this->operator)->post('/shipments', [
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->loc1->id,
            'destination_id' => $this->loc2->id,
            'cargo_type' => 'Corn Bulk',
            'weight' => 28000,
            'volume' => 35000,
            'expected_departure' => now()->format('Y-m-d H:i'),
            'expected_arrival' => now()->addDays(10)->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipments', [
            'customer_id' => $this->customerA->id,
            'cargo_type' => 'Corn Bulk',
        ]);
    }

    public function test_operator_cannot_access_admin_users_settings_or_delete_shipment(): void
    {
        $this->actingAs($this->operator)->get('/users')->assertStatus(403);
        $this->actingAs($this->operator)->get('/settings')->assertStatus(403);
        $this->actingAs($this->operator)->get('/carriers')->assertStatus(403);
        $this->actingAs($this->operator)->get('/locations')->assertStatus(403);
        $this->actingAs($this->operator)->get('/audit-logs')->assertStatus(403);
        $this->actingAs($this->operator)->delete("/shipments/{$this->shipmentOfA->id}")->assertStatus(403);
    }

    public function test_admin_can_delete_shipment_and_cannot_create_operational_shipment(): void
    {
        $this->actingAs($this->admin)->get('/shipments-create')->assertStatus(403);

        $response = $this->actingAs($this->admin)->delete("/shipments/{$this->shipmentOfA->id}");
        $response->assertRedirect('/shipments');
        $this->assertSoftDeleted('shipments', ['id' => $this->shipmentOfA->id]);
    }

    public function test_api_register_enforces_customer_role_against_privilege_escalation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Malicious Attacker',
            'email' => 'attacker@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'attacker@test.com',
            'role' => 'customer',
        ]);
    }

    public function test_api_customer_cannot_create_shipment_while_operator_can(): void
    {
        Sanctum::actingAs($this->customerA);
        $this->postJson('/api/shipments', [
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->loc1->id,
            'destination_id' => $this->loc2->id,
            'cargo_type' => 'Illegal Direct Create',
            'weight' => 100,
            'volume' => 10,
            'expected_departure' => now()->toDateTimeString(),
            'expected_arrival' => now()->addDays(2)->toDateTimeString(),
        ])->assertStatus(403);

        Sanctum::actingAs($this->operator);
        $this->postJson('/api/shipments', [
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->loc1->id,
            'destination_id' => $this->loc2->id,
            'cargo_type' => 'Valid Operator Dispatch',
            'weight' => 500,
            'volume' => 15,
            'expected_departure' => now()->toDateTimeString(),
            'expected_arrival' => now()->addDays(2)->toDateTimeString(),
        ])->assertStatus(201);
    }
}
