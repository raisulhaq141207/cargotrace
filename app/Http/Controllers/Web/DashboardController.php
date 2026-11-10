<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $baseQuery = Shipment::query();
        if ($user->isCustomer()) {
            $baseQuery->where('customer_id', $user->id);
        }

        // Search in recent shipments if query present
        if ($request->filled('search')) {
            $search = $request->input('search');
            $baseQuery->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('carrier', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        // Summary stats (matching the 5 KPI cards in mockup)
        $totalShipments      = (clone $baseQuery)->count();
        $inTransitCount      = (clone $baseQuery)->where('current_status', Shipment::STATUS_IN_TRANSIT)->count();
        $deliveredCount      = (clone $baseQuery)->where('current_status', Shipment::STATUS_DELIVERED)->count();
        $delayedCount        = (clone $baseQuery)->where('is_delayed', true)->count();

        // Exceptions query
        $exceptionsQuery = ShipmentException::query();
        if ($user->isCustomer()) {
            $exceptionsQuery->whereHas('shipment', fn($q) => $q->where('customer_id', $user->id));
        }
        $openExceptionsCount = (clone $exceptionsQuery)->whereIn('status', ['OPEN', 'INVESTIGATING'])->count();

        // Delay rate
        $delayRate = $totalShipments > 0 ? round(($delayedCount / $totalShipments) * 100, 1) : 0;

        // Carrier distribution for "Delay Rate by Carrier" Donut Chart
        $carrierStats = Carrier::where('is_active', true)
            ->withCount(['shipments' => function ($q) use ($user) {
                if ($user->isCustomer()) $q->where('customer_id', $user->id);
            }])
            ->withCount(['shipments as delayed_shipments_count' => function ($q) use ($user) {
                $q->where('is_delayed', true);
                if ($user->isCustomer()) $q->where('customer_id', $user->id);
            }])
            ->get();

        $totalActiveVolume = $carrierStats->sum('shipments_count');
        $carrierChartData = [];
        $carrierColors = ['#0284c7', '#f97316', '#64748b', '#10b981', '#8b5cf6'];
        $idx = 0;

        foreach ($carrierStats as $c) {
            if ($c->shipments_count > 0 || $totalActiveVolume === 0) {
                $pct = $totalActiveVolume > 0 ? round(($c->shipments_count / $totalActiveVolume) * 100) : 20;
                $carrierChartData[] = [
                    'name'       => $c->name,
                    'count'      => $c->shipments_count,
                    'percentage' => $pct,
                    'color'      => $carrierColors[$idx % count($carrierColors)],
                ];
                $idx++;
            }
        }

        // Map Waypoints & Routes for Dashboard Map widget (Global World View)
        $mapShipments = (clone $baseQuery)
            ->with(['origin', 'destination', 'carrier', 'customer', 'trackingEvents' => fn($q) => $q->latest('event_time')])
            ->whereIn('current_status', [Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_PICKED_UP, Shipment::STATUS_BOOKED, Shipment::STATUS_CREATED])
            ->take(8)
            ->get()
            ->map(function ($s) {
                $latestEvent = $s->trackingEvents->first();
                $curLat = $latestEvent?->latitude ?? (($s->origin?->latitude + $s->destination?->latitude) / 2);
                $curLng = $latestEvent?->longitude ?? (($s->origin?->longitude + $s->destination?->longitude) / 2);

                return [
                    'id'               => $s->id,
                    'shipment_number'  => $s->shipment_number,
                    'customer'         => $s->customer?->name,
                    'carrier'          => $s->carrier?->name,
                    'status'           => $s->current_status,
                    'is_delayed'       => $s->is_delayed,
                    'origin_code'      => $s->origin?->code,
                    'origin_city'      => $s->origin?->city,
                    'origin_lat'       => $s->origin?->latitude,
                    'origin_lng'       => $s->origin?->longitude,
                    'dest_code'        => $s->destination?->code,
                    'dest_city'        => $s->destination?->city,
                    'dest_lat'         => $s->destination?->latitude,
                    'dest_lng'         => $s->destination?->longitude,
                    'current_lat'      => (float) $curLat,
                    'current_lng'      => (float) $curLng,
                    'show_url'         => route('shipments.show', $s->id),
                ];
            });

        // Recent shipments table
        $recentShipments = (clone $baseQuery)
            ->with(['carrier', 'origin', 'destination', 'customer'])
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard.index', compact(
            'totalShipments',
            'inTransitCount',
            'deliveredCount',
            'delayedCount',
            'openExceptionsCount',
            'delayRate',
            'carrierChartData',
            'mapShipments',
            'recentShipments'
        ));
    }

    /**
     * Export shipments to CSV (Export button on mockup)
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Shipment::with(['customer', 'carrier', 'origin', 'destination']);
        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        $shipments = $query->latest()->get();

        $filename = 'cargotrace_shipments_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($shipments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Shipment Number',
                'Customer',
                'Carrier',
                'Origin Hub',
                'Destination Port',
                'Cargo Type',
                'Weight (kg)',
                'Status',
                'Is Delayed',
                'Delay Hours',
                'Expected Arrival',
                'Estimated Arrival',
            ]);

            foreach ($shipments as $s) {
                fputcsv($handle, [
                    $s->shipment_number,
                    $s->customer?->name,
                    $s->carrier?->name,
                    $s->origin?->city . ' (' . $s->origin?->code . ')',
                    $s->destination?->city . ' (' . $s->destination?->code . ')',
                    $s->cargo_type,
                    $s->weight,
                    $s->current_status,
                    $s->is_delayed ? 'YES' : 'NO',
                    $s->delay_duration_minutes ? round($s->delay_duration_minutes / 60, 1) : 0,
                    $s->expected_arrival?->toIso8601String(),
                    $s->estimated_arrival?->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
