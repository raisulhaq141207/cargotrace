<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTrackingEventRequest;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingApiController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Get tracking history / timeline for a shipment
     */
    public function index(Request $request, int|string $shipmentId): JsonResponse
    {
        $shipment = Shipment::find($shipmentId);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        if ($request->user()->isCustomer() && $shipment->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not own this shipment.',
            ], 403);
        }

        $events = $shipment->trackingEvents()
            ->with(['location', 'creator:id,name,role'])
            ->orderBy('event_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment_id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'current_status' => $shipment->current_status,
                'is_delayed' => $shipment->is_delayed,
                'events' => $events,
            ],
        ]);
    }

    /**
     * Add a historical tracking event
     */
    public function store(CreateTrackingEventRequest $request, int|string $shipmentId): JsonResponse
    {
        $shipment = Shipment::find($shipmentId);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        $event = $this->trackingService->addEvent($shipment, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tracking event logged successfully.',
            'data' => $event,
        ], 201);
    }
}
