<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\EtaService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the CargoTrace database with comprehensive, realistic synthetic data.
     */
    public function run(): void
    {
        $etaService = app(EtaService::class);

        // 1. Seed Users (Roles: Admin, Logistics Operator, Customer)
        $admin = User::firstOrCreate(
            ['email' => 'admin@cargotrace.com'],
            [
                'name' => 'Chief Operations Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'phone' => '+62 21 555-0100',
                'company' => 'CargoTrace Global HQ',
            ]
        );

        $operator = User::firstOrCreate(
            ['email' => 'operator@cargotrace.com'],
            [
                'name' => 'Budi Santoso (Logistics Dispatcher)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR,
                'phone' => '+62 21 555-0101',
                'company' => 'CargoTrace Southeast Asia Operations',
            ]
        );

        $operator2 = User::firstOrCreate(
            ['email' => 'operator2@cargotrace.com'],
            [
                'name' => 'Sarah Jenkins (Terminal Controller)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OPERATOR,
                'phone' => '+31 10 555-0202',
                'company' => 'CargoTrace European Hub',
            ]
        );

        $customer1 = User::firstOrCreate(
            ['email' => 'customer@cargotrace.com'],
            [
                'name' => 'PT Nusantara Global Export',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'phone' => '+62 812 3456 7890',
                'company' => 'PT Nusantara Global Export',
            ]
        );

        $customer2 = User::firstOrCreate(
            ['email' => 'customer2@cargotrace.com'],
            [
                'name' => 'Aussie Trans-Pacific Importers',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'phone' => '+61 2 9876 5432',
                'company' => 'Aussie Trans-Pacific Pty Ltd',
            ]
        );

        $customer3 = User::firstOrCreate(
            ['email' => 'customer3@cargotrace.com'],
            [
                'name' => 'EuroBaltic Commodities BV',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'phone' => '+31 20 888 9900',
                'company' => 'EuroBaltic Commodities BV',
            ]
        );

        // 2. Seed Global Carriers
        $carriersData = [
            [
                'name' => 'Maersk Line',
                'code' => 'MAEU',
                'contact_email' => 'contact@maersk.com',
                'contact_phone' => '+45 33 63 33 63',
                'website' => 'https://www.maersk.com',
                'is_active' => true,
            ],
            [
                'name' => 'Evergreen Marine',
                'code' => 'EMC',
                'contact_email' => 'service@evergreen-marine.com',
                'contact_phone' => '+886 2 2501 1211',
                'website' => 'https://www.evergreen-marine.com',
                'is_active' => true,
            ],
            [
                'name' => 'Ocean Network Express (ONE)',
                'code' => 'ONEY',
                'contact_email' => 'cargo@one-line.com',
                'contact_phone' => '+65 6890 1800',
                'website' => 'https://www.one-line.com',
                'is_active' => true,
            ],
            [
                'name' => 'DHL Global Forwarding',
                'code' => 'DHLG',
                'contact_email' => 'track@dhl.com',
                'contact_phone' => '+49 228 1820',
                'website' => 'https://www.dhl.com',
                'is_active' => true,
            ],
            [
                'name' => 'Mediterranean Shipping Company (MSC)',
                'code' => 'MSCU',
                'contact_email' => 'info@msc.com',
                'contact_phone' => '+41 22 703 8888',
                'website' => 'https://www.msc.com',
                'is_active' => true,
            ],
        ];

        $carriers = [];
        foreach ($carriersData as $cData) {
            $carriers[$cData['code']] = Carrier::firstOrCreate(['code' => $cData['code']], $cData);
        }

        // 3. Seed Major Logistics Hubs, Ports & Terminals
        $locationsData = [
            [
                'name' => 'Port of Tanjung Priok',
                'code' => 'IDJKT',
                'type' => 'PORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1038000,
                'longitude' => 106.8833000,
                'timezone' => 'Asia/Jakarta',
            ],
            [
                'name' => 'Port of Singapore',
                'code' => 'SGSIN',
                'type' => 'PORT',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.2644000,
                'longitude' => 103.8222000,
                'timezone' => 'Asia/Singapore',
            ],
            [
                'name' => 'Port of Rotterdam',
                'code' => 'NLRTM',
                'type' => 'PORT',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'latitude' => 51.9500000,
                'longitude' => 4.1400000,
                'timezone' => 'Europe/Amsterdam',
            ],
            [
                'name' => 'Port of Hamburg',
                'code' => 'DEHAM',
                'type' => 'PORT',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'latitude' => 53.5353000,
                'longitude' => 9.9872000,
                'timezone' => 'Europe/Berlin',
            ],
            [
                'name' => 'Port of Los Angeles',
                'code' => 'USLAX',
                'type' => 'PORT',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'latitude' => 33.7432000,
                'longitude' => -118.2673000,
                'timezone' => 'America/Los_Angeles',
            ],
            [
                'name' => 'Port of Sydney (Port Botany)',
                'code' => 'AUSYD',
                'type' => 'PORT',
                'city' => 'Sydney',
                'country' => 'Australia',
                'latitude' => -33.9744000,
                'longitude' => 151.2186000,
                'timezone' => 'Australia/Sydney',
            ],
            [
                'name' => 'Suez Canal Transit Point',
                'code' => 'EGSUZ',
                'type' => 'CHECKPOINT',
                'city' => 'Suez',
                'country' => 'Egypt',
                'latitude' => 29.9700000,
                'longitude' => 32.5500000,
                'timezone' => 'Africa/Cairo',
            ],
        ];

        $locations = [];
        foreach ($locationsData as $lData) {
            $locations[$lData['code']] = Location::firstOrCreate(['code' => $lData['code']], $lData);
        }

        // 4. Seed Shipments with Realistic Historical Milestones & Scenarios

        // Shipment 1: SE Asia -> Europe (IN_TRANSIT with Customs Exception & Delay)
        // Group Scenario A: Delay threshold 12h, customs hold
        $s1 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-0101'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['MAEU']->id,
                'origin_id' => $locations['IDJKT']->id,
                'destination_id' => $locations['NLRTM']->id,
                'cargo_type' => 'REFRIGERATED',
                'weight' => 24500.00,
                'volume' => 64.00,
                'current_status' => Shipment::STATUS_IN_TRANSIT,
                'expected_departure' => Carbon::now()->subDays(6),
                'actual_departure' => Carbon::now()->subDays(6)->addHours(2),
                'expected_arrival' => Carbon::now()->addDays(5),
                'estimated_arrival' => Carbon::now()->addDays(5)->addHours(14), // Delayed by 14h (> 12h threshold)
                'notes' => 'High-grade Indonesian specialty coffee beans. Temperature controlled 18C.',
            ]
        );
        $etaService->syncShipmentDelay($s1);

        ShipmentItem::create([
            'shipment_id' => $s1->id,
            'description' => 'Arabica Gayo Clean Washed Beans',
            'quantity' => 450,
            'unit' => 'CARTONS',
            'weight' => 15000.00,
            'declared_value' => 180000.00,
            'hs_code' => '0901.11.00',
        ]);
        ShipmentItem::create([
            'shipment_id' => $s1->id,
            'description' => 'Toraja Single Origin Green Coffee',
            'quantity' => 280,
            'unit' => 'CARTONS',
            'weight' => 9500.00,
            'declared_value' => 114000.00,
            'hs_code' => '0901.11.00',
        ]);

        // Historical tracking checkpoints for Shipment 1
        TrackingEvent::create([
            'shipment_id' => $s1->id,
            'location_id' => $locations['IDJKT']->id,
            'location_name' => 'Port of Tanjung Priok, Jakarta',
            'status' => 'PICKED_UP',
            'event_time' => Carbon::now()->subDays(6),
            'latitude' => -6.1038,
            'longitude' => 106.8833,
            'description' => 'Container sealed and cleared for customs export departure.',
            'created_by' => $operator->id,
            'weather_snapshot' => ['temperature' => 31.0, 'windspeed' => 10.5, 'condition' => 'Mainly clear / Partly cloudy', 'source' => 'Open-Meteo API'],
        ]);

        TrackingEvent::create([
            'shipment_id' => $s1->id,
            'location_id' => $locations['SGSIN']->id,
            'location_name' => 'Port of Singapore Transshipment Hub',
            'status' => 'IN_TRANSIT',
            'event_time' => Carbon::now()->subDays(4),
            'latitude' => 1.2644,
            'longitude' => 103.8222,
            'description' => 'Feeder vessel transshipment completed onto mainline carrier Maersk Mc-Kinney Moller.',
            'created_by' => $operator->id,
            'weather_snapshot' => ['temperature' => 29.2, 'windspeed' => 8.1, 'condition' => 'Rain showers', 'source' => 'Open-Meteo API'],
        ]);

        TrackingEvent::create([
            'shipment_id' => $s1->id,
            'location_id' => $locations['EGSUZ']->id,
            'location_name' => 'Suez Canal Maritime Checkpoint',
            'status' => 'IN_TRANSIT',
            'event_time' => Carbon::now()->subDays(1),
            'latitude' => 29.9700,
            'longitude' => 32.5500,
            'description' => 'Vessel entered northbound convoy approaching Mediterranean passage.',
            'created_by' => $operator2->id,
            'weather_snapshot' => ['temperature' => 24.5, 'windspeed' => 14.0, 'condition' => 'Clear sky', 'source' => 'Open-Meteo API'],
        ]);

        // Operational exception for Shipment 1: Customs documentation inquiry
        ShipmentException::create([
            'shipment_id' => $s1->id,
            'exception_type' => ShipmentException::TYPE_CUSTOMS_HOLD,
            'severity' => ShipmentException::SEVERITY_MEDIUM,
            'status' => ShipmentException::STATUS_OPEN,
            'detected_at' => Carbon::now()->subHours(18),
            'description' => 'EU phytosanitary certificate verification requested by Rotterdam customs pre-arrival bureau.',
            'reported_by' => $operator2->id,
        ]);

        // Shipment 2: SE Asia -> Australia (DELIVERED On-Time)
        $s2 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-0102'],
            [
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['ONEY']->id,
                'origin_id' => $locations['IDJKT']->id,
                'destination_id' => $locations['AUSYD']->id,
                'cargo_type' => 'GENERAL',
                'weight' => 18200.00,
                'volume' => 45.00,
                'current_status' => Shipment::STATUS_DELIVERED,
                'expected_departure' => Carbon::now()->subDays(12),
                'actual_departure' => Carbon::now()->subDays(12),
                'expected_arrival' => Carbon::now()->subDays(1),
                'estimated_arrival' => Carbon::now()->subDays(1),
                'actual_arrival' => Carbon::now()->subDays(1)->subHours(3),
                'notes' => 'Timber and engineered wooden panels.',
            ]
        );
        $etaService->syncShipmentDelay($s2);

        ShipmentItem::create([
            'shipment_id' => $s2->id,
            'description' => 'Meranti Plywood Commercial Grade',
            'quantity' => 600,
            'unit' => 'PALLETS',
            'weight' => 18200.00,
            'declared_value' => 95000.00,
            'hs_code' => '4412.31.00',
        ]);

        TrackingEvent::create([
            'shipment_id' => $s2->id,
            'location_id' => $locations['IDJKT']->id,
            'location_name' => 'Port of Tanjung Priok, Jakarta',
            'status' => 'PICKED_UP',
            'event_time' => Carbon::now()->subDays(12),
            'latitude' => -6.1038,
            'longitude' => 106.8833,
            'description' => 'Vessel departed berth.',
            'created_by' => $operator->id,
        ]);

        TrackingEvent::create([
            'shipment_id' => $s2->id,
            'location_id' => $locations['AUSYD']->id,
            'location_name' => 'Port of Sydney (Port Botany)',
            'status' => 'DELIVERED',
            'event_time' => Carbon::now()->subDays(1)->subHours(3),
            'latitude' => -33.9744,
            'longitude' => 151.2186,
            'description' => 'Discharged at terminal and signed off by consignee representative.',
            'created_by' => $operator->id,
            'weather_snapshot' => ['temperature' => 22.0, 'windspeed' => 11.2, 'condition' => 'Clear sky', 'source' => 'Open-Meteo API'],
        ]);

        // Shipment 3: Asia -> North America (IN_TRANSIT with Weather Delay)
        // Group Scenario B: Delay threshold 18h, weather delay
        $s3 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-0103'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['EMC']->id,
                'origin_id' => $locations['SGSIN']->id,
                'destination_id' => $locations['USLAX']->id,
                'cargo_type' => 'ELECTRONICS',
                'weight' => 8400.00,
                'volume' => 28.00,
                'current_status' => Shipment::STATUS_IN_TRANSIT,
                'expected_departure' => Carbon::now()->subDays(4),
                'actual_departure' => Carbon::now()->subDays(4),
                'expected_arrival' => Carbon::now()->addDays(9),
                'estimated_arrival' => Carbon::now()->addDays(10)->addHours(4), // Delayed by 28 hours (HIGH severity)
                'notes' => 'High-value semiconductor circuit assemblies in moisture-barrier packaging.',
            ]
        );
        $etaService->syncShipmentDelay($s3);

        ShipmentItem::create([
            'shipment_id' => $s3->id,
            'description' => 'Integrated Microcontrollers and Sensor Modules',
            'quantity' => 120,
            'unit' => 'CARTONS',
            'weight' => 8400.00,
            'declared_value' => 620000.00,
            'hs_code' => '8542.31.00',
        ]);

        TrackingEvent::create([
            'shipment_id' => $s3->id,
            'location_id' => $locations['SGSIN']->id,
            'location_name' => 'Port of Singapore',
            'status' => 'PICKED_UP',
            'event_time' => Carbon::now()->subDays(4),
            'latitude' => 1.2644,
            'longitude' => 103.8222,
            'description' => 'Laden onto Evergreen Ever Given container ship.',
            'created_by' => $operator->id,
        ]);

        TrackingEvent::create([
            'shipment_id' => $s3->id,
            'location_name' => 'Pacific Ocean Western Corridor',
            'status' => 'IN_TRANSIT',
            'event_time' => Carbon::now()->subHours(12),
            'latitude' => 18.5000,
            'longitude' => 135.0000,
            'description' => 'Route diversion advised to avoid tropical storm front; vessel reducing knots.',
            'created_by' => $operator->id,
            'is_exception' => true,
            'weather_snapshot' => ['temperature' => 26.0, 'windspeed' => 45.0, 'condition' => 'Thunderstorm', 'source' => 'Open-Meteo API'],
        ]);

        ShipmentException::create([
            'shipment_id' => $s3->id,
            'exception_type' => ShipmentException::TYPE_WEATHER_DELAY,
            'severity' => ShipmentException::SEVERITY_HIGH,
            'status' => ShipmentException::STATUS_OPEN,
            'detected_at' => Carbon::now()->subHours(12),
            'description' => 'Severe maritime storm in Philippine Sea necessitated 350 nautical mile detour.',
            'reported_by' => $operator->id,
        ]);

        // Shipment 4: Europe -> SE Asia (BOOKED)
        $s4 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-0104'],
            [
                'customer_id' => $customer3->id,
                'carrier_id' => $carriers['DHLG']->id,
                'origin_id' => $locations['DEHAM']->id,
                'destination_id' => $locations['IDJKT']->id,
                'cargo_type' => 'AUTOMOTIVE',
                'weight' => 14300.00,
                'volume' => 38.00,
                'current_status' => Shipment::STATUS_BOOKED,
                'expected_departure' => Carbon::now()->addDays(2),
                'expected_arrival' => Carbon::now()->addDays(18),
                'notes' => 'Heavy diesel engine replacement components.',
            ]
        );
        $etaService->syncShipmentDelay($s4);

        // Shipment 5: SE Asia -> Europe (CREATED - Fresh Booking)
        $s5 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-0105'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['MSCU']->id,
                'origin_id' => $locations['IDJKT']->id,
                'destination_id' => $locations['DEHAM']->id,
                'cargo_type' => 'FRAGILE',
                'weight' => 5200.00,
                'volume' => 19.50,
                'current_status' => Shipment::STATUS_CREATED,
                'expected_departure' => Carbon::now()->addDays(4),
                'expected_arrival' => Carbon::now()->addDays(22),
                'notes' => 'Handcrafted ceramic tiles and ornamental stone art.',
            ]
        );
        $etaService->syncShipmentDelay($s5);

        // Shipment 6: Port Congestion (RESOLVED Exception demonstration)
        $s6 = Shipment::firstOrCreate(
            ['shipment_number' => 'CT-2026-0106'],
            [
                'customer_id' => $customer3->id,
                'carrier_id' => $carriers['MAEU']->id,
                'origin_id' => $locations['NLRTM']->id,
                'destination_id' => $locations['USLAX']->id,
                'cargo_type' => 'GENERAL',
                'weight' => 21000.00,
                'volume' => 52.00,
                'current_status' => Shipment::STATUS_IN_TRANSIT,
                'expected_departure' => Carbon::now()->subDays(8),
                'actual_departure' => Carbon::now()->subDays(8),
                'expected_arrival' => Carbon::now()->addDays(3),
                'estimated_arrival' => Carbon::now()->addDays(3)->addHours(4),
                'notes' => 'Medical equipment and laboratory instruments.',
            ]
        );
        $etaService->syncShipmentDelay($s6);

        // Resolved exception
        $exc6 = ShipmentException::create([
            'shipment_id' => $s6->id,
            'exception_type' => ShipmentException::TYPE_PORT_CONGESTION,
            'severity' => ShipmentException::SEVERITY_LOW,
            'status' => ShipmentException::STATUS_RESOLVED,
            'detected_at' => Carbon::now()->subDays(7),
            'resolved_at' => Carbon::now()->subDays(6),
            'description' => 'Container yard crane maintenance caused temporary 4-hour berthing delay at Rotterdam.',
            'resolution_notes' => 'Vessel berthed successfully at Terminal 3; loaded without further impact.',
            'reported_by' => $operator2->id,
            'resolved_by' => $operator2->id,
        ]);

        // 5. Seed In-app Notifications
        Notification::create([
            'user_id' => $customer1->id,
            'shipment_id' => $s1->id,
            'type' => 'EXCEPTION_OPENED',
            'title' => 'Customs Clearance Inquiry (CT-2026-0101)',
            'message' => 'Customs phytosanitary document requested for Rotterdam terminal entry. Operator investigating.',
            'read_at' => null,
            'created_at' => Carbon::now()->subHours(18),
        ]);

        Notification::create([
            'user_id' => $customer1->id,
            'shipment_id' => $s3->id,
            'type' => 'DELAY_ALERT',
            'title' => 'Severe Weather Route Deviation (CT-2026-0103)',
            'message' => 'Shipment #CT-2026-0103 revised ETA has been moved back +28h due to Pacific storm avoidance.',
            'read_at' => null,
            'created_at' => Carbon::now()->subHours(12),
        ]);

        Notification::create([
            'user_id' => $customer2->id,
            'shipment_id' => $s2->id,
            'type' => 'STATUS_UPDATE',
            'title' => 'Shipment Delivered: CT-2026-0102',
            'message' => 'Your shipment has successfully been delivered to Port Botany, Sydney on schedule.',
            'read_at' => Carbon::now()->subHours(20),
            'created_at' => Carbon::now()->subDays(1),
        ]);

        // 6. Seed Initial Audit Logs
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'LOGIN',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        AuditLog::create([
            'user_id' => $operator->id,
            'action' => 'STATUS_CHANGE',
            'auditable_type' => Shipment::class,
            'auditable_id' => $s1->id,
            'old_values' => ['current_status' => 'PICKED_UP'],
            'new_values' => ['current_status' => 'IN_TRANSIT'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'created_at' => Carbon::now()->subDays(4),
        ]);

        // 7. Seed Maritime News & Bulletins
        $this->call(NewsSeeder::class);
    }
}
