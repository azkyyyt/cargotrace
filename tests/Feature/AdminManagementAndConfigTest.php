<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Services\EtaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementAndConfigTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Chief',
            'email' => 'admin@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->operator = User::create([
            'name' => 'Ops User',
            'email' => 'op@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
        ]);

        $this->customer = User::create([
            'name' => 'Cust User',
            'email' => 'cust@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
    }

    public function test_admin_can_create_update_and_delete_users(): void
    {
        $this->actingAs($this->admin)->get(route('users.index'))->assertStatus(200);

        $createRes = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'New Dispatcher',
            'email' => 'dispatcher2@cargotrace.test',
            'password' => 'password123',
            'role' => 'operator',
            'company' => 'Port Authority',
        ]);
        $createRes->assertRedirect(route('users.index'));
        $created = User::where('email', 'dispatcher2@cargotrace.test')->firstOrFail();
        $this->assertEquals('operator', $created->role);

        $updateRes = $this->actingAs($this->admin)->put(route('users.update', $created->id), [
            'name' => 'Promoted Admin',
            'email' => 'dispatcher2@cargotrace.test',
            'role' => 'admin',
            'company' => 'CargoTrace HQ',
        ]);
        $updateRes->assertRedirect(route('users.index'));
        $this->assertEquals('admin', $created->fresh()->role);

        $deleteRes = $this->actingAs($this->admin)->delete(route('users.destroy', $created->id));
        $deleteRes->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $created->id]);
    }

    public function test_admin_can_configure_delay_thresholds_and_recalculate_shipments(): void
    {
        $carrier = Carrier::create(['code' => 'MSK', 'name' => 'Maersk', 'transport_mode' => 'SEA', 'is_active' => true]);
        $orig = Location::create(['code' => 'O1', 'name' => 'Port O', 'city' => 'JKT', 'country' => 'ID', 'type' => 'PORT', 'latitude' => -6.1, 'longitude' => 106.8]);
        $dest = Location::create(['code' => 'D1', 'name' => 'Port D', 'city' => 'SIN', 'country' => 'SG', 'type' => 'PORT', 'latitude' => 1.3, 'longitude' => 103.8]);

        $shipment = Shipment::create([
            'shipment_number' => 'CT-CFG-01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $orig->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Wheat',
            'weight' => 10000,
            'volume' => 12000,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(2),
            'estimated_arrival' => now()->addDays(2)->addHours(10), // 10 hours late
            'current_status' => 'IN_TRANSIT',
        ]);

        // Set low=12, medium=30, group=12 -> 10h delay now becomes LOW instead of default MEDIUM!
        $res = $this->actingAs($this->admin)->put(route('settings.update'), [
            'group_delay_threshold_hours' => 12,
            'delay_threshold_low_hours' => 12,
            'delay_threshold_medium_hours' => 30,
            'active_group_scenario' => 'Group A (Southeast Asia -> Europe | 12h Threshold)',
            'recalculate_shipments' => '1',
        ]);

        $res->assertRedirect(route('settings.index'));
        $this->assertEquals('12', Setting::getValue('group_delay_threshold_hours'));
        $this->assertEquals('LOW', $shipment->fresh()->delay_severity);
    }

    public function test_operator_can_trigger_weather_rule_check(): void
    {
        $carrier = Carrier::create(['code' => 'EVG', 'name' => 'Evergreen', 'transport_mode' => 'SEA', 'is_active' => true]);
        $orig = Location::create(['code' => 'O2', 'name' => 'Port O2', 'city' => 'JKT', 'country' => 'ID', 'type' => 'PORT', 'latitude' => -6.1, 'longitude' => 106.8]);
        $dest = Location::create(['code' => 'D2', 'name' => 'Port D2', 'city' => 'RTM', 'country' => 'NL', 'type' => 'PORT', 'latitude' => 51.9, 'longitude' => 4.3]);

        $shipment = Shipment::create([
            'shipment_number' => 'CT-WX-01',
            'customer_id' => $this->customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $orig->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Corn',
            'weight' => 15000,
            'volume' => 20000,
            'expected_departure' => now()->subDays(3),
            'expected_arrival' => now()->addDays(8),
            'estimated_arrival' => now()->addDays(8),
            'current_status' => 'IN_TRANSIT',
        ]);

        $this->actingAs($this->customer)->post(route('shipments.weatherCheck', $shipment->id))->assertStatus(403);
        $this->actingAs($this->operator)->post(route('shipments.weatherCheck', $shipment->id))
            ->assertRedirect(route('shipments.show', $shipment->id));
    }
}
