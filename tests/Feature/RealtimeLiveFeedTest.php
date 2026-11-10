<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeLiveFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Carrier $carrier;
    private Location $origin;
    private Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->carrier = Carrier::create([
            'name' => 'Maersk Line',
            'code' => 'MSK',
            'contact_email' => 'ops@maersk.com',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'name' => 'Tanjung Priok Port',
            'code' => 'IDTPP',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'latitude' => -6.101,
            'longitude' => 106.883,
            'type' => 'PORT',
        ]);

        $this->destination = Location::create([
            'name' => 'Port of Rotterdam',
            'code' => 'NLRTM',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.954,
            'longitude' => 4.133,
            'type' => 'PORT',
        ]);
    }

    public function test_authenticated_user_can_poll_dashboard_stats(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/realtime/dashboard-stats');

        $response->assertOk()
            ->assertJsonStructure([
                'timestamp',
                'total_shipments',
                'in_transit_count',
                'delivered_count',
                'delayed_count',
                'open_exceptions_count',
                'delay_rate',
                'status_counts',
                'carrier_counts',
                'recent_shipments',
                'recent_exceptions',
                'unread_notifications',
            ]);
    }

    public function test_authenticated_user_can_poll_live_activity_feed(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-TEST-001',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'CONTAINER_FCL',
            'weight' => 15000,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(5),
            'delay_severity' => 'NONE',
        ]);

        TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'location_name' => 'Suez Canal',
            'status' => Shipment::STATUS_IN_TRANSIT,
            'event_time' => now(),
            'latitude' => 30.5,
            'longitude' => 32.3,
            'description' => 'Vessel transit authorized through canal.',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/realtime/live-feed');

        $response->assertOk()
            ->assertJsonStructure([
                'timestamp',
                'events',
            ])
            ->assertJsonFragment([
                'location' => 'Suez Canal',
                'shipment_number' => 'CT-TEST-001',
            ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/realtime/dashboard-stats');
        $response->assertUnauthorized();
    }
}

