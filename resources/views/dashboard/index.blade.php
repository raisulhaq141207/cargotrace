@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@section('content')
<div class="space-y-5">

    <!-- 1. Top Row: 5 Summary KPI Metric Cards (Matches Mockup Exactly) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- Total Shipments -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-box"></i>
            </div>
            <div>
                <div class="text-[11px] font-medium text-slate-500">Total Shipments</div>
                <div id="stat-total" class="text-2xl font-bold text-slate-900 leading-tight">{{ number_format($totalShipments) }}</div>
            </div>
        </div>

        <!-- In Transit -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div>
                <div class="text-[11px] font-medium text-slate-500">In Transit</div>
                <div id="stat-intransit" class="text-2xl font-bold text-slate-900 leading-tight">{{ number_format($inTransitCount) }}</div>
            </div>
        </div>

        <!-- Delivered -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-truck"></i>
            </div>
            <div>
                <div class="text-[11px] font-medium text-slate-500">Delivered</div>
                <div id="stat-delivered" class="text-2xl font-bold text-slate-900 leading-tight">{{ number_format($deliveredCount) }}</div>
            </div>
        </div>

        <!-- Delayed -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center text-lg shrink-0">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <div class="text-[11px] font-medium text-slate-500">Delayed</div>
                <div id="stat-delayed" class="text-2xl font-bold text-slate-900 leading-tight">{{ number_format($delayedCount) }}</div>
            </div>
        </div>

        <!-- Open Exceptions -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3.5 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-500 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="text-[11px] font-medium text-slate-500">Open Exceptions</div>
                <div id="stat-exceptions" class="text-2xl font-bold text-slate-900 leading-tight">{{ number_format($openExceptionsCount) }}</div>
            </div>
        </div>

    </div>

    <!-- 2. Middle Row: 3 Widget Cards (Shipments by Status, Delay Rate by Carrier, Shipment Map) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Widget 1: Shipments by Status (Bar Chart + Total Badge) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm lg:col-span-5 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Shipments by Status</h3>
                        <p class="text-[11px] text-slate-400">Overview of shipments by status</p>
                    </div>
                </div>

                <!-- Legend on Top -->
                <div class="flex flex-wrap items-center gap-3 mt-3 text-[10px] text-slate-600 font-medium">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#0284c7]"></span> In Transit (<span id="legend-intransit">{{ $inTransitCount }}</span>)
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#10b981]"></span> Delivered (<span id="legend-delivered">{{ $deliveredCount }}</span>)
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#f97316]"></span> Delayed (<span id="legend-delayed">{{ $delayedCount }}</span>)
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#ef4444]"></span> Open Exceptions (<span id="legend-exceptions">{{ $openExceptionsCount }}</span>)
                    </span>
                </div>
            </div>

            <!-- Bar Chart & Total Box -->
            <div class="flex items-end gap-3 mt-4">
                <div class="flex-1 h-44 relative">
                    <canvas id="statusBarChart"></canvas>
                </div>
                <div class="bg-sky-50/70 border border-sky-100 rounded-xl p-3 text-center shrink-0 w-28">
                    <div id="stat-total-badge" class="text-2xl font-extrabold text-sky-600 leading-tight">{{ $totalShipments }}</div>
                    <div class="text-[10px] text-slate-500 font-medium mt-0.5">Total Shipments</div>
                </div>
            </div>
        </div>

        <!-- Widget 2: Delay Rate by Carrier (Donut Chart + Center Text + Legend List) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm lg:col-span-4 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Delay Rate by Carrier</h3>
                <p class="text-[11px] text-slate-400">Distribution of delay rates across carriers</p>
            </div>

            <!-- Donut + Legend Layout -->
            <div class="flex items-center justify-between gap-4 my-auto pt-2">
                <!-- Donut Chart with Center Text Overlay -->
                <div class="relative w-36 h-36 shrink-0 flex items-center justify-center">
                    <canvas id="carrierDonutChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                        <span id="donut-avg-rate" class="text-lg font-extrabold text-slate-900 leading-none">{{ $delayRate }}%</span>
                        <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-tight mt-0.5">Average Delay Rate</span>
                    </div>
                </div>

                <!-- Carrier Legend List -->
                <div class="flex-1 space-y-2.5 pr-2">
                    @forelse($carrierChartData as $c)
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background-color: {{ $c['color'] }}"></span>
                            <span class="font-medium text-slate-700 truncate text-[11px]">{{ $c['name'] }}</span>
                        </div>
                        <span class="font-bold text-slate-600 text-[11px] shrink-0 ml-1">{{ $c['percentage'] }}%</span>
                    </div>
                    @empty
                    <div class="text-xs text-slate-400">No active carriers.</div>
                    @endforelse
                </div>
            </div>

            <div class="text-[10px] text-slate-400 pt-2 border-t border-slate-100 flex items-center justify-between">
                <span>Threshold: {{ config('cargotrace.delay_threshold_hours', 12) }}h</span>
                <span class="text-emerald-600 font-semibold">Active Fleet</span>
            </div>
        </div>

        <!-- Widget 3: Shipment Map (Interactive Leaflet Map with World View) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm lg:col-span-3 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Shipment Map</h3>
                    <p class="text-[11px] text-slate-400">Real-time global routes &amp; locations</p>
                </div>
                <a href="{{ route('tracking.index') }}" class="text-[11px] font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                    <span>World Map</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                </a>
            </div>

            <!-- Mini Map Canvas -->
            <div class="relative rounded-xl overflow-hidden border border-slate-200 h-44">
                <div id="shipmentMiniMap" class="w-full h-full"></div>
            </div>

            <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium pt-2.5">
                <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Origin (A)</span>
                <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-sky-500"></span> Transit (B)</span>
                <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Target (C)</span>
            </div>
        </div>

    </div>

    <!-- 3. Bottom Row: Recent Shipments Table (Matches Mockup Table Design) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Table Header Bar with Filter & Export -->
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent Shipments</h3>
                <p class="text-xs text-slate-400">Live overview of all shipments</p>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- Filter Dropdown Button -->
                <div class="relative" id="filterDropdownContainer">
                    <button type="button" onclick="document.getElementById('filterMenu').classList.toggle('hidden')"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                        <i class="fa-solid fa-arrow-down-wide-short text-slate-400 text-[11px]"></i>
                        <span>Filter</span>
                        <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"></i>
                    </button>
                    <!-- Filter Dropdown Menu -->
                    <div id="filterMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 p-2 z-20 text-xs">
                        <a href="{{ route('dashboard') }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700">All Shipments</a>
                        <a href="{{ route('dashboard', ['status' => 'IN_TRANSIT']) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700">In Transit Only</a>
                        <a href="{{ route('dashboard', ['status' => 'DELAYED']) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700">Delayed Only</a>
                        <a href="{{ route('dashboard', ['status' => 'DELIVERED']) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700">Delivered Only</a>
                    </div>
                </div>

                <!-- Export CSV Button (Real Working Download) -->
                <a href="{{ route('shipments.export') }}"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition shadow-sm">
                    <i class="fa-solid fa-download text-slate-400 text-[11px]"></i>
                    <span>Export</span>
                </a>
            </div>
        </div>

        <!-- Shipments Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-400 font-semibold text-[11px]">
                    <tr>
                        <th class="py-3 px-5">Shipment No.</th>
                        <th class="py-3 px-5">Customer</th>
                        <th class="py-3 px-5">Carrier</th>
                        <th class="py-3 px-5">Origin</th>
                        <th class="py-3 px-5">Destination</th>
                        <th class="py-3 px-5">ETA</th>
                        <th class="py-3 px-5">Status</th>
                    </tr>
                </thead>
                <tbody id="dashboard-shipments-tbody" class="divide-y divide-slate-100 font-medium">
                    @forelse($recentShipments as $s)
                    <tr class="hover:bg-slate-50/60 transition" data-id="{{ $s->id }}">
                        <!-- Shipment No (Blue Link) -->
                        <td class="py-3.5 px-5 font-bold text-sky-600 hover:underline">
                            <a href="{{ route('shipments.show', $s) }}">
                                {{ $s->shipment_number }}
                            </a>
                        </td>

                        <!-- Customer -->
                        <td class="py-3.5 px-5 text-slate-700">
                            {{ $s->customer?->company ?? $s->customer?->name }}
                        </td>

                        <!-- Carrier -->
                        <td class="py-3.5 px-5 text-slate-700">
                            {{ $s->carrier?->name }}
                        </td>

                        <!-- Origin -->
                        <td class="py-3.5 px-5 text-slate-600">
                            {{ $s->origin?->city }}, {{ $s->origin?->country }}
                        </td>

                        <!-- Destination -->
                        <td class="py-3.5 px-5 text-slate-600">
                            {{ $s->destination?->city }}, {{ $s->destination?->country }}
                        </td>

                        <!-- ETA -->
                        <td class="py-3.5 px-5 text-slate-600 font-mono text-[11px]">
                            {{ $s->estimated_arrival?->format('Y-m-d') ?? $s->expected_arrival?->format('Y-m-d') }}
                        </td>

                        <!-- Status Badge (Matching Red DELAYED, Cyan IN TRANSIT, Green DELIVERED) -->
                        <td class="py-3.5 px-5">
                            @if($s->is_delayed)
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-600 text-white shadow-sm">
                                DELAYED
                            </span>
                            @elseif($s->current_status === 'IN_TRANSIT')
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-[#0284c7] text-white shadow-sm">
                                IN TRANSIT
                            </span>
                            @elseif($s->current_status === 'DELIVERED')
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-600 text-white shadow-sm">
                                DELIVERED
                            </span>
                            @elseif($s->current_status === 'BOOKED')
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-sky-500 text-white shadow-sm">
                                BOOKED
                            </span>
                            @elseif($s->current_status === 'PICKED_UP')
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-500 text-white shadow-sm">
                                PICKED UP
                            </span>
                            @else
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-200 text-slate-700">
                                {{ $s->current_status }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No shipments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // ─────────────────────────────────────────────────────────────────
        // 1. Status Bar Chart (4 Bars: In Transit, Delivered, Delayed, Open Exc)
        // ─────────────────────────────────────────────────────────────────
        const ctxBar = document.getElementById('statusBarChart').getContext('2d');
        const statusBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['In Transit', 'Delivered', 'Delayed', 'Exceptions'],
                datasets: [{
                    data: [
                        {{ $inTransitCount }},
                        {{ $deliveredCount }},
                        {{ $delayedCount }},
                        {{ $openExceptionsCount }}
                    ],
                    backgroundColor: [
                        '#0284c7', // In Transit (Blue)
                        '#10b981', // Delivered (Green)
                        '#f97316', // Delayed (Orange)
                        '#ef4444'  // Exceptions (Red)
                    ],
                    borderRadius: 6,
                    barPercentage: 0.65,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.raw} shipments`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 2. Carrier Donut Chart (Delay Rate by Carrier)
        // ─────────────────────────────────────────────────────────────────
        const carrierData = {!! json_encode($carrierChartData) !!};
        const donutLabels = carrierData.map(c => c.name);
        const donutValues = carrierData.map(c => c.percentage);
        const donutColors = carrierData.map(c => c.color);

        const ctxDonut = document.getElementById('carrierDonutChart').getContext('2d');
        const carrierDonutChart = new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: donutLabels.length ? donutLabels : ['Standard'],
                datasets: [{
                    data: donutValues.length ? donutValues : [100],
                    backgroundColor: donutColors.length ? donutColors : ['#0284c7'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '72%'
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 3. Mini Leaflet World Map with Real Shipments & Waypoints
        // ─────────────────────────────────────────────────────────────────
        const mapShipments = {!! json_encode($mapShipments) !!};

        const miniMap = L.map('shipmentMiniMap', {
            zoomControl: false,
            attributionControl: false
        }).setView([20.0, 40.0], 1.5); // Global World Centering

        // CartoDB / OSM Global Tile Layer
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 18,
            subdomains: 'abcd'
        }).addTo(miniMap);

        // Zoom control in bottom right
        L.control.zoom({ position: 'bottomright' }).addTo(miniMap);

        // Render actual active world shipments
        const allBounds = [];

        mapShipments.forEach(s => {
            if (!s.origin_lat || !s.dest_lat) return;

            // Route line between Origin & Destination
            const routeLine = [
                [s.origin_lat, s.origin_lng],
                [s.current_lat, s.current_lng],
                [s.dest_lat, s.dest_lng]
            ];
            allBounds.push([s.origin_lat, s.origin_lng]);
            allBounds.push([s.dest_lat, s.dest_lng]);

            L.polyline(routeLine, {
                color: s.is_delayed ? '#ef4444' : '#0284c7',
                weight: 2,
                opacity: 0.7,
                dashArray: '4, 5'
            }).addTo(miniMap);

            // Vessel Marker (B)
            const bIcon = L.divIcon({
                className: 'mini-map-pin',
                html: `<div style="background-color:${s.is_delayed ? '#ef4444' : '#0284c7'}; width:18px; height:18px; border-radius:50%; border:2px solid #ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.3); color:#ffffff; font-weight:bold; font-size:9px; display:flex; align-items:center; justify-content:center;">B</div>`,
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });

            L.marker([s.current_lat, s.current_lng], { icon: bIcon })
                .bindPopup(`<b>#${s.shipment_number}</b><br><small>${s.carrier || ''}</small><br><a href="${s.show_url}" style="color:#0284c7;font-weight:bold;">Track &rarr;</a>`)
                .addTo(miniMap);
        });

        // Always show primary global corridor if bounds found
        if (allBounds.length > 0) {
            miniMap.fitBounds(allBounds, { padding: [15, 15] });
        } else {
            // Default world coordinates (Jakarta to Rotterdam)
            miniMap.setView([15.0, 60.0], 2);
        }

        // ─────────────────────────────────────────────────────────────────
        // 4. Real-time Live Polling Engine (Every 5 seconds)
        // ─────────────────────────────────────────────────────────────────
        async function fetchLiveDashboard() {
            try {
                const res = await fetch('{{ route('realtime.dashboard-stats') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();

                // Update 5 KPI Cards
                updateText('stat-total', data.total_shipments);
                updateText('stat-intransit', data.in_transit_count);
                updateText('stat-delivered', data.delivered_count);
                updateText('stat-delayed', data.delayed_count);
                updateText('stat-exceptions', data.open_exceptions_count);
                updateText('stat-total-badge', data.total_shipments);

                // Update Legend numbers
                updateText('legend-intransit', data.in_transit_count);
                updateText('legend-delivered', data.delivered_count);
                updateText('legend-delayed', data.delayed_count);
                updateText('legend-exceptions', data.open_exceptions_count);

                // Update Bar Chart
                if (statusBarChart) {
                    statusBarChart.data.datasets[0].data = [
                        data.in_transit_count,
                        data.delivered_count,
                        data.delayed_count,
                        data.open_exceptions_count
                    ];
                    statusBarChart.update();
                }

                // Update Average Delay Rate text
                const avgEl = document.getElementById('donut-avg-rate');
                if (avgEl && data.delay_rate !== undefined) {
                    avgEl.textContent = data.delay_rate + '%';
                }

                // Update table if shipments provided
                if (data.recent_shipments && data.recent_shipments.length > 0) {
                    renderTableRows(data.recent_shipments);
                }
            } catch (err) {
                // Silently ignore network retry errors
            }
        }

        function updateText(id, val) {
            const el = document.getElementById(id);
            if (el && String(el.textContent).trim() !== String(val)) {
                el.textContent = val;
                el.classList.add('text-sky-500');
                setTimeout(() => el.classList.remove('text-sky-500'), 400);
            }
        }

        function renderTableRows(shipments) {
            const tbody = document.getElementById('dashboard-shipments-tbody');
            if (!tbody) return;

            tbody.innerHTML = shipments.map(s => {
                let badge = '';
                if (s.is_delayed) {
                    badge = `<span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-600 text-white shadow-sm">DELAYED</span>`;
                } else if (s.current_status === 'IN_TRANSIT') {
                    badge = `<span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-[#0284c7] text-white shadow-sm">IN TRANSIT</span>`;
                } else if (s.current_status === 'DELIVERED') {
                    badge = `<span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-600 text-white shadow-sm">DELIVERED</span>`;
                } else {
                    badge = `<span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-200 text-slate-700">${s.current_status}</span>`;
                }

                return `
                <tr class="hover:bg-slate-50/60 transition" data-id="${s.id}">
                    <td class="py-3.5 px-5 font-bold text-sky-600 hover:underline">
                        <a href="${s.show_url}">${s.shipment_number}</a>
                    </td>
                    <td class="py-3.5 px-5 text-slate-700">${s.customer_name || '-'}</td>
                    <td class="py-3.5 px-5 text-slate-700">${s.carrier_name || '-'}</td>
                    <td class="py-3.5 px-5 text-slate-600">${s.origin_city || '-'}</td>
                    <td class="py-3.5 px-5 text-slate-600">${s.destination_city || '-'}</td>
                    <td class="py-3.5 px-5 text-slate-600 font-mono text-[11px]">${s.eta ? s.eta.split(',')[0] : '-'}</td>
                    <td class="py-3.5 px-5">${badge}</td>
                </tr>`;
            }).join('');
        }

        // Poll every 5 seconds
        setInterval(fetchLiveDashboard, 5000);
    });
</script>
@endpush
