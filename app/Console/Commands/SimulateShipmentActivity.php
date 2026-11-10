<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * CargoTrace Real-Time Shipment Simulator
 *
 * Randomly advances shipments through their lifecycle and injects
 * realistic tracking events, so the dashboard shows live-changing data.
 *
 * Usage:
 *   php artisan cargotrace:simulate              # run once
 *   php artisan cargotrace:simulate --loop=60    # run every 60 seconds
 *   php artisan cargotrace:simulate --loop=10 --verbose
 */
class SimulateShipmentActivity extends Command
{
    protected $signature   = 'cargotrace:simulate
                                {--loop=0      : Seconds between runs (0 = run once)}
                                {--advance=1   : Max shipments to advance per run}
                                {--events=3    : Extra tracking events to inject per run}';

    protected $description = 'Simulate real-time shipment movement — advance statuses & inject tracking events';

    // Lifecycle order
    private const NEXT_STATUS = [
        Shipment::STATUS_CREATED    => Shipment::STATUS_BOOKED,
        Shipment::STATUS_BOOKED     => Shipment::STATUS_PICKED_UP,
        Shipment::STATUS_PICKED_UP  => Shipment::STATUS_IN_TRANSIT,
        Shipment::STATUS_IN_TRANSIT => Shipment::STATUS_DELIVERED,
    ];

    // Realistic mid-ocean / hub waypoints with coordinates
    private const WAYPOINTS = [
        ['location' => 'Singapore Strait', 'lat' => 1.25,   'lng' => 103.85],
        ['location' => 'Indian Ocean',      'lat' => -10.5,  'lng' => 80.0],
        ['location' => 'Gulf of Aden',      'lat' => 11.8,   'lng' => 50.5],
        ['location' => 'Suez Canal',        'lat' => 30.5,   'lng' => 32.3],
        ['location' => 'Mediterranean Sea', 'lat' => 36.0,   'lng' => 15.0],
        ['location' => 'Bay of Biscay',     'lat' => 45.0,   'lng' => -4.5],
        ['location' => 'North Sea',         'lat' => 55.5,   'lng' => 4.0],
        ['location' => 'Pacific Ocean',     'lat' => 10.0,   'lng' => 155.0],
        ['location' => 'Panama Canal',      'lat' => 9.08,   'lng' => -79.68],
        ['location' => 'Atlantic Ocean',    'lat' => 25.0,   'lng' => -40.0],
        ['location' => 'Port Klang Hub',    'lat' => 3.0,    'lng' => 101.4],
        ['location' => 'Dubai Jebel Ali',   'lat' => 25.0,   'lng' => 55.06],
        ['location' => 'Colombo Port',      'lat' => 6.95,   'lng' => 79.84],
    ];

    private const EVENT_TEMPLATES = [
        'IN_TRANSIT' => [
            'Vessel departed port on schedule.',
            'Cargo container scanned at transshipment hub.',
            'Shipment cleared intermediate customs checkpoint.',
            'Position update: en route to next waypoint.',
            'Container integrity check passed.',
            'Vessel speed adjusted due to weather advisory.',
            'ETA recalculated based on current progress.',
            'Cargo manifest verified at port authority.',
            'Refrigeration unit (reefer) check: OK.',
            'Stowage plan confirmed — no cargo shift detected.',
        ],
        'PICKED_UP' => [
            'Cargo collected from shipper warehouse.',
            'Freight loaded onto domestic feeder vessel.',
            'Bill of lading stamped at origin port.',
            'Container sealed and tagged for export clearance.',
        ],
        'BOOKED' => [
            'Booking confirmed with carrier.',
            'Export documentation submitted to customs.',
            'Freight insurance activated.',
            'Pre-carriage transport arranged.',
        ],
        'DELIVERED' => [
            'Final delivery confirmed — recipient signature obtained.',
            'Proof of delivery (POD) uploaded to system.',
            'Container returned to depot.',
        ],
    ];

    public function handle(): int
    {
        $loop     = (int) $this->option('loop');
        $maxAdv   = max(1, (int) $this->option('advance'));
        $maxEvts  = max(0, (int) $this->option('events'));

        do {
            $this->runOnce($maxAdv, $maxEvts);
            if ($loop > 0) {
                $this->line("  <fg=gray>⏱  Sleeping {$loop}s…</>");
                sleep($loop);
            }
        } while ($loop > 0);

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────
    private function runOnce(int $maxAdv, int $maxEvts): void
    {
        $ts = now()->format('H:i:s');
        $this->info("🚢  [{$ts}] CargoTrace Simulator — tick");

        // 1. Advance a few shipments along the lifecycle
        $advanceable = Shipment::whereIn('current_status', array_keys(self::NEXT_STATUS))
            ->inRandomOrder()
            ->take($maxAdv)
            ->get();

        foreach ($advanceable as $shipment) {
            $newStatus = self::NEXT_STATUS[$shipment->current_status];
            $oldStatus = $shipment->current_status;

            $shipment->current_status = $newStatus;

            if ($newStatus === Shipment::STATUS_DELIVERED) {
                $shipment->actual_arrival = now();
                $shipment->is_delayed     = false;
                $shipment->delay_severity = 'NONE';
            }

            if ($newStatus === Shipment::STATUS_IN_TRANSIT && !$shipment->estimated_arrival) {
                $shipment->estimated_arrival = now()->addDays(rand(3, 14));
            }

            $shipment->save();

            // Log a tracking event for the transition
            $waypoint = self::WAYPOINTS[array_rand(self::WAYPOINTS)];
            TrackingEvent::create([
                'shipment_id'   => $shipment->id,
                'status'        => $newStatus,
                'location_name' => $waypoint['location'],
                'latitude'      => $waypoint['lat'],
                'longitude'     => $waypoint['lng'],
                'description'   => "Status advanced: {$oldStatus} → {$newStatus}. " . $this->randomDescription($newStatus),
                'event_time'    => now(),
                'created_by'    => 1, // system / admin user
            ]);

            // Create in-app notification for the customer
            if ($shipment->customer_id) {
                Notification::create([
                    'user_id'    => $shipment->customer_id,
                    'type'       => 'SHIPMENT_UPDATE',
                    'title'      => "Shipment {$shipment->shipment_number} → {$newStatus}",
                    'message'    => "Your shipment has progressed to: {$newStatus}.",
                    'data'       => json_encode(['shipment_id' => $shipment->id]),
                    'read_at'    => null,
                ]);
            }

            $this->line("  ✅  <fg=green>{$shipment->shipment_number}</> <fg=gray>{$oldStatus}</> → <fg=cyan>{$newStatus}</>");
        }

        // 2. Inject random extra tracking events on IN_TRANSIT shipments
        if ($maxEvts > 0) {
            $inTransit = Shipment::where('current_status', Shipment::STATUS_IN_TRANSIT)
                ->inRandomOrder()
                ->take($maxEvts)
                ->get();

            foreach ($inTransit as $shipment) {
                $waypoint = self::WAYPOINTS[array_rand(self::WAYPOINTS)];
                // Add slight jitter to coordinates
                $lat = $waypoint['lat'] + (rand(-50, 50) / 100);
                $lng = $waypoint['lng'] + (rand(-50, 50) / 100);

                TrackingEvent::create([
                    'shipment_id'   => $shipment->id,
                    'status'        => Shipment::STATUS_IN_TRANSIT,
                    'location_name' => $waypoint['location'],
                    'latitude'      => round($lat, 4),
                    'longitude'     => round($lng, 4),
                    'description'   => $this->randomDescription(Shipment::STATUS_IN_TRANSIT),
                    'event_time'    => now(),
                    'created_by'    => 1,
                ]);

                $this->line("  📡  <fg=yellow>{$shipment->shipment_number}</> position update @ {$waypoint['location']}");
            }
        }

        // 3. Randomly flip delay flag on a few in-transit shipments (simulate ETA shifts)
        $flipCandidates = Shipment::where('current_status', Shipment::STATUS_IN_TRANSIT)
            ->inRandomOrder()
            ->take(2)
            ->get();

        foreach ($flipCandidates as $s) {
            $nowDelayed = (bool) rand(0, 1);
            if ($nowDelayed !== (bool) $s->is_delayed) {
                $s->is_delayed              = $nowDelayed;
                $s->delay_severity          = $nowDelayed ? (['LOW', 'MEDIUM', 'HIGH'])[rand(0, 2)] : 'NONE';
                $s->delay_duration_minutes  = $nowDelayed ? rand(60, 1440) : 0;
                $s->save();
                $flag = $nowDelayed ? '⚠️  DELAYED' : '✅ ON SCHEDULE';
                $this->line("  {$flag}  <fg=yellow>{$s->shipment_number}</>");
            }
        }

        $this->line('');
    }

    private function randomDescription(string $status): string
    {
        $templates = self::EVENT_TEMPLATES[$status] ?? ['System update recorded.'];
        return $templates[array_rand($templates)];
    }
}

