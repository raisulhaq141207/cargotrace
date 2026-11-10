<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExceptionRequest;
use App\Http\Requests\ResolveExceptionRequest;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExceptionController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    /**
     * Dedicated Exceptions Management Screen
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = ShipmentException::with(['shipment.carrier', 'shipment.customer', 'shipment.origin', 'shipment.destination']);

        if ($user->isCustomer()) {
            $query->whereHas('shipment', fn($q) => $q->where('customer_id', $user->id));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('exception_type', $request->input('type'));
        }

        // Summary counts
        $baseQuery = clone $query;
        $openCount     = (clone $query)->whereIn('status', ['OPEN', 'INVESTIGATING'])->count();
        $criticalCount = (clone $query)->where('severity', 'CRITICAL')->whereIn('status', ['OPEN', 'INVESTIGATING'])->count();
        $resolvedCount = (clone $query)->where('status', 'RESOLVED')->count();

        $exceptions = $query->latest('detected_at')->paginate(12)->withQueryString();

        return view('exceptions.index', compact(
            'exceptions',
            'openCount',
            'criticalCount',
            'resolvedCount'
        ));
    }

    public function store(CreateExceptionRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->exceptionService->createException($shipment, $request->validated(), Auth::user());

        return back()->with('warning', 'Operational exception registered and customer notified.');
    }

    public function resolve(ResolveExceptionRequest $request, ShipmentException $exception): RedirectResponse
    {
        $this->exceptionService->resolveException($exception, $request->validated(), Auth::user());

        return back()->with('success', 'Operational exception marked as RESOLVED.');
    }
}
