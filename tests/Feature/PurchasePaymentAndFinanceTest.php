<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PurchasePaymentAndFinanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer1;
    protected User $customer2;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

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
            'name' => 'Ops Dispatcher',
            'email' => 'operator@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'operator',
        ]);

        $this->customer1 = User::create([
            'name' => 'Buyer One',
            'email' => 'buyer1@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'company' => 'PT Pangan Nusantara',
        ]);

        $this->customer2 = User::create([
            'name' => 'Buyer Two',
            'email' => 'buyer2@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'company' => 'Pacific Grain Corp',
        ]);

        $this->carrier = Carrier::create([
            'code' => 'CMA',
            'name' => 'CMA CGM Bulk Line',
            'transport_mode' => 'SEA',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'code' => 'THBKK',
            'name' => 'Laem Chabang Rice Terminal',
            'city' => 'Bangkok',
            'country' => 'Thailand',
            'type' => 'PORT',
            'latitude' => 13.0827,
            'longitude' => 100.8833,
        ]);

        $this->destination = Location::create([
            'code' => 'IDTPP',
            'name' => 'Tanjung Priok Grain Silos',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'type' => 'PORT',
            'latitude' => -6.1045,
            'longitude' => 106.8837,
        ]);
    }

    public function test_customer_can_purchase_commodity_and_redirects_to_payment_gateway(): void
    {
        $response = $this->actingAs($this->customer1)->post('/shop', [
            'cargo_type' => 'Rice',
            'weight' => 100,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'payment_method' => 'QRIS_REALTIME',
        ]);

        $shipment = Shipment::firstOrFail();
        $response->assertRedirect(route('shipments.payment', $shipment->id));

        $this->assertEquals('PENDING', $shipment->payment_status);
        $this->assertEquals(Shipment::STATUS_CREATED, $shipment->current_status);
        $this->assertNull($shipment->paid_at);
        $this->assertEquals(58000.00, (float) $shipment->commodity_price_usd);
        $this->assertEquals(4500.00, (float) $shipment->freight_charge_usd);
        $this->assertEquals(2800.00, (float) $shipment->vessel_fee_usd);
        $this->assertEquals(62500.00, (float) $shipment->gross_amount_usd);
        $this->assertEquals(59700.00, (float) $shipment->net_revenue_usd);
    }

    public function test_vessel_tracking_stays_locked_before_payment_and_unlocks_after_settlement(): void
    {
        $this->actingAs($this->customer1)->post('/shop', [
            'cargo_type' => 'Rice',
            'weight' => 250,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
        ]);

        $shipment = Shipment::firstOrFail();

        // Step 1: Before payment, vessel tracking map is locked
        $lockedView = $this->actingAs($this->customer1)->get(route('shipments.show', $shipment->id));
        $lockedView->assertOk();
        $lockedView->assertViewHas('trackingUnlocked', false);
        $lockedView->assertSee('Vessel Tracking Locked Pending Payment Settlement');
        $lockedView->assertDontSee('id="shipmentMap"', false);

        // Step 2: Settle via Payment Gateway
        $payResponse = $this->actingAs($this->customer1)->post(route('shipments.pay', $shipment->id), [
            'payment_method' => 'VIRTUAL_ACCOUNT',
        ]);
        $payResponse->assertRedirect(route('shipments.show', $shipment->id));

        $shipment->refresh();
        $this->assertEquals('PAID', $shipment->payment_status);
        $this->assertEquals('VIRTUAL_ACCOUNT', $shipment->payment_method);
        $this->assertNotNull($shipment->paid_at);
        $this->assertNotNull($shipment->payment_reference);
        $this->assertEquals(Shipment::STATUS_BOOKED, $shipment->current_status);

        // Step 3: After settlement, live vessel tracking unlocks cleanly
        $unlockedView = $this->actingAs($this->customer1)->get(route('shipments.show', $shipment->id));
        $unlockedView->assertOk();
        $unlockedView->assertViewHas('trackingUnlocked', true);
        $unlockedView->assertSee('id="shipmentMap"', false);
        $unlockedView->assertDontSee('Vessel Tracking Locked Pending Payment Settlement');
    }

    public function test_customer_cannot_access_another_customers_payment_gateway(): void
    {
        $this->actingAs($this->customer1)->post('/shop', [
            'cargo_type' => 'Corn',
            'weight' => 100,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
        ]);

        $shipment = Shipment::firstOrFail();

        $this->actingAs($this->customer2)->get(route('shipments.payment', $shipment->id))->assertForbidden();
        $this->actingAs($this->customer2)->post(route('shipments.pay', $shipment->id), [
            'payment_method' => 'QRIS_REALTIME',
        ])->assertForbidden();
    }

    public function test_role_boundaries_enforce_shop_for_customer_and_finance_for_admin_operator(): void
    {
        $this->actingAs($this->admin)->get(route('shop.index'))->assertForbidden();
        $this->actingAs($this->operator)->get(route('shop.index'))->assertForbidden();
        $this->actingAs($this->admin)->post(route('shop.store'), [])->assertForbidden();
        $this->actingAs($this->operator)->post(route('shop.store'), [])->assertForbidden();

        $this->actingAs($this->customer1)->get(route('finance.index'))->assertForbidden();
        $this->actingAs($this->customer1)->get(route('finance.export.xlsx'))->assertForbidden();
        $this->actingAs($this->customer1)->get(route('finance.export.csv'))->assertForbidden();
    }

    public function test_admin_and_operator_view_accurate_gross_vessel_fee_net_and_pending_totals(): void
    {
        // Order 1: Paid Rice (100 MT -> Commodity $58k + Freight $4.5k = Gross $62.5k, Vessel Fee $2.8k, Net $59.7k)
        $this->actingAs($this->customer1)->post('/shop', [
            'cargo_type' => 'Rice',
            'weight' => 100,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
        ]);
        $paidShipment = Shipment::firstOrFail();
        $this->actingAs($this->customer1)->post(route('shipments.pay', $paidShipment->id), [
            'payment_method' => 'QRIS_REALTIME',
        ]);

        // Order 2: Unsettled Corn (200 MT -> Commodity $43k + Freight $9k = Gross $52k, Vessel Fee $5.6k, Projected Net $46.4k)
        $this->actingAs($this->customer2)->post('/shop', [
            'cargo_type' => 'Corn',
            'weight' => 200,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
        ]);

        foreach ([$this->admin, $this->operator] as $privileged) {
            $res = $this->actingAs($privileged)->get(route('finance.index'));
            $res->assertOk();
            $res->assertViewHas('summary', function (array $summary) {
                return $summary['paid_count'] === 1
                    && $summary['pending_count'] === 1
                    && abs($summary['paid_gross_usd'] - 62500.00) < 0.01
                    && abs($summary['paid_vessel_fee_usd'] - 2800.00) < 0.01
                    && abs($summary['paid_net_usd'] - 59700.00) < 0.01
                    && abs($summary['pending_gross_usd'] - 52000.00) < 0.01;
            });

            $res->assertSee('$62,500.00');
            $res->assertSee('-$2,800.00');
            $res->assertSee('$59,700.00');
            $res->assertSee('$52,000.00');
        }
    }

    public function test_admin_and_operator_can_export_valid_xlsx_and_csv_financial_reports(): void
    {
        $this->actingAs($this->customer1)->post('/shop', [
            'cargo_type' => 'Wheat',
            'weight' => 100,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
        ]);
        $shipment = Shipment::firstOrFail();
        $this->actingAs($this->customer1)->post(route('shipments.pay', $shipment->id), [
            'payment_method' => 'LETTER_OF_CREDIT',
        ]);

        // Test genuine XLSX binary output via ZipArchive validation
        $xlsxResponse = $this->actingAs($this->admin)->get(route('finance.export.xlsx'));
        $xlsxResponse->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $xlsxResponse->headers->get('Content-Type')
        );

        $tmpFile = $xlsxResponse->getFile()->getPathname();
        $this->assertStringStartsWith('PK', file_get_contents($tmpFile));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmpFile) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmpFile);

        $this->assertNotFalse($sheetXml);
        $this->assertStringContainsString($shipment->shipment_number, (string) $sheetXml);
        $this->assertStringContainsString('TOTAL (1 Contracts)', (string) $sheetXml);

        // Test CSV streaming output for Operator
        $csvResponse = $this->actingAs($this->operator)->get(route('finance.export.csv'));
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('Content-Type'));
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString($shipment->shipment_number, $csvContent);
        $this->assertStringContainsString('TOTAL (1 Contracts)', $csvContent);
    }
}
