<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    public function __construct(
        protected WeatherService $weatherService,
        protected EtaService $etaService,
        protected AuditService $auditService
    ) {}

    /**
     * Record a historical tracking event.
     * Preserves all past history and validates chronological integrity.
     */
    public function addEvent(Shipment $shipment, array $data, User $actor): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $eventTime = isset($data['event_time']) ? Carbon::parse($data['event_time']) : now();

            // Chronological order sanity check:
            $latestEvent = $shipment->latestTrackingEvent;
            if ($latestEvent && $eventTime->lt(Carbon::parse($latestEvent->event_time))) {
                // We permit backfilling only if explicitly flagged, otherwise warn/prevent
                if (empty($data['allow_backdate'])) {
                    throw new DomainException(
                        "Chronological sequence violation: Event time ({$eventTime->toDateTimeString()}) cannot be earlier than latest recorded event ({$latestEvent->event_time->toDateTimeString()})."
                    );
                }
            }

            // Coordinate derivation: if location_id provided but lat/lng empty, populate from Location model
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            $locationName = $data['location_name'] ?? null;

            if (!empty($data['location_id'])) {
                $location = Location::find($data['location_id']);
                if ($location) {
                    $latitude = $latitude ?? $location->latitude;
                    $longitude = $longitude ?? $location->longitude;
                    $locationName = $locationName ?? "{$location->name} ({$location->code}), {$location->city}";
                }
            }

            // External Weather API integration
            $weatherSnapshot = null;
            if ($latitude !== null && $longitude !== null) {
                $weatherSnapshot = $this->weatherService->getWeather((float) $latitude, (float) $longitude);
            }

            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $data['location_id'] ?? null,
                'location_name' => $locationName ?? 'In Transit Checkpoint',
                'status' => $data['status'] ?? $shipment->current_status,
                'event_time' => $eventTime,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'description' => $data['description'],
                'created_by' => $actor->id,
                'is_exception' => (bool) ($data['is_exception'] ?? false),
                'weather_snapshot' => $weatherSnapshot,
            ]);

            // If event status represents a forward shipment status transition, update shipment
            $targetStatus = $data['status'] ?? null;
            if ($targetStatus && $targetStatus !== $shipment->current_status && $shipment->canTransitionTo($targetStatus)) {
                $shipment->current_status = $targetStatus;
                if ($targetStatus === Shipment::STATUS_PICKED_UP && !$shipment->actual_departure) {
                    $shipment->actual_departure = $eventTime;
                } elseif ($targetStatus === Shipment::STATUS_DELIVERED && !$shipment->actual_arrival) {
                    $shipment->actual_arrival = $eventTime;
                }
            }

            // If estimated arrival was passed in tracking event, update it
            if (!empty($data['estimated_arrival'])) {
                $shipment->estimated_arrival = Carbon::parse($data['estimated_arrival']);
            }

            $this->etaService->syncShipmentDelay($shipment);

            $this->auditService->log(
                'TRACKING_EVENT_ADDED',
                $event,
                null,
                $event->toArray(),
                $actor->id
            );

            // In-app notification to the customer
            if ($shipment->customer_id) {
                Notification::create([
                    'user_id' => $shipment->customer_id,
                    'shipment_id' => $shipment->id,
                    'type' => $event->is_exception ? 'EXCEPTION_OPENED' : 'STATUS_UPDATE',
                    'title' => "Tracking Update: {$event->location_name}",
                    'message' => "Shipment #{$shipment->shipment_number}: {$event->description} (Status: {$event->status})",
                ]);
            }

            return $event->load(['location', 'creator']);
        });
    }
}
