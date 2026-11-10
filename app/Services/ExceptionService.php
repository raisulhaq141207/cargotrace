<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    public function __construct(
        protected EtaService $etaService,
        protected AuditService $auditService
    ) {}

    /**
     * Create an operational exception (e.g. CUSTOMS_HOLD, WEATHER_DELAY)
     */
    public function createException(Shipment $shipment, array $data, User $actor): ShipmentException
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $exception = ShipmentException::create([
                'shipment_id' => $shipment->id,
                'exception_type' => $data['exception_type'],
                'severity' => $data['severity'] ?? ShipmentException::SEVERITY_MEDIUM,
                'status' => ShipmentException::STATUS_OPEN,
                'detected_at' => isset($data['detected_at']) ? Carbon::parse($data['detected_at']) : now(),
                'description' => $data['description'],
                'reported_by' => $actor->id,
            ]);

            // If hours_added is specified or exception automatically extends estimated arrival:
            if (!empty($data['delay_hours_impact'])) {
                $currentEta = $shipment->estimated_arrival ?? $shipment->expected_arrival;
                $shipment->estimated_arrival = Carbon::parse($currentEta)->addHours((int) $data['delay_hours_impact']);
                $this->etaService->syncShipmentDelay($shipment);
            }

            // Also record a tracking event to show on timeline that an exception occurred!
            $shipment->trackingEvents()->create([
                'location_name' => $data['location_name'] ?? 'Exception Logged',
                'status' => 'EXCEPTION_' . $data['exception_type'],
                'event_time' => $exception->detected_at,
                'description' => "[EXCEPTION - {$data['exception_type']}] " . $data['description'],
                'created_by' => $actor->id,
                'is_exception' => true,
            ]);

            $this->auditService->log(
                'EXCEPTION_CREATED',
                $exception,
                null,
                $exception->toArray(),
                $actor->id
            );

            // In-app alert to customer
            if ($shipment->customer_id) {
                Notification::create([
                    'user_id' => $shipment->customer_id,
                    'shipment_id' => $shipment->id,
                    'type' => 'EXCEPTION_OPENED',
                    'title' => "Operational Exception: {$exception->exception_type}",
                    'message' => "Shipment #{$shipment->shipment_number} has encountered a {$exception->exception_type} ({$exception->severity} severity): {$exception->description}",
                ]);
            }

            return $exception->load(['reporter', 'shipment']);
        });
    }

    /**
     * Resolve an open exception
     */
    public function resolveException(ShipmentException $exception, array $data, User $actor): ShipmentException
    {
        return DB::transaction(function () use ($exception, $data, $actor) {
            $oldValues = $exception->toArray();

            $exception->status = ShipmentException::STATUS_RESOLVED;
            $exception->resolved_at = isset($data['resolved_at']) ? Carbon::parse($data['resolved_at']) : now();
            $exception->resolution_notes = $data['resolution_notes'] ?? 'Issue investigated and resolved.';
            $exception->resolved_by = $actor->id;
            $exception->save();

            // Re-sync shipment delays
            $this->etaService->syncShipmentDelay($exception->shipment);

            $this->auditService->log(
                'EXCEPTION_RESOLVED',
                $exception,
                $oldValues,
                $exception->toArray(),
                $actor->id
            );

            if ($exception->shipment && $exception->shipment->customer_id) {
                Notification::create([
                    'user_id' => $exception->shipment->customer_id,
                    'shipment_id' => $exception->shipment->id,
                    'type' => 'EXCEPTION_RESOLVED',
                    'title' => "Exception Resolved: {$exception->exception_type}",
                    'message' => "The {$exception->exception_type} on shipment #{$exception->shipment->shipment_number} has been resolved. Note: {$exception->resolution_notes}",
                ]);
            }

            return $exception->fresh(['reporter', 'resolver', 'shipment']);
        });
    }
}

