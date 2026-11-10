<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Shipment::with(['customer', 'carrier', 'origin', 'destination'])
            ->withCount(['exceptions as open_exceptions_count' => fn($q) => $q->whereIn('status', ['OPEN', 'INVESTIGATING'])]);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        if ($request->filled('origin_id')) {
            $query->where('origin_id', $request->origin_id);
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }

        if ($request->has('is_delayed') && $request->is_delayed !== '') {
            $query->where('is_delayed', filter_var($request->is_delayed, FILTER_VALIDATE_BOOLEAN));
        }

        $shipments = $query->latest()->paginate(10)->withQueryString();

        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('shipments.index', compact('shipments', 'carriers', 'locations'));
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::user()->isCustomer()) {
            abort(403, 'Customers cannot create shipments directly.');
        }

        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $customers = User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get();
        $suggestedNumber = $this->shipmentService->generateShipmentNumber();

        return view('shipments.create', compact('carriers', 'locations', 'customers', 'suggestedNumber'));
    }

    public function store(CreateShipmentRequest $request): RedirectResponse
    {
        $shipment = $this->shipmentService->createShipment($request->validated(), Auth::user());

        return redirect()->route('shipments.show', $shipment)->with('success', "Shipment #{$shipment->shipment_number} created successfully.");
    }

    public function show(Shipment $shipment): View
    {
        $user = Auth::user();

        if ($user->isCustomer() && $shipment->customer_id !== $user->id) {
            abort(403, 'Forbidden. You do not own this shipment.');
        }

        $shipment->load([
            'customer',
            'carrier',
            'origin',
            'destination',
            'items',
            'trackingEvents' => fn($q) => $q->orderBy('event_time', 'asc'),
            'trackingEvents.location',
            'trackingEvents.creator',
            'exceptions' => fn($q) => $q->orderBy('detected_at', 'desc'),
            'exceptions.reporter',
            'exceptions.resolver',
        ]);

        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('shipments.show', compact('shipment', 'locations'));
    }

    public function edit(Shipment $shipment): View|RedirectResponse
    {
        if (Auth::user()->isCustomer()) {
            abort(403, 'Forbidden.');
        }

        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $customers = User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get();

        return view('shipments.edit', compact('shipment', 'carriers', 'locations', 'customers'));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        try {
            $this->shipmentService->updateShipment($shipment, $request->validated(), Auth::user());
            return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated successfully.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function transition(Request $request, Shipment $shipment): RedirectResponse
    {
        if (Auth::user()->isCustomer()) {
            abort(403, 'Customers cannot modify status.');
        }

        $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->shipmentService->transitionStatus($shipment, $request->status, Auth::user(), $request->reason);
            return back()->with('success', "Status successfully updated to {$request->status}.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        try {
            $this->shipmentService->deleteShipment($shipment, Auth::user());
            return redirect()->route('shipments.index')->with('success', 'Shipment deleted successfully.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

