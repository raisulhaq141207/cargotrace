<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $customer;
    protected Shipment $shipment;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $carrier = Carrier::create(['name' => 'ONE', 'code' => 'ONEY', 'is_active' => true]);
        $this->location = Location::create([
            'name' => 'Singapore Port',
            'code' => 'SGSIN',
            'type' => 'PORT',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'latitude' => 1.26,
            'longitude' => 103.82,
        ]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-2026-LIFECYCLE',
            'customer_id' => $this->customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $this->location->id,
            'destination_id' => $this->location->id,
            'cargo_type' => 'GENERAL',
            'weight' => 5000,
            'current_status' => Shipment::STATUS_CREATED,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
        ]);
    }

    public function test_valid_status_progression(): void
    {
        $shipmentService = app(ShipmentService::class);

        // CREATED -> BOOKED
        $shipmentService->transitionStatus($this->shipment, Shipment::STATUS_BOOKED, $this->operator);
        $this->assertEquals(Shipment::STATUS_BOOKED, $this->shipment->fresh()->current_status);

        // BOOKED -> PICKED_UP
        $shipmentService->transitionStatus($this->shipment, Shipment::STATUS_PICKED_UP, $this->operator);
        $this->assertEquals(Shipment::STATUS_PICKED_UP, $this->shipment->fresh()->current_status);

        // PICKED_UP -> IN_TRANSIT
        $shipmentService->transitionStatus($this->shipment, Shipment::STATUS_IN_TRANSIT, $this->operator);
        $this->assertEquals(Shipment::STATUS_IN_TRANSIT, $this->shipment->fresh()->current_status);

        // IN_TRANSIT -> DELIVERED
        $shipmentService->transitionStatus($this->shipment, Shipment::STATUS_DELIVERED, $this->operator);
        $this->assertEquals(Shipment::STATUS_DELIVERED, $this->shipment->fresh()->current_status);
        $this->assertNotNull($this->shipment->fresh()->actual_arrival);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $shipmentService = app(ShipmentService::class);

        // Directly transition from CREATED to DELIVERED is illegal
        $shipmentService->transitionStatus($this->shipment, Shipment::STATUS_DELIVERED, $this->operator);
    }

    public function test_terminal_status_cannot_be_reverted(): void
    {
        $shipmentService = app(ShipmentService::class);

        $this->shipment->current_status = Shipment::STATUS_DELIVERED;
        $this->shipment->save();

        $this->expectException(DomainException::class);
        $shipmentService->transitionStatus($this->shipment, Shipment::STATUS_IN_TRANSIT, $this->operator);
    }

    public function test_historical_tracking_events_accumulate_without_overwriting(): void
    {
        $trackingService = app(TrackingService::class);

        $event1 = $trackingService->addEvent($this->shipment, [
            'location_id' => $this->location->id,
            'status' => 'PICKED_UP',
            'event_time' => now()->subHours(5),
            'description' => 'Container sealed at origin berth.',
        ], $this->operator);

        $event2 = $trackingService->addEvent($this->shipment, [
            'location_name' => 'Open Sea Corridor',
            'status' => 'IN_TRANSIT',
            'event_time' => now()->subHours(2),
            'latitude' => 2.5,
            'longitude' => 104.0,
            'description' => 'Vessel at 16 knots cruising speed.',
        ], $this->operator);

        $this->assertEquals(2, $this->shipment->trackingEvents()->count());
        $this->assertDatabaseHas('tracking_events', ['id' => $event1->id]);
        $this->assertDatabaseHas('tracking_events', ['id' => $event2->id]);
    }
}
