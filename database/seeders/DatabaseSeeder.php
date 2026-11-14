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
        ];

        $carrierModels = [];
        foreach ($carriers as $carrierData) {
            $carrierModels[$carrierData['code']] = Carrier::updateOrCreate(['code' => $carrierData['code']], $carrierData);
        }

        // 3. Create Locations (Ports, Airports, Hubs with accurate Lat/Lng)
        $locations = [
            [
                'code' => 'IDTPP',
                'name' => 'Tanjung Priok Port',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'type' => 'PORT',
                'latitude' => -6.1038,
                'longitude' => 106.8837,
                'address' => 'Jl. Raya Pelabuhan No.9, Tanjung Priok, Jakarta Utara',
            ],
            [
                'code' => 'SGSIN',
                'name' => 'Port of Singapore (PSA)',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'type' => 'PORT',
                'latitude' => 1.2644,
                'longitude' => 103.8228,
                'address' => '7B Keppel Rd, Tanjong Pagar Complex, Singapore',
            ],
            [
                'code' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'type' => 'PORT',
                'latitude' => 51.9054,
                'longitude' => 4.3160,
                'address' => 'Wilhelminakade 902, 3072 AP Rotterdam, Netherlands',
            ],
            [
                'code' => 'USLAX',
                'name' => 'Port of Los Angeles',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'type' => 'PORT',
                'latitude' => 33.7432,
                'longitude' => -118.2673,
                'address' => '425 S. Palos Verdes St, San Pedro, CA 90731, USA',
            ],
            [
                'code' => 'AUSYD',
                'name' => 'Port Botany Sydney',
                'city' => 'Sydney',
                'country' => 'Australia',
                'type' => 'PORT',
                'latitude' => -33.9744,
                'longitude' => 151.2183,
                'address' => 'Brotherson Dock, Port Botany NSW 2036, Australia',
            ],
            [
                'code' => 'CNSHG',
                'name' => 'Port of Shanghai (Yangshan)',
                'city' => 'Shanghai',
                'country' => 'China',
                'type' => 'PORT',
                'latitude' => 30.6278,
                'longitude' => 122.0642,
                'address' => 'Yangshan Deepwater Port, Shanghai, China',
            ],
            [
                'code' => 'DEHAM',
                'name' => 'Port of Hamburg',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'type' => 'PORT',
                'latitude' => 53.5353,
                'longitude' => 9.9872,
                'address' => 'Neuer Wandrahm 4, 20457 Hamburg, Germany',
            ],
        ];

        $locationModels = [];
        foreach ($locations as $locData) {
            $locationModels[$locData['code']] = Location::updateOrCreate(['code' => $locData['code']], $locData);
        }

        // 4. Create Shipments with realistic scenarios matching Group A, B, C slides
        $now = Carbon::now();

        // Scenario 1: Group A (Southeast Asia -> Europe: Tanjung Priok to Rotterdam) - Delayed due to Customs Hold
        $s1Departure = $now->copy()->subDays(6);
        $s1ExpectedArrival = $now->copy()->addDays(2);
        $s1EstimatedArrival = $now->copy()->addDays(3)->addHours(4); // Delayed by ~28 hours (HIGH severity)

        $s1 = Shipment::create([
            'shipment_number' => 'CT-2026-EUR01',
            'customer_id' => $customer1->id,
            'carrier_id' => $carrierModels['MSK']->id,
            'origin_id' => $locationModels['IDTPP']->id,
            'destination_id' => $locationModels['NLRTM']->id,
            'current_location_id' => $locationModels['SGSIN']->id,
            'cargo_type' => 'Consumer Electronics (High-Value)',
            'weight' => 18450.00,
            'volume' => 64.50,
            'expected_departure' => $s1Departure,
            'expected_arrival' => $s1ExpectedArrival,
            'actual_departure' => $s1Departure,
            'estimated_arrival' => $s1EstimatedArrival,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'delay_status' => 'DELAYED',
            'delay_duration_hours' => 28,
            'delay_severity' => 'HIGH',
            'delay_reason' => 'Delay of 28h triggered by customs document inspection hold at transshipment hub.',
            'notes' => 'Priority batch for European holiday market distribution.',
        ]);

        $s1->items()->createMany([
            [
                'item_description' => 'Lithium Battery Power Units',
                'quantity' => 120,
                'weight' => 6000.00,
                'volume' => 22.00,
                'hs_code' => '8507.60.00',
                'package_type' => 'PALLET',
            ],
            [
                'item_description' => 'Precision Audio Components',
                'quantity' => 350,
                'weight' => 12450.00,
                'volume' => 42.50,
                'hs_code' => '8518.22.00',
                'package_type' => 'CRATE',
            ],
        ]);

        // Realistic Maritime Sea Route: Jakarta -> Sunda Strait -> Singapore -> Malacca Strait -> Indian Ocean -> Red Sea -> Suez Canal -> Mediterranean -> Gibraltar -> English Channel -> Rotterdam
        $s1->trackingEvents()->create([
            'event_type' => 'STATUS_UPDATE',
            'status' => 'CREATED',
            'location_id' => $locationModels['IDTPP']->id,
            'location_name' => $locationModels['IDTPP']->name,
            'latitude' => -6.1038,
            'longitude' => 106.8837,
            'description' => 'Shipment booking registered in system.',
            'event_time' => $s1Departure->copy()->subDays(2),
            'created_by' => $operator->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'PICKED_UP',
            'location_id' => $locationModels['IDTPP']->id,
            'location_name' => 'Tanjung Priok Container Terminal 3',
            'latitude' => -5.9800,
            'longitude' => 106.8800,
            'description' => 'Cargo received and container sealed on vessel MV Maersk Mc-Kinney Moller.',
            'event_time' => $s1Departure,
            'created_by' => $operator->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Sunda Strait Sea Corridor',
            'latitude' => -5.9200,
            'longitude' => 105.8500,
            'description' => 'Vessel exited Sunda Strait heading northwest towards Singapore Strait.',
            'event_time' => $s1Departure->copy()->addHours(14),
            'created_by' => $operator->id,
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => $locationModels['SGSIN']->id,
            'location_name' => 'Port of Singapore (Pasir Panjang)',
            'latitude' => 1.2750,
            'longitude' => 103.7800,
            'description' => 'Vessel docked for scheduled feeder container transfers.',
            'event_time' => $s1Departure->copy()->addDays(2),
            'created_by' => $operator->id,
        ]);

        $ex1Event = $s1->trackingEvents()->create([
            'event_type' => 'EXCEPTION_FLAG',
            'status' => 'IN_TRANSIT',
            'location_id' => $locationModels['SGSIN']->id,
            'location_name' => 'Singapore Customs Anchorage East',
            'latitude' => 1.2400,
            'longitude' => 103.8800,
            'description' => '[EXCEPTION: CUSTOMS_HOLD] Declaration discrepancy on lithium battery documentation.',
            'event_time' => $now->copy()->subHours(18),
            'created_by' => $operator->id,
        ]);

        $s1->exceptions()->create([
            'tracking_event_id' => $ex1Event->id,
            'exception_type' => ShipmentException::TYPE_CUSTOMS_HOLD,
            'severity' => 'HIGH',
            'status' => ShipmentException::STATUS_INVESTIGATING,
            'description' => 'Customs authority requested supplementary MSDS battery certification.',
            'detected_at' => $now->copy()->subHours(18),
            'resolution_notes' => 'Forwarder requested revised MSDS from manufacturer; awaiting validation.',
        ]);

        $s1->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Malacca Strait Sea Lane (Underway)',
            'latitude' => 2.8500,
            'longitude' => 101.2000,
            'description' => 'Documentation cleared for transit; vessel steaming speed 19.4 knots heading to Indian Ocean.',
            'event_time' => $now->copy()->subHours(3),
            'created_by' => $operator->id,
        ]);

        // Scenario 2: Group B (Asia -> North America: Shanghai to Los Angeles) - Weather Delay
        $s2Departure = $now->copy()->subDays(4);
        $s2ExpectedArrival = $now->copy()->addDays(8);
        $s2EstimatedArrival = $now->copy()->addDays(8)->addHours(14); // Delayed by 14h (MEDIUM severity)

        $s2 = Shipment::create([
            'shipment_number' => 'CT-2026-USA02',
            'customer_id' => $customer2->id,
            'carrier_id' => $carrierModels['EVG']->id,
            'origin_id' => $locationModels['CNSHG']->id,
            'destination_id' => $locationModels['USLAX']->id,
            'current_location_id' => null,
            'cargo_type' => 'Industrial Solar Panels & Inverters',
            'weight' => 24800.00,
            'volume' => 78.00,
            'expected_departure' => $s2Departure,
            'expected_arrival' => $s2ExpectedArrival,
            'actual_departure' => $s2Departure,
            'estimated_arrival' => $s2EstimatedArrival,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'delay_status' => 'DELAYED',
            'delay_duration_hours' => 14,
            'delay_severity' => 'MEDIUM',
            'delay_reason' => 'Vessel speed reduced due to North Pacific gale weather front.',
            'notes' => 'Commercial installation consignment.',
        ]);

        $s2->items()->create([
            'item_description' => 'Tier-1 Monocrystalline Solar Panels',
            'quantity' => 450,
            'weight' => 24800.00,
            'volume' => 78.00,
            'hs_code' => '8541.40.10',
            'package_type' => 'CONTAINER_40FT',
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'STATUS_UPDATE',
            'status' => 'CREATED',
            'location_id' => $locationModels['CNSHG']->id,
            'location_name' => $locationModels['CNSHG']->name,
            'latitude' => 30.6274,
            'longitude' => 122.0642,
            'description' => 'Consignment booked with carrier Evergreen Marine Corp.',
            'event_time' => $s2Departure->copy()->subDays(1),
            'created_by' => $operator->id,
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'PICKED_UP',
            'location_id' => $locationModels['CNSHG']->id,
            'location_name' => 'Shanghai Yangshan Deepwater Terminal Phase 4',
            'latitude' => 30.6274,
            'longitude' => 122.0642,
            'description' => 'Loaded aboard MV Ever Given for Pacific Trans-load route.',
            'event_time' => $s2Departure,
            'created_by' => $operator->id,
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'East China Sea - Osumi Strait Corridor',
            'latitude' => 30.5000,
            'longitude' => 130.5000,
            'description' => 'Navigating through Osumi Strait, entered open North Pacific shipping fairway.',
            'event_time' => $s2Departure->copy()->addDays(1),
            'created_by' => $operator->id,
        ]);

        $s2->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'North Pacific Great Circle Waypoint Alpha',
            'latitude' => 38.5000,
            'longitude' => 155.0000,
            'description' => 'Steaming at 20.8 knots heading towards International Date Line.',
            'event_time' => $s2Departure->copy()->addDays(2)->addHours(12),
            'created_by' => $operator->id,
        ]);

        $s2ExEvent = $s2->trackingEvents()->create([
            'event_type' => 'EXCEPTION_FLAG',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'North Pacific Oceanic Storm Front (Mid-Pacific)',
            'latitude' => 41.5000,
            'longitude' => 178.0000,
            'description' => '[EXCEPTION: WEATHER_DELAY] Severe oceanic gale and 7-meter swells. Speed reduced to 11.2 knots for container safety.',
            'event_time' => $now->copy()->subHours(6),
            'created_by' => $operator->id,
        ]);

        $s2->exceptions()->create([
            'tracking_event_id' => $s2ExEvent->id,
            'exception_type' => ShipmentException::TYPE_WEATHER_DELAY,
            'severity' => 'MEDIUM',
            'status' => ShipmentException::STATUS_OPEN,
            'description' => 'Gale-force headwinds in mid-Pacific causing 14h ETA slip to Port of Los Angeles.',
            'detected_at' => $now->copy()->subHours(6),
        ]);

        // Scenario 3: Group C (Southeast Asia -> Australia: Jakarta to Sydney) - On-Time & Delivered!
        $s3Departure = $now->copy()->subDays(12);
        $s3ExpectedArrival = $now->copy()->subDays(2);

        $s3 = Shipment::create([
            'shipment_number' => 'CT-2026-AUS03',
            'customer_id' => $customer1->id,
            'carrier_id' => $carrierModels['MSC']->id,
            'origin_id' => $locationModels['IDTPP']->id,
            'destination_id' => $locationModels['AUSYD']->id,
            'current_location_id' => $locationModels['AUSYD']->id,
            'cargo_type' => 'Processed Organic Arabica Coffee Beans',
            'weight' => 19200.00,
            'volume' => 52.00,
            'expected_departure' => $s3Departure,
            'expected_arrival' => $s3ExpectedArrival,
            'actual_departure' => $s3Departure,
            'actual_arrival' => $s3ExpectedArrival->copy()->subHours(4),
            'estimated_arrival' => $s3ExpectedArrival,
            'current_status' => Shipment::STATUS_DELIVERED,
            'delay_status' => 'ON_TIME',
            'delay_duration_hours' => 0,
            'delay_severity' => 'NONE',
            'delay_reason' => 'Delivered 4 hours ahead of schedule.',
            'notes' => 'Temperature-controlled reefer container.',
        ]);

        $s3->items()->create([
            'item_description' => 'Sumatra Gayo Grade 1 Green Coffee',
            'quantity' => 320,
            'weight' => 19200.00,
            'volume' => 52.00,
            'hs_code' => '0901.11.10',
            'package_type' => 'GRAINPRO_BAGS',
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'PICKED_UP',
            'location_id' => $locationModels['IDTPP']->id,
            'location_name' => $locationModels['IDTPP']->name,
            'latitude' => -6.1038,
            'longitude' => 106.8837,
            'description' => 'Container sealed at origin export facility.',
            'event_time' => $s3Departure,
            'created_by' => $operator->id,
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Lombok Strait Maritime Passage',
            'latitude' => -8.7500,
            'longitude' => 115.3000,
            'description' => 'Transited Lombok Strait safely into southern Indian Ocean.',
            'event_time' => $s3Departure->copy()->addDays(2),
            'created_by' => $operator->id,
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => null,
            'location_name' => 'Coral Sea Maritime Lane',
            'latitude' => -16.0000,
            'longitude' => 146.5000,
            'description' => 'Cruising eastern Australian seaboard under clear weather conditions.',
            'event_time' => $s3Departure->copy()->addDays(7),
            'created_by' => $operator->id,
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'IN_TRANSIT',
            'location_id' => $locationModels['AUSYD']->id,
            'location_name' => 'Port Botany Sydney Terminal 3',
            'latitude' => -33.9500,
            'longitude' => 151.2200,
            'description' => 'Vessel berthed at Port Botany, discharge operations commenced.',
            'event_time' => $s3ExpectedArrival->copy()->subHours(8),
            'created_by' => $operator->id,
        ]);

        $s3->trackingEvents()->create([
            'event_type' => 'CHECKPOINT',
            'status' => 'DELIVERED',
            'location_id' => $locationModels['AUSYD']->id,
            'location_name' => 'Sydney Central Roastery Distribution Hub',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'description' => 'Consignment signed and received in good condition by Sydney Roasting Co.',
            'event_time' => $s3ExpectedArrival->copy()->subHours(4),
            'created_by' => $operator->id,
        ]);

        // Scenario 4: Fast Air Freight (Jakarta to Hamburg via SIA) - BOOKED
        $s4 = Shipment::create([
            'shipment_number' => 'CT-2026-AIR04',
            'customer_id' => $customer2->id,
            'carrier_id' => $carrierModels['SIA']->id,
            'origin_id' => $locationModels['IDTPP']->id,
            'destination_id' => $locationModels['DEHAM']->id,
            'current_location_id' => $locationModels['IDTPP']->id,
            'cargo_type' => 'Pharmaceutical Vaccines (Cold Chain)',
            'weight' => 2200.00,
            'volume' => 8.50,
            'expected_departure' => $now->copy()->addDay(),
            'expected_arrival' => $now->copy()->addDays(3),
            'estimated_arrival' => $now->copy()->addDays(3),
            'current_status' => Shipment::STATUS_BOOKED,
            'delay_status' => 'ON_TIME',
            'delay_duration_hours' => 0,
            'delay_severity' => 'NONE',
            'delay_reason' => 'Scheduled air corridor confirmed.',
            'notes' => 'Constant -20C required.',
        ]);

        $s4->items()->create([
            'item_description' => 'Lyophilized Biological Formulations',
            'quantity' => 40,
            'weight' => 2200.00,
            'volume' => 8.50,
            'hs_code' => '3002.20.00',
            'package_type' => 'INSULATED_BOX',
        ]);

        $s4->trackingEvents()->create([
            'event_type' => 'STATUS_UPDATE',
            'status' => 'BOOKED',
            'location_id' => $locationModels['IDTPP']->id,
            'location_name' => 'Jakarta Air Cargo Center',
            'latitude' => -6.1256,
            'longitude' => 106.6558,
            'description' => 'Airway bill issued; flight space allocated.',
            'event_time' => $now->copy()->subHours(4),
            'created_by' => $operator->id,
        ]);

        // 5. Create In-App Notifications
        Notification::create([
            'user_id' => $customer1->id,
            'title' => 'Disruption Reported: CT-2026-EUR01',
            'message' => 'Customs check hold flagged at Port of Singapore. Estimated arrival revised.',
            'type' => 'danger',
            'is_read' => false,
            'link' => "/shipments/{$s1->id}",
        ]);

        Notification::create([
            'user_id' => $customer1->id,
            'title' => 'Shipment Delivered: CT-2026-AUS03',
            'message' => 'Your shipment of Arabica Coffee has been safely delivered in Sydney.',
            'type' => 'success',
            'is_read' => true,
            'read_at' => $now->copy()->subDays(1),
            'link' => "/shipments/{$s3->id}",
        ]);

        Notification::create([
            'user_id' => $customer2->id,
            'title' => 'Transit Delay Warning: CT-2026-USA02',
            'message' => 'Vessel has adjusted navigation speed due to North Pacific weather front.',
            'type' => 'warning',
            'is_read' => false,
            'link' => "/shipments/{$s2->id}",
        ]);

        // 6. Create Audit Logs
        AuditService::log('SYSTEM_SEED', $admin, null, ['status' => 'Initial database seed complete'], $admin);
        AuditService::log('SHIPMENT_CREATED', $s1, null, $s1->toArray(), $operator);
        AuditService::log('SHIPMENT_CREATED', $s2, null, $s2->toArray(), $operator);
        AuditService::log('SHIPMENT_DELIVERED', $s3, null, ['status' => 'DELIVERED'], $operator);
    }
}
