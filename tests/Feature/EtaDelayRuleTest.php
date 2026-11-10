<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\EtaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtaDelayRuleTest extends TestCase
{
    use RefreshDatabase;

    protected EtaService $etaService;
    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etaService = app(EtaService::class);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $carrier = Carrier::create(['name' => 'MSC', 'code' => 'MSCU', 'is_active' => true]);
        $location = Location::create([
            'name' => 'Hamburg',
            'code' => 'DEHAM',
            'type' => 'PORT',
            'city' => 'Hamburg',
            'country' => 'Germany',
            'latitude' => 53.53,
            'longitude' => 9.98,
        ]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-2026-ETA-TEST',
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $location->id,
            'destination_id' => $location->id,
            'weight' => 1000,
            'current_status' => Shipment::STATUS_IN_TRANSIT,
            'expected_departure' => Carbon::parse('2026-11-01 08:00:00'),
            'expected_arrival' => Carbon::parse('2026-11-10 12:00:00'),
        ]);
    }

    public function test_on_time_shipment_is_not_delayed(): void
    {
        $this->shipment->estimated_arrival = Carbon::parse('2026-11-10 10:00:00'); // 2 hours early
        $eval = $this->etaService->evaluateDelay($this->shipment);

        $this->assertFalse($eval['is_delayed']);
        $this->assertEquals(0, $eval['delay_duration_minutes']);
        $this->assertEquals(Shipment::SEVERITY_NONE, $eval['delay_severity']);
    }

    public function test_low_delay_between_zero_and_six_hours(): void
    {
        $this->shipment->estimated_arrival = Carbon::parse('2026-11-10 16:00:00'); // 4 hours late
        $eval = $this->etaService->evaluateDelay($this->shipment);

        $this->assertTrue($eval['is_delayed']);
        $this->assertEquals(240, $eval['delay_duration_minutes']); // 4 * 60 = 240 min
        $this->assertEquals(Shipment::SEVERITY_LOW, $eval['delay_severity']);
    }

    public function test_medium_delay_between_six_and_twenty_four_hours(): void
    {
        $this->shipment->estimated_arrival = Carbon::parse('2026-11-11 02:00:00'); // 14 hours late
        $eval = $this->etaService->evaluateDelay($this->shipment);

        $this->assertTrue($eval['is_delayed']);
        $this->assertEquals(14 * 60, $eval['delay_duration_minutes']);
        $this->assertEquals(Shipment::SEVERITY_MEDIUM, $eval['delay_severity']);
    }

    public function test_high_delay_greater_than_twenty_four_hours(): void
    {
        $this->shipment->estimated_arrival = Carbon::parse('2026-11-11 20:00:00'); // 32 hours late
        $eval = $this->etaService->evaluateDelay($this->shipment);

        $this->assertTrue($eval['is_delayed']);
        $this->assertEquals(32 * 60, $eval['delay_duration_minutes']);
        $this->assertEquals(Shipment::SEVERITY_HIGH, $eval['delay_severity']);
        $this->assertStringContainsString('delayed by 32.0 hours', $eval['delay_reason']);
    }
}
