<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    /**
     * List shipments with server-side filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Shipment::with(['customer:id,name,email,company', 'carrier:id,name,code', 'origin:id,name,code,city,country', 'destination:id,name,code,city,country'])
            ->withCount(['exceptions as open_exceptions_count' => function ($q) {
                $q->whereIn('status', ['OPEN', 'INVESTIGATING']);
            }]);

        // Authorization scoping: Customers only see their own shipments
        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Search by shipment number or notes
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filters
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

        if ($request->has('is_delayed')) {
            $query->where('is_delayed', filter_var($request->is_delayed, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expected_departure', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expected_arrival', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['id', 'shipment_number', 'created_at', 'expected_departure', 'expected_arrival', 'current_status', 'weight'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $shipments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $shipments,
        ]);
    }

    /**
     * Create a new shipment
     */
    public function store(CreateShipmentRequest $request): JsonResponse
    {
        $shipment = $this->shipmentService->createShipment($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully.',
            'data' => $shipment,
        ], 201);
    }

    /**
     * Get a single shipment detail
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $shipment = Shipment::with([
            'customer:id,name,email,company,phone',
            'carrier',
            'origin',
            'destination',
            'items',
            'trackingEvents' => fn($q) => $q->orderBy('event_time', 'asc'),
            'exceptions.reporter:id,name',
            'exceptions.resolver:id,name',
        ])->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        // Authorization check: Customer can only view own shipment
        if ($request->user()->isCustomer() && $shipment->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not own this shipment.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $shipment,
        ]);
    }

    /**
     * Update shipment details
     */
    public function update(UpdateShipmentRequest $request, int|string $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        $updated = $this->shipmentService->updateShipment($shipment, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully.',
            'data' => $updated,
        ]);
    }

    /**
     * Delete shipment
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        if ($request->user()->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Customers cannot delete shipments.',
            ], 403);
        }

        $this->shipmentService->deleteShipment($shipment, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully.',
        ]);
    }
}
