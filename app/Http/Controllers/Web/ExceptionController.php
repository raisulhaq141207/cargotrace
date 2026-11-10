<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExceptionRequest;
use App\Http\Requests\ResolveExceptionRequest;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ExceptionController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

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

