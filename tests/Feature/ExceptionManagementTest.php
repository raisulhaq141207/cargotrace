<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\ExceptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExceptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $customer;
    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $carrier = Carrier::create(['name' => 'Evergreen', 'code' => 'EMC', 'is_active' => true]);
        $location = Location::create([
            'name' => 'Rotterdam',
            'code' => 'NLRTM',
            'type' => 'PORT',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'latitude' => 51.95,
            'longitude' => 4.14,
        ]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-2026-EXC-TEST',
            'customer_id' => $this->customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $location->id,
            'destination_id' => $location->id,
            'weight' => 4000,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'expected_departure' => Carbon::now()->subDays(2),
            'expected_arrival' => Carbon::now()->addDays(3),
        ]);
    }

    public function test_operator_can_create_operational_exception_with_delay_impact(): void
    {
        $exceptionService = app(ExceptionService::class);

        $exception = $exceptionService->createException($this->shipment, [
            'exception_type' => ShipmentException::TYPE_WEATHER_DELAY,
            'severity' => ShipmentException::SEVERITY_HIGH,
            'detected_at' => now(),
            'description' => 'Tropical typhoon forcing route diversion around Luzon Strait.',
            'delay_hours_impact' => 20,
        ], $this->operator);

        $this->assertEquals(ShipmentException::STATUS_OPEN, $exception->status);
        $this->assertTrue($this->shipment->fresh()->is_delayed);
        $this->assertEquals(Shipment::SEVERITY_MEDIUM, $this->shipment->fresh()->delay_severity);

        // Verify customer received in-app notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'EXCEPTION_OPENED',
            'shipment_id' => $this->shipment->id,
        ]);
    }

    public function test_operator_can_resolve_open_exception(): void
    {
        $exceptionService = app(ExceptionService::class);

        $exception = $exceptionService->createException($this->shipment, [
            'exception_type' => ShipmentException::TYPE_CUSTOMS_HOLD,
            'severity' => ShipmentException::SEVERITY_MEDIUM,
            'detected_at' => now()->subDay(),
            'description' => 'Bill of lading discrepancy.',
        ], $this->operator);

        $resolved = $exceptionService->resolveException($exception, [
            'resolved_at' => now(),
            'resolution_notes' => 'Updated electronic manifest submitted and accepted by customs.',
        ], $this->operator);

        $this->assertEquals(ShipmentException::STATUS_RESOLVED, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertEquals($this->operator->id, $resolved->resolved_by);

        // Verify customer received resolution notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'EXCEPTION_RESOLVED',
            'shipment_id' => $this->shipment->id,
        ]);
    }
}

