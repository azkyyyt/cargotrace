<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'Alexander Vance (System Admin)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'company' => 'CargoTrace Global HQ',
                'phone' => '+62 811-0000-001',
            ]
        );

        $operator = User::firstOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Sarah Connor (Fleet Dispatcher)',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'company' => 'CargoTrace Southeast Asia Operations',
                'phone' => '+62 812-0000-002',
            ]
        );

        $customer1 = User::firstOrCreate(
            ['email' => 'customer@cargotrace.test'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company' => 'PT Global Tech Solusindo',
                'phone' => '+62 813-0000-003',
            ]
        );

        $customer2 = User::firstOrCreate(
            ['email' => 'pacific@cargotrace.test'],
            [
                'name' => 'Elena Rostova',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company' => 'Pacific Agro Trading Pte Ltd',
                'phone' => '+65 6789-0123',
            ]
        );

        // 2. Create Carriers
        $carriers = [
            [
                'code' => 'MSK',
                'name' => 'Maersk Line Shipping',
                'transport_mode' => 'SEA',
                'contact_email' => 'booking@maersk.example.com',
                'contact_phone' => '+45 3363 3363',
                'website' => 'https://www.maersk.com',
                'is_active' => true,
            ],
            [
                'code' => 'MSC',
                'name' => 'MSC Mediterranean Shipping',
                'transport_mode' => 'SEA',
                'contact_email' => 'info@msc.example.com',
                'contact_phone' => '+41 22 703 8888',
                'website' => 'https://www.msc.com',
                'is_active' => true,
            ],
            [
                'code' => 'SIA',
                'name' => 'Singapore Airlines Cargo',
                'transport_mode' => 'AIR',
                'contact_email' => 'cargo@singaporeair.example.com',
                'contact_phone' => '+65 6542 5588',
                'website' => 'https://www.siacargo.com',
                'is_active' => true,
            ],
            [
                'code' => 'DHL',
                'name' => 'DHL Global Forwarding',
                'transport_mode' => 'AIR',
                'contact_email' => 'airfreight@dhl.example.com',
                'contact_phone' => '+49 228 1820',
                'website' => 'https://www.dhl.com',
                'is_active' => true,
            ],
            [
                'code' => 'EVG',
                'name' => 'Evergreen Marine Corp',
                'transport_mode' => 'SEA',
                'contact_email' => 'service@evergreen-marine.example.com',
                'contact_phone' => '+886 2 2501 1211',
                'website' => 'https://www.evergreen-marine.com',
                'is_active' => true,
            ],
            [
                'code' => 'CMA',
                'name' => 'CMA CGM Bulk Logistics',
                'transport_mode' => 'SEA',
                'contact_email' => 'bulk@cma-cgm.example.com',
                'contact_phone' => '+33 4 88 91 90 00',
                'website' => 'https://www.cma-cgm.com',
                'is_active' => true,
            ],
        ];

        $carrierModels = [];
        foreach ($carriers as $carrierData) {
            $carrierModels[$carrierData['code']] = Carrier::updateOrCreate(['code' => $carrierData['code']], $carrierData);
        }

        // 3. Create Locations (Major International Grain Loading Silos and Discharge Ports)
        $locations = [
            [
                'code' => 'BRSSZ',
                'name' => 'Port of Santos (Grain Terminal)',
                'city' => 'Santos',
                'country' => 'Brazil',
                'type' => 'PORT',
                'latitude' => -23.9678,
                'longitude' => -46.2956,
                'address' => 'Avenida Conselheiro Rodrigues Alves, Santos - SP, Brazil',
            ],
            [
                'code' => 'IDTPP',
                'name' => 'Tanjung Priok Bulk Terminal',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'type' => 'PORT',
                'latitude' => -6.1038,
                'longitude' => 106.8837,
                'address' => 'Dermaga Bogasari Grain Silos, Pelabuhan Tanjung Priok, Jakarta',
            ],
            [
                'code' => 'IDSUB',
                'name' => 'Tanjung Perak Port (Grain Silos)',
                'city' => 'Surabaya',
                'country' => 'Indonesia',
                'type' => 'PORT',
                'latitude' => -7.1982,
                'longitude' => 112.7335,
                'address' => 'Terminal Jamrud Utara, Pelabuhan Tanjung Perak, Surabaya',
            ],
            [
                'code' => 'AUFRE',
                'name' => 'Port of Fremantle (Kwinana Grain Terminal)',
                'city' => 'Perth',
                'country' => 'Australia',
                'type' => 'PORT',
                'latitude' => -32.0569,
                'longitude' => 115.7439,
                'address' => 'CBH Grain Jetty, Kwinana, Western Australia',
            ],
            [
                'code' => 'USMSY',
                'name' => 'Port of South Louisiana (Mississippi Grain Silos)',
                'city' => 'New Orleans',
                'country' => 'United States',
                'type' => 'PORT',
                'latitude' => 29.9511,
                'longitude' => -90.0715,
                'address' => 'Mississippi River Mile 118, Convent, LA, USA',
            ],
            [
                'code' => 'CNTAO',
                'name' => 'Port of Qingdao (Grain & Oil Terminal)',
                'city' => 'Qingdao',
                'country' => 'China',
                'type' => 'PORT',
                'latitude' => 36.0671,
                'longitude' => 120.3826,
                'address' => 'Dagang Port Area, Qingdao, Shandong, China',
            ],
            [
                'code' => 'THBKK',
                'name' => 'Port of Laem Chabang (Rice Bulk Pier)',
                'city' => 'Bangkok',
                'country' => 'Thailand',
                'type' => 'PORT',
                'latitude' => 13.0827,
                'longitude' => 100.8833,
                'address' => 'Laem Chabang Port Complex, Chonburi, Thailand',
            ],
            [
                'code' => 'SGSIN',
                'name' => 'Port of Singapore (PSA Grain Anchorage)',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'type' => 'PORT',
                'latitude' => 1.2644,
                'longitude' => 103.8228,
                'address' => 'Keppel Distripark, Singapore',
            ],
        ];

        $locationModels = [];
        foreach ($locations as $locData) {
            $locationModels[$locData['code']] = Location::updateOrCreate(['code' => $locData['code']], $locData);
        }

        // 4. Create Shipments with realistic grain scenarios (Wheat, Corn, Soybeans, Rice)
        $now = Carbon::now();

        // Scenario 1: Corn (Santos, Brazil -> Tanjung Priok, Indonesia) - Paid via Real-time QRIS & Underway with Live Weather!
        $s1Departure = $now->copy()->subDays(8);
        $s1ExpectedArrival = $now->copy()->addDays(18);
        $s1EstimatedArrival = $now->copy()->addDays(18)->addHours(8); // +8h slight sea swell

        $s1 = Shipment::create([
            'shipment_number' => 'CT-2026-CRN01',
            'customer_id' => $customer1->id,
            'carrier_id' => $carrierModels['MSK']->id,
            'origin_id' => $locationModels['BRSSZ']->id,
            'destination_id' => $locationModels['IDTPP']->id,
            'current_location_id' => null,
            'cargo_type' => 'Corn',
            'weight' => 28000.00, // 28,000 Metric Tons
            'volume' => 37800.00,
            'expected_departure' => $s1Departure,
            'expected_arrival' => $s1ExpectedArrival,
            'actual_departure' => $s1Departure,
            'estimated_arrival' => $s1EstimatedArrival,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'delay_status' => 'DELAYED',
            'delay_duration_hours' => 8,
            'delay_severity' => 'LOW',
            'delay_reason' => 'Moderate rough sea state off Cape of Good Hope (+8h speed reduction).',
            'payment_status' => 'PAID',
            'payment_method' => 'QRIS_REALTIME',
            'payment_reference' => 'QRIS-2026-BRZ99120',
            'freight_charge_usd' => 1358000.00,
            'notes' => 'Bulk yellow corn for feed mill processing in West Java.',
        ]);

        $s1->items()->create([
            'item_description' => 'Brazilian Yellow Corn #2 (Non-GMO)',
            'grade_specification' => 'Moisture Max 14.0%, Broken Kernels Max 3.0%',
            'quantity' => 1,
            'weight' => 28000.00,
            'volume' => 37800.00,
            'moisture_content_percent' => 13.20,
            'hs_code' => '1005.90.00',
            'package_type' => 'BULK_VESSEL_HOLD',
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'STATUS_UPDATE',
            'status' => 'CREATED',
            'location_id' => $locationModels['BRSSZ']->id,
            'location_name' => $locationModels['BRSSZ']->name,
            'latitude' => -23.9678,
            'longitude' => -46.2956,
            'description' => 'Grain freight booking placed by PT Budi Santoso Agrobisnis. Awaiting payment.',
            'event_time' => $s1Departure->copy()->subDays(2),
            'created_by' => $operator->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'BOOKED',
            'location_id' => $locationModels['BRSSZ']->id,
            'location_name' => 'Santos Terminal 12 Bulk Silos',
            'latitude' => -23.9678,
            'longitude' => -46.2956,
            'description' => '[PAYMENT GATEWAY SIMULATION] Real-time QRIS payment verified $1,358,000 USD. Ref: QRIS-2026-BRZ99120. Hold booked on MV Maersk Dry Bulk.',
            'event_time' => $s1Departure->copy()->subDays(1),
            'created_by' => $customer1->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'PICKED_UP',
            'location_id' => $locationModels['BRSSZ']->id,
            'location_name' => 'Santos Loading Berth 4',
            'latitude' => -23.9678,
            'longitude' => -46.2956,
            'description' => 'Loading 28,000 MT corn completed into 5 holds. Phyto-sanitary inspection passed.',
            'event_time' => $s1Departure,
            'created_by' => $operator->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'South Atlantic Shipping Corridor',
            'latitude' => -30.0000,
            'longitude' => -25.0000,
            'description' => 'Steaming eastward at 14.5 knots towards Cape of Good Hope.',
            'event_time' => $s1Departure->copy()->addDays(3),
            'created_by' => $operator->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Cape of Good Hope Sea Corridor (Active Live Position)',
            'latitude' => -35.5000,
            'longitude' => 18.5000,
            'description' => '[LIVE WEATHER IMPACT] Wind speed 42 km/h. Sea swell encountered. Speed adjusted to 11.8 knots (+8h delay impact).',
            'event_time' => $now->copy()->subHours(4),
            'created_by' => $operator->id,
        ]);

        // Scenario 2: Wheat (Fremantle, Australia -> Tanjung Priok, Indonesia) - On-Time & Delivered!
        $s2Departure = $now->copy()->subDays(10);
        $s2ExpectedArrival = $now->copy()->subDays(1);

        $s2 = Shipment::create([
            'shipment_number' => 'CT-2026-WHT02',
            'customer_id' => $customer1->id,
            'carrier_id' => $carrierModels['MSC']->id,
            'origin_id' => $locationModels['AUFRE']->id,
            'destination_id' => $locationModels['IDTPP']->id,
            'current_location_id' => $locationModels['IDTPP']->id,
            'cargo_type' => 'Wheat',
            'weight' => 32000.00,
            'volume' => 42000.00,
            'expected_departure' => $s2Departure,
            'expected_arrival' => $s2ExpectedArrival,
            'actual_departure' => $s2Departure,
            'actual_arrival' => $s2ExpectedArrival->copy()->subHours(6),
            'estimated_arrival' => $s2ExpectedArrival,
            'current_status' => Shipment::STATUS_DELIVERED,
            'delay_status' => 'ON_TIME',
            'delay_duration_hours' => 0,
            'delay_severity' => 'NONE',
            'delay_reason' => 'Delivered 6 hours ahead of scheduled berthing window.',
            'payment_status' => 'PAID',
            'payment_method' => 'LETTER_OF_CREDIT',
            'payment_reference' => 'LC-ANZ-2026-00412',
            'freight_charge_usd' => 960000.00,
            'notes' => 'Australian Hard Wheat (APW) for flour mill silo storage.',
        ]);

        $s2->items()->create([
            'item_description' => 'Australian Premium White Wheat (APW1)',
            'grade_specification' => 'Protein 10.5% Min, Moisture 12.0% Max',
            'quantity' => 1,
            'weight' => 32000.00,
            'volume' => 42000.00,
            'moisture_content_percent' => 11.40,
            'hs_code' => '1001.99.10',
            'package_type' => 'BULK_VESSEL_HOLD',
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'PICKED_UP',
            'location_id' => $locationModels['AUFRE']->id,
            'location_name' => 'Kwinana Grain Jetty CBH Silos',
            'latitude' => -32.0569,
            'longitude' => 115.7439,
            'description' => 'Vessel laden with 32,000 MT wheat. Draft inspection confirmed.',
            'event_time' => $s2Departure,
            'created_by' => $operator->id,
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Eastern Indian Ocean Maritime Route',
            'latitude' => -20.0000,
            'longitude' => 110.0000,
            'description' => 'Cruising northward under calm tropical ocean conditions at 15.2 knots.',
            'event_time' => $s2Departure->copy()->addDays(4),
            'created_by' => $operator->id,
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'DELIVERED',
            'location_id' => $locationModels['IDTPP']->id,
            'location_name' => 'Tanjung Priok Bogasari Flour Mill Jetty',
            'latitude' => -6.1038,
            'longitude' => 106.8837,
            'description' => 'Pneumatic unloader discharge completed. Consignment accepted by customer.',
            'event_time' => $s2ExpectedArrival->copy()->subHours(6),
            'created_by' => $operator->id,
        ]);

        // Scenario 3: Soybeans (New Orleans, USA -> Qingdao, China via Panama) - Delayed due to Weather
        $s3Departure = $now->copy()->subDays(6);
        $s3ExpectedArrival = $now->copy()->addDays(20);
        $s3EstimatedArrival = $now->copy()->addDays(21)->addHours(4); // +28h delay (HIGH)

        $s3 = Shipment::create([
            'shipment_number' => 'CT-2026-SOY03',
            'customer_id' => $customer2->id,
            'carrier_id' => $carrierModels['EVG']->id,
            'origin_id' => $locationModels['USMSY']->id,
            'destination_id' => $locationModels['CNTAO']->id,
            'current_location_id' => null,
            'cargo_type' => 'Soybeans',
            'weight' => 54000.00, // Panamax bulk load
            'volume' => 71500.00,
            'expected_departure' => $s3Departure,
            'expected_arrival' => $s3ExpectedArrival,
            'actual_departure' => $s3Departure,
            'estimated_arrival' => $s3EstimatedArrival,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'delay_status' => 'DELAYED',
            'delay_duration_hours' => 28,
            'delay_severity' => 'HIGH',
            'delay_reason' => 'Delay of 28h: Severe gale winds & rough sea state in mid-Pacific wake.',
            'payment_status' => 'PAID',
            'payment_method' => 'LETTER_OF_CREDIT',
            'payment_reference' => 'LC-CITI-2026-88091',
            'freight_charge_usd' => 2862000.00,
            'notes' => 'US Yellow Soybeans #2 for crush plant delivery in Shandong.',
        ]);

        $s3->items()->create([
            'item_description' => 'US No. 2 Yellow Soybeans (GMO Bulk)',
            'grade_specification' => 'Oil Content 18.5% Min, Moisture 13.0% Max',
            'quantity' => 1,
            'weight' => 54000.00,
            'volume' => 71500.00,
            'moisture_content_percent' => 12.50,
            'hs_code' => '1201.90.00',
            'package_type' => 'BULK_VESSEL_HOLD',
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'PICKED_UP',
            'location_id' => $locationModels['USMSY']->id,
            'location_name' => 'Mississippi River Bulk Silo Complex',
            'latitude' => 29.9511,
            'longitude' => -90.0715,
            'description' => 'Loaded aboard bulk carrier MV Evergreen Bulk. Draft surveyed.',
            'event_time' => $s3Departure,
            'created_by' => $operator->id,
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Panama Canal Transit Channel',
            'latitude' => 8.5000,
            'longitude' => -79.5000,
            'description' => 'Exited Miraflores Locks entering Pacific ocean track.',
            'event_time' => $s3Departure->copy()->addDays(3),
            'created_by' => $operator->id,
        ]);

        $s3ExEvent = $s3->trackingEvents()->create([
            'event_type' => 'EXCEPTION_FLAG',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'East Pacific Tropical Fairway (Current Live Position)',
            'latitude' => 12.0000,
            'longitude' => -110.0000,
            'description' => '[EXCEPTION: WEATHER_DELAY] Tropical disturbance winds 58 km/h. Speed reduced to 10.2 knots (+28h ETA impact).',
            'event_time' => $now->copy()->subHours(6),
            'created_by' => $operator->id,
        ]);

        $s3->exceptions()->create([
            'tracking_event_id' => $s3ExEvent->id,
            'exception_type' => ShipmentException::TYPE_WEATHER_DELAY,
            'severity' => 'HIGH',
            'status' => ShipmentException::STATUS_OPEN,
            'description' => 'Severe weather front in East Pacific forcing routing detour and speed slowdown.',
            'detected_at' => $now->copy()->subHours(6),
        ]);

        // Scenario 4: Rice (Bangkok, Thailand -> Tanjung Perak, Surabaya, Indonesia) - Booked & Loading
        $s4 = Shipment::create([
            'shipment_number' => 'CT-2026-RIC04',
            'customer_id' => $customer2->id,
            'carrier_id' => $carrierModels['CMA']->id,
            'origin_id' => $locationModels['THBKK']->id,
            'destination_id' => $locationModels['IDSUB']->id,
            'current_location_id' => $locationModels['THBKK']->id,
            'cargo_type' => 'Rice',
            'weight' => 12500.00,
            'volume' => 16500.00,
            'expected_departure' => $now->copy()->addDays(2),
            'expected_arrival' => $now->copy()->addDays(8),
            'estimated_arrival' => $now->copy()->addDays(8),
            'current_status' => Shipment::STATUS_BOOKED,
            'delay_status' => 'ON_TIME',
            'delay_duration_hours' => 0,
            'delay_severity' => 'NONE',
            'delay_reason' => 'Vessel berthing schedule on track at Laem Chabang terminal.',
            'payment_status' => 'PAID',
            'payment_method' => 'VIRTUAL_ACCOUNT',
            'payment_reference' => 'VA-BNI-2026-55310',
            'freight_charge_usd' => 375000.00,
            'notes' => 'Thai Hom Mali Jasmine Rice (Jumbo Bags). Stored in climate-monitored hold.',
        ]);

        $s4->items()->create([
            'item_description' => 'Thai Fragrant Rice 100% Grade A',
            'grade_specification' => 'Broken 5% Max, Moisture 14.0% Max',
            'quantity' => 12500,
            'weight' => 12500.00,
            'volume' => 16500.00,
            'moisture_content_percent' => 13.50,
            'hs_code' => '1006.30.40',
            'package_type' => 'JUMBO_BAGS',
        ]);

        $s4->trackingEvents()->create([
            'event_type' => 'STATUS_UPDATE',
            'status' => 'BOOKED',
            'location_id' => $locationModels['THBKK']->id,
            'location_name' => 'Laem Chabang Port Complex',
            'latitude' => 13.0827,
            'longitude' => 100.8833,
            'description' => 'Consignment booked with carrier CMA CGM Bulk. Jumbo bags staged at warehouse.',
            'event_time' => $now->copy()->subHours(12),
            'created_by' => $operator->id,
        ]);
        // 5. Create In-App Notifications
        Notification::create([
            'user_id' => $customer1->id,
            'title' => 'Bulk Grain Booking Paid: CT-2026-CRN01',
            'message' => 'Simulated QRIS payment confirmed ($1,358,000 USD). Vessel MV Maersk Dry Bulk en route Santos to Tanjung Priok.',
            'type' => 'success',
            'is_read' => false,
            'link' => "/shipments/{$s1->id}",
        ]);

        Notification::create([
            'user_id' => $customer1->id,
            'title' => 'Wheat Consignment Delivered: CT-2026-WHT02',
            'message' => 'Australian Premium Wheat safely discharged at Bogasari Grain Silos, Tanjung Priok.',
            'type' => 'success',
            'is_read' => true,
            'read_at' => $now->copy()->subDays(1),
            'link' => "/shipments/{$s2->id}",
        ]);

        Notification::create([
            'user_id' => $customer2->id,
            'title' => 'Severe Weather Alert: CT-2026-SOY03',
            'message' => 'Gale-force ocean front encountered in East Pacific. Automated delay engine computed +28h ETA adjustment.',
            'type' => 'warning',
            'is_read' => false,
            'link' => "/shipments/{$s3->id}",
        ]);

        // 6. Create Audit Logs
        AuditService::log('SYSTEM_SEED', $admin, null, ['status' => 'Initial database seed complete'], $admin);
        AuditService::log('SHIPMENT_CREATED', $s1, null, $s1->toArray(), $operator);
        AuditService::log('SHIPMENT_CREATED', $s2, null, $s2->toArray(), $operator);
        AuditService::log('SHIPMENT_DELIVERED', $s3, null, ['status' => 'DELIVERED'], $operator);
    }
}
