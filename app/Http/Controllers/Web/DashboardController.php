<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Shipment;
use App\Models\ShipmentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $baseQuery = Shipment::query();
        if ($user->isCustomer()) {
            $baseQuery->where('customer_id', $user->id);
        }

        // Summary stats
        $totalShipments = (clone $baseQuery)->count();
        $inTransitCount = (clone $baseQuery)->where('current_status', Shipment::STATUS_IN_TRANSIT)->count();
        $deliveredCount = (clone $baseQuery)->where('current_status', Shipment::STATUS_DELIVERED)->count();
        $delayedCount = (clone $baseQuery)->where('is_delayed', true)->count();

        // Exceptions query
        $exceptionsQuery = ShipmentException::query();
        if ($user->isCustomer()) {
            $exceptionsQuery->whereHas('shipment', fn($q) => $q->where('customer_id', $user->id));
        }
        $openExceptionsCount = (clone $exceptionsQuery)->whereIn('status', ['OPEN', 'INVESTIGATING'])->count();

        // Rates
        $delayRate = $totalShipments > 0 ? round(($delayedCount / $totalShipments) * 100, 1) : 0;
        $exceptionRate = $totalShipments > 0 ? round(($openExceptionsCount / $totalShipments) * 100, 1) : 0;

        // Shipments by Status for Chart.js
        $statusCounts = (clone $baseQuery)
            ->select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        // Shipments by Carrier for Chart.js
        $carrierCounts = (clone $baseQuery)
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id')
            ->select('carriers.name as carrier_name', DB::raw('count(shipments.id) as count'))
            ->groupBy('carriers.name')
            ->orderByDesc('count')
            ->take(6)
            ->pluck('count', 'carrier_name')
            ->toArray();

        // Recent shipments
        $recentShipments = (clone $baseQuery)
            ->with(['carrier', 'origin', 'destination', 'customer'])
            ->latest()
            ->take(6)
            ->get();

        // Critical open exceptions
        $recentExceptions = (clone $exceptionsQuery)
            ->with('shipment')
            ->whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 ELSE 4 END")
            ->latest('detected_at')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalShipments',
            'inTransitCount',
            'deliveredCount',
            'delayedCount',
            'openExceptionsCount',
            'delayRate',
            'exceptionRate',
            'statusCounts',
            'carrierCounts',
            'recentShipments',
            'recentExceptions'
        ));
    }
}
