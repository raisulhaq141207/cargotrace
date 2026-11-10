<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTrackingEventRequest;
use App\Models\Shipment;
use App\Services\TrackingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

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
