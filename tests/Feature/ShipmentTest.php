<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTest extends TestCase
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

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->customer1 = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->customer2 = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->carrier = Carrier::create([
            'name' => 'Maersk',
            'code' => 'MAEU',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'name' => 'Jakarta Port',
            'code' => 'IDJKT',
            'type' => 'PORT',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'latitude' => -6.10,
            'longitude' => 106.88,
        ]);

        $this->destination = Location::create([
            'name' => 'Rotterdam Port',
            'code' => 'NLRTM',
            'type' => 'PORT',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.95,
            'longitude' => 4.14,
        ]);
    }

    public function test_operator_can_create_shipment_with_auto_generated_number(): void
    {
        $payload = [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'weight' => 5000,
            'expected_departure' => now()->addDay()->format('Y-m-d H:i:s'),
            'expected_arrival' => now()->addDays(10)->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->operator)
            ->post(route('shipments.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shipments', [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'current_status' => Shipment::STATUS_CREATED,
        ]);
    }

    public function test_customer_cannot_create_shipment_directly(): void
    {
        $payload = [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'weight' => 1000,
            'expected_departure' => now()->addDay()->format('Y-m-d H:i:s'),
            'expected_arrival' => now()->addDays(5)->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->customer1)
            ->post(route('shipments.store'), $payload);

        $response->assertStatus(403);
    }

    public function test_customer_can_only_view_own_shipment(): void
    {
        $shipment1 = Shipment::create([
            'shipment_number' => 'CT-2026-TEST1',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'weight' => 2000,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
            'current_status' => Shipment::STATUS_CREATED,
        ]);

        $shipment2 = Shipment::create([
            'shipment_number' => 'CT-2026-TEST2',
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'weight' => 3000,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
            'current_status' => Shipment::STATUS_CREATED,
        ]);

        // Customer 1 viewing own shipment -> 200 OK
        $this->actingAs($this->customer1)
            ->get(route('shipments.show', $shipment1))
            ->assertStatus(200);

        // Customer 1 viewing Customer 2's shipment -> 403 Forbidden
        $this->actingAs($this->customer1)
            ->get(route('shipments.show', $shipment2))
            ->assertStatus(403);
    }

    public function test_customer_cannot_delete_shipment(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-2026-TEST3',
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'weight' => 2000,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(5),
            'current_status' => Shipment::STATUS_CREATED,
        ]);

        $this->actingAs($this->customer1)
            ->deleteJson("/api/shipments/{$shipment->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id]);
    }

    public function test_shipment_validation_requires_positive_weight_and_valid_dates(): void
    {
        $invalidPayload = [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'weight' => -50, // Negative weight
            'expected_departure' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'expected_arrival' => now()->format('Y-m-d H:i:s'), // Arrival earlier than departure
        ];

        $response = $this->actingAs($this->operator)
            ->postJson('/api/shipments', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['weight', 'expected_arrival']);
    }
}

