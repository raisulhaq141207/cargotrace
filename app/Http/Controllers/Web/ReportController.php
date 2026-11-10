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

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $baseQuery = Shipment::query();

        if ($user->isCustomer()) {
            $baseQuery->where('customer_id', $user->id);
        }

        $totalShipments = (clone $baseQuery)->count();
        $delayedCount   = (clone $baseQuery)->where('is_delayed', true)->count();
        $deliveredCount = (clone $baseQuery)->where('current_status', Shipment::STATUS_DELIVERED)->count();
        $totalWeight    = (clone $baseQuery)->sum('weight');

        $onTimeRate = $totalShipments > 0 ? round((($totalShipments - $delayedCount) / $totalShipments) * 100, 1) : 100;
        $avgDelay   = $delayedCount > 0 ? round((clone $baseQuery)->where('is_delayed', true)->avg('delay_duration_minutes') / 60, 1) : 0;

        // Carrier Performance Table
        $carrierScorecard = Carrier::where('is_active', true)
            ->withCount(['shipments' => function ($q) use ($user) {
                if ($user->isCustomer()) $q->where('customer_id', $user->id);
            }])
            ->withCount(['shipments as on_time_count' => function ($q) use ($user) {
                $q->where('is_delayed', false);
                if ($user->isCustomer()) $q->where('customer_id', $user->id);
            }])
            ->withCount(['shipments as delayed_count' => function ($q) use ($user) {
                $q->where('is_delayed', true);
                if ($user->isCustomer()) $q->where('customer_id', $user->id);
            }])
            ->get()
            ->map(function ($c) {
                $pct = $c->shipments_count > 0 ? round(($c->on_time_count / $c->shipments_count) * 100) : 100;
                $grade = $pct >= 90 ? 'A+' : ($pct >= 75 ? 'A' : ($pct >= 60 ? 'B' : 'C'));
                return [
                    'name' => $c->name,
                    'code' => $c->code,
                    'total' => $c->shipments_count,
                    'on_time' => $c->on_time_count,
                    'delayed' => $c->delayed_count,
                    'pct' => $pct,
                    'grade' => $grade,
                ];
            });

        // Exception categories breakdown
        $exceptionBreakdown = ShipmentException::select('exception_type', DB::raw('count(*) as count'))
            ->groupBy('exception_type')
            ->pluck('count', 'exception_type')
            ->toArray();

        return view('reports.index', compact(
            'totalShipments',
            'deliveredCount',
            'totalWeight',
            'onTimeRate',
            'avgDelay',
            'carrierScorecard',
            'exceptionBreakdown'
        ));
    }
}
