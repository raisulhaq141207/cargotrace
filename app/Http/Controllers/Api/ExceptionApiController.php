<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExceptionRequest;
use App\Http\Requests\ResolveExceptionRequest;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExceptionApiController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    /**
     * Get all exceptions for a shipment
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

        $exceptions = $shipment->exceptions()
            ->with(['reporter:id,name', 'resolver:id,name'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $exceptions,
        ]);
    }

    /**
     * Log a new exception for a shipment
     */
    public function store(CreateExceptionRequest $request, int|string $shipmentId): JsonResponse
    {
        $shipment = Shipment::find($shipmentId);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        $exception = $this->exceptionService->createException($shipment, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Exception logged successfully.',
            'data' => $exception,
        ], 201);
    }

    /**
     * Resolve or update an exception status
     */
    public function update(ResolveExceptionRequest $request, int|string $id): JsonResponse
    {
        $exception = ShipmentException::with('shipment')->find($id);

        if (!$exception) {
            return response()->json([
                'success' => false,
                'message' => 'Exception record not found.',
            ], 404);
        }

        $resolved = $this->exceptionService->resolveException($exception, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Exception resolved successfully.',
            'data' => $resolved,
        ]);
    }
}
