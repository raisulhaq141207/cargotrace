<?php

namespace App\Services;

use App\Models\Shipment;
use Carbon\Carbon;

class EtaService
{
    /**
     * Re-calculate delay status, delay duration, and severity for a shipment.
     * Business Rule from Slide 08:
     * - Compare estimated arrival (or actual arrival if delivered) with expected arrival.
     * - If arrival > expected arrival: mark delayed.
     * - Severity rules:
     *     0-6 hours: LOW
     *     >6-24 hours: MEDIUM
     *     >24 hours: HIGH
     */
    public function evaluateDelay(Shipment $shipment): array
    {
        $referenceArrival = $shipment->actual_arrival ?? $shipment->estimated_arrival ?? $shipment->expected_arrival;
        $expectedArrival = $shipment->expected_arrival;

        if (!$referenceArrival || !$expectedArrival) {
            return [
                'is_delayed' => false,
                'delay_duration_minutes' => 0,
                'delay_severity' => Shipment::SEVERITY_NONE,
                'delay_reason' => null,
            ];
        }

        $refCarbon = Carbon::parse($referenceArrival);
        $expCarbon = Carbon::parse($expectedArrival);

        if ($refCarbon->gt($expCarbon)) {
            $diffMinutes = (int) $refCarbon->diffInMinutes($expCarbon, true);
            $diffHours = $diffMinutes / 60;

            if ($diffHours <= 6) {
                $severity = Shipment::SEVERITY_LOW;
            } elseif ($diffHours <= 24) {
                $severity = Shipment::SEVERITY_MEDIUM;
            } else {
                $severity = Shipment::SEVERITY_HIGH;
            }

            $hoursFormatted = number_format($diffHours, 1);
            $reason = "Arrival delayed by {$hoursFormatted} hours beyond scheduled expected arrival ({$expCarbon->format('Y-m-d H:i')}).";

            return [
                'is_delayed' => true,
                'delay_duration_minutes' => $diffMinutes,
                'delay_severity' => $severity,
                'delay_reason' => $reason,
            ];
        }

        return [
            'is_delayed' => false,
            'delay_duration_minutes' => 0,
            'delay_severity' => Shipment::SEVERITY_NONE,
            'delay_reason' => null,
        ];
    }

    /**
     * Apply delay calculation directly onto a shipment model and persist if requested.
     */
    public function syncShipmentDelay(Shipment $shipment, bool $persist = true): Shipment
    {
        $eval = $this->evaluateDelay($shipment);

        $shipment->is_delayed = $eval['is_delayed'];
        $shipment->delay_duration_minutes = $eval['delay_duration_minutes'];
        $shipment->delay_severity = $eval['delay_severity'];
        $shipment->delay_reason = $eval['delay_reason'];

        if ($persist) {
            $shipment->save();
        }

        return $shipment;
    }
}
