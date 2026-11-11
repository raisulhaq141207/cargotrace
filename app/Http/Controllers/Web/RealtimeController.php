<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * RealtimeController — provides JSON polling endpoints for live dashboard
 * and tracking-page updates.  No WebSocket server required; the front-end
 * polls these endpoints every 5–10 seconds via fetch().
 */
class RealtimeController extends Controller
{
    /**
     * GET /realtime/dashboard-stats
     * Returns all KPI numbers + recent shipments + open exceptions as JSON.
     * Used by the dashboard polling loop.
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        $baseQuery = Shipment::query();
        if ($user->isCustomer()) {
            $baseQuery->where('customer_id', $user->id);
        }

        $totalShipments  = (clone $baseQuery)->count();
        $inTransitCount  = (clone $baseQuery)->where('current_status', Shipment::STATUS_IN_TRANSIT)->count();
        $deliveredCount  = (clone $baseQuery)->where('current_status', Shipment::STATUS_DELIVERED)->count();
        $delayedCount    = (clone $baseQuery)->where('is_delayed', true)->count();

        $exceptionsQuery = ShipmentException::query();
        if ($user->isCustomer()) {
            $exceptionsQuery->whereHas('shipment', fn($q) => $q->where('customer_id', $user->id));
        }
        $openExceptionsCount = (clone $exceptionsQuery)->whereIn('status', ['OPEN', 'INVESTIGATING'])->count();

        $delayRate    = $totalShipments > 0 ? round(($delayedCount / $totalShipments) * 100, 1) : 0;

        $statusCounts = (clone $baseQuery)
            ->select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        $carrierCounts = (clone $baseQuery)
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id')
            ->select('carriers.name as carrier_name', DB::raw('count(shipments.id) as count'))
            ->groupBy('carriers.name')
            ->orderByDesc('count')
            ->take(6)
            ->pluck('count', 'carrier_name')
            ->toArray();

        $recentShipments = (clone $baseQuery)
            ->with(['carrier', 'origin', 'destination', 'customer'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($s) {
                return [
                    'id'               => $s->id,
                    'shipment_number'  => $s->shipment_number,
                    'carrier_name'     => $s->carrier?->name,
                    'customer_name'    => $s->customer?->name,
                    'origin_code'      => $s->origin?->code,
                    'origin_city'      => $s->origin?->city,
                    'destination_code' => $s->destination?->code,
                    'destination_city' => $s->destination?->city,
                    'current_status'   => $s->current_status,
                    'is_delayed'       => $s->is_delayed,
                    'delay_severity'   => $s->delay_severity,
                    'delay_hours'      => $s->delay_duration_minutes ? round($s->delay_duration_minutes / 60, 1) : null,
                    'eta'              => $s->estimated_arrival?->format('M d, H:i') ?? $s->expected_arrival?->format('M d, H:i'),
                    'show_url'         => route('shipments.show', $s->id),
                ];
            });

        $recentExceptions = (clone $exceptionsQuery)
            ->with('shipment')
            ->whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 ELSE 4 END")
            ->latest('detected_at')
            ->take(5)
            ->get()
            ->map(function ($e) {
                return [
                    'id'             => $e->id,
                    'exception_type' => str_replace('_', ' ', $e->exception_type),
                    'severity'       => $e->severity,
                    'description'    => $e->description,
                    'shipment_number'=> $e->shipment?->shipment_number,
                    'shipment_url'   => $e->shipment_id ? route('shipments.show', $e->shipment_id) : '#',
                    'detected_ago'   => $e->detected_at?->diffForHumans(),
                ];
            });

        // Unread notification count for the bell icon
        $unreadNotifications = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Live News & Advisories
        $breakingNews = News::published()->where('is_breaking', true)->latest('published_at')->first();
        $latestNews   = News::published()->latest('published_at')->take(4)->get()->map(fn($n) => [
            'id'             => $n->id,
            'title'          => $n->title,
            'slug'           => $n->slug,
            'category'       => $n->category,
            'category_label' => $n->category_label,
            'summary'        => $n->summary,
            'is_breaking'    => (bool) $n->is_breaking,
            'time_ago'       => $n->published_at->diffForHumans(),
            'url'            => route('news.show', $n->slug),
        ]);

        return response()->json([
            'timestamp'            => now()->toIso8601String(),
            'total_shipments'      => $totalShipments,
            'in_transit_count'     => $inTransitCount,
            'delivered_count'      => $deliveredCount,
            'delayed_count'        => $delayedCount,
            'open_exceptions_count'=> $openExceptionsCount,
            'delay_rate'           => $delayRate,
            'status_counts'        => $statusCounts,
            'carrier_counts'       => $carrierCounts,
            'recent_shipments'     => $recentShipments,
            'recent_exceptions'    => $recentExceptions,
            'unread_notifications' => $unreadNotifications,
            'breaking_news'        => $breakingNews ? [
                'id'      => $breakingNews->id,
                'title'   => $breakingNews->title,
                'slug'    => $breakingNews->slug,
                'summary' => $breakingNews->summary,
                'url'     => route('news.show', $breakingNews->slug),
            ] : null,
            'latest_news'          => $latestNews,
        ]);
    }

    /**
     * GET /realtime/shipment/{id}/tracking
     * Returns the latest tracking events for a single shipment.
     * Used by the shipment-detail / tracking page polling loop.
     */
    public function shipmentTracking(Request $request, int $id): JsonResponse
    {
        $user     = Auth::user();
        $shipment = Shipment::with(['carrier', 'origin', 'destination', 'customer', 'exceptions'])->findOrFail($id);

        // Authorise: customer may only see their own shipments
        if ($user->isCustomer() && $shipment->customer_id !== $user->id) {
            abort(403);
        }

        $events = TrackingEvent::where('shipment_id', $id)
            ->orderBy('event_time', 'desc')
            ->take(20)
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'event_time'  => $e->event_time->toIso8601String(),
                'event_time_human' => $e->event_time->format('M d, Y H:i'),
                'location'    => $e->location_name,
                'status'      => $e->status,
                'description' => $e->description,
                'latitude'    => $e->latitude,
                'longitude'   => $e->longitude,
            ]);

        $openExceptions = $shipment->exceptions()
            ->whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 ELSE 4 END")
            ->get()
            ->map(fn($e) => [
                'id'             => $e->id,
                'exception_type' => str_replace('_', ' ', $e->exception_type),
                'severity'       => $e->severity,
                'description'    => $e->description,
                'detected_ago'   => $e->detected_at?->diffForHumans(),
            ]);

        return response()->json([
            'timestamp'       => now()->toIso8601String(),
            'shipment_id'     => $shipment->id,
            'current_status'  => $shipment->current_status,
            'is_delayed'      => $shipment->is_delayed,
            'delay_severity'  => $shipment->delay_severity,
            'delay_hours'     => $shipment->delay_duration_minutes ? round($shipment->delay_duration_minutes / 60, 1) : null,
            'eta'             => $shipment->estimated_arrival?->toIso8601String() ?? $shipment->expected_arrival?->toIso8601String(),
            'eta_human'       => $shipment->estimated_arrival?->format('M d, Y H:i') ?? $shipment->expected_arrival?->format('M d, Y H:i'),
            'tracking_events' => $events,
            'open_exceptions' => $openExceptions,
        ]);
    }

    /**
     * GET /realtime/live-feed
     * Returns a compact activity feed of the latest 15 tracking events
     * across ALL shipments the current user may see.
     * Shown in the sidebar / live-activity panel on the dashboard.
     */
    public function liveFeed(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = TrackingEvent::with('shipment')
            ->orderBy('event_time', 'desc')
            ->take(15);

        if ($user->isCustomer()) {
            $query->whereHas('shipment', fn($q) => $q->where('customer_id', $user->id));
        }

        $events = $query->get()->map(fn($e) => [
            'id'               => $e->id,
            'shipment_number'  => $e->shipment?->shipment_number,
            'shipment_url'     => $e->shipment_id ? route('shipments.show', $e->shipment_id) : '#',
            'status'           => $e->status,
            'location'         => $e->location_name,
            'description'      => $e->description,
            'event_time_human' => $e->event_time->diffForHumans(),
            'latitude'         => $e->latitude,
            'longitude'        => $e->longitude,
        ]);

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'events'    => $events,
        ]);
    }

    /**
     * GET /realtime/news-feed
     * Returns the latest published maritime advisories for live auto-polling.
     */
    public function newsFeed(Request $request): JsonResponse
    {
        $query = News::published()->latest('published_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $breaking = News::published()->where('is_breaking', true)->latest('published_at')->first();
        $articles = $query->take(12)->get()->map(fn($item) => [
            'id'             => $item->id,
            'title'          => $item->title,
            'slug'           => $item->slug,
            'category'       => $item->category,
            'category_label' => $item->category_label,
            'summary'        => $item->summary,
            'image_url'      => $item->image_url,
            'is_breaking'    => (bool) $item->is_breaking,
            'published_at'   => $item->published_at->format('M d, Y'),
            'time_ago'       => $item->published_at->diffForHumans(),
            'reading_time'   => max(1, ceil(str_word_count($item->content) / 200)),
            'url'            => route('news.show', $item->slug),
        ]);

        return response()->json([
            'timestamp'     => now()->toIso8601String(),
            'count'         => $articles->count(),
            'breaking_news' => $breaking ? [
                'id'      => $breaking->id,
                'title'   => $breaking->title,
                'slug'    => $breaking->slug,
                'summary' => $breaking->summary,
                'url'     => route('news.show', $breaking->slug),
            ] : null,
            'articles'      => $articles,
        ]);
    }
}

