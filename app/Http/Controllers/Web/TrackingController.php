<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTrackingEventRequest;
use App\Models\Location;
use App\Models\Shipment;
use App\Services\TrackingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Interactive Global World Tracking Map (Peta Dunia Pengiriman)
     */
    public function map(): View
    {
        $user = Auth::user();

        $query = Shipment::with(['origin', 'destination', 'carrier', 'customer', 'trackingEvents' => fn($q) => $q->orderBy('event_time')]);
        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        $shipments = $query->get();
        $locations = Location::where('is_active', true)->get();

        $inTransitCount = $shipments->where('current_status', Shipment::STATUS_IN_TRANSIT)->count();
        $delayedCount   = $shipments->where('is_delayed', true)->count();
        $deliveredCount = $shipments->where('current_status', Shipment::STATUS_DELIVERED)->count();

        return view('tracking.map', compact(
            'shipments',
            'locations',
            'inTransitCount',
            'delayedCount',
            'deliveredCount'
        ));
    }

    public function store(CreateTrackingEventRequest $request, Shipment $shipment): RedirectResponse
    {
        try {
            $this->trackingService->addEvent($shipment, $request->validated(), Auth::user());
            return back()->with('success', 'Tracking checkpoint recorded successfully.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
