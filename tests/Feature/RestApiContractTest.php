<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestApiContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->carrier = Carrier::create(['name' => 'Maersk', 'code' => 'MAEU', 'is_active' => true]);
        $this->origin = Location::create([
            'name' => 'Tanjung Priok',
            'code' => 'IDJKT',
            'type' => 'PORT',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'latitude' => -6.10,
            'longitude' => 106.88,
        ]);
        $this->destination = Location::create([
            'name' => 'Rotterdam',
            'code' => 'NLRTM',
            'type' => 'PORT',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.95,
            'longitude' => 4.14,
        ]);
    }

    public function test_api_shipments_crud_contract(): void
    {
        Sanctum::actingAs($this->operator);

        // 1. POST /api/shipments
        $createRes = $this->postJson('/api/shipments', [
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'weight' => 12000,
            'expected_departure' => now()->addDay()->toIso8601String(),
            'expected_arrival' => now()->addDays(7)->toIso8601String(),
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $shipmentId = $createRes->json('data.id');

        // 2. GET /api/shipments/{id}
        $getRes = $this->getJson("/api/shipments/{$shipmentId}");
        $getRes->assertStatus(200)
            ->assertJsonPath('data.id', $shipmentId)
            ->assertJsonPath('data.current_status', 'CREATED');

        // 3. PATCH /api/shipments/{id}
        $updateRes = $this->patchJson("/api/shipments/{$shipmentId}", [
            'notes' => 'Updated via REST API contract',
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('data.notes', 'Updated via REST API contract');

        // 4. POST /api/shipments/{id}/tracking
        $trackRes = $this->postJson("/api/shipments/{$shipmentId}/tracking", [
            'location_id' => $this->origin->id,
            'status' => 'BOOKED',
            'event_time' => now()->toIso8601String(),
            'description' => 'Booking space confirmed on vessel.',
        ]);
        $trackRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // 5. GET /api/shipments/{id}/tracking
        $trackListRes = $this->getJson("/api/shipments/{$shipmentId}/tracking");
        $trackListRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.events');

        // 6. POST /api/shipments/{id}/exceptions
        $excRes = $this->postJson("/api/shipments/{$shipmentId}/exceptions", [
            'exception_type' => 'PORT_CONGESTION',
            'severity' => 'LOW',
            'detected_at' => now()->toIso8601String(),
            'description' => 'Minor berth delay.',
        ]);
        $excRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $exceptionId = $excRes->json('data.id');

        // 7. PATCH /api/exceptions/{id}
        $resolveRes = $this->patchJson("/api/exceptions/{$exceptionId}", [
            'resolution_notes' => 'Berth allocated.',
        ]);
        $resolveRes->assertStatus(200)
            ->assertJsonPath('data.status', ShipmentException::STATUS_RESOLVED);
    }

    public function test_api_carriers_and_locations_list(): void
    {
        Sanctum::actingAs($this->customer);

        $this->getJson('/api/carriers')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson('/api/locations')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
