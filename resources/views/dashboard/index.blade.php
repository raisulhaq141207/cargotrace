@extends('layouts.app')

@section('title', 'Operations Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Top Greeting & Live Control Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ auth()->user()->isCustomer() ? 'Customer Shipment Portal' : 'Global Operations Center' }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ auth()->user()->isAdmin() ? 'bg-amber-100 text-amber-800' : (auth()->user()->isOperator() ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800') }}">
                    {{ auth()->user()->role }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Real-time visibility metrics, ETA tracking, and operational exception monitoring.
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Live Status & Refresh Control -->
            <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                <span class="relative flex h-2 w-2">
                    <span id="poll-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span id="poll-dot" class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span id="poll-status" class="font-semibold text-slate-700">Auto-refresh <span id="poll-countdown" class="text-blue-600 font-bold">5s</span></span>
                <button type="button" id="btn-toggle-poll" title="Pause / Resume Live Feed" class="ml-1 text-slate-400 hover:text-slate-700">
                    <i id="btn-toggle-icon" class="fa-solid fa-pause text-[11px]"></i>
                </button>
            </div>

            @if(!auth()->user()->isCustomer())
            <a href="{{ route('shipments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/20 transition">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>New Shipment</span>
            </a>
            @endif
            <a href="{{ route('shipments.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
                <i class="fa-solid fa-list text-xs"></i>
                <span>View Shipments</span>
            </a>
        </div>
    </div>

    <!-- 4 Summary KPI Metric Cards (Database-driven, Live-Updated) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Shipments -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tracked</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div id="stat-total" class="text-3xl font-extrabold text-slate-900 mt-2 transition-colors">{{ number_format($totalShipments) }}</div>
            <div class="text-xs text-slate-500 mt-1">Across global freight routes</div>
        </div>

        <!-- In Transit -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active In-Transit</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-ship"></i>
                </div>
            </div>
            <div id="stat-intransit" class="text-3xl font-extrabold text-indigo-600 mt-2 transition-colors">{{ number_format($inTransitCount) }}</div>
            <div class="text-xs text-slate-500 mt-1">Underway via sea/air corridors</div>
        </div>

        <!-- Delivered -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Completed Delivered</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div id="stat-delivered" class="text-3xl font-extrabold text-emerald-600 mt-2 transition-colors">{{ number_format($deliveredCount) }}</div>
            <div class="text-xs text-slate-500 mt-1">Successfully fulfilled to destination</div>
        </div>

        <!-- Delayed & Exceptions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Delayed / Exceptions</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span id="stat-delayed" class="text-3xl font-extrabold text-rose-600 transition-colors">{{ number_format($delayedCount) }}</span>
                <span id="stat-exceptions-pill" class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $openExceptionsCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                    <span id="stat-exceptions">{{ $openExceptionsCount }}</span> Open Exc.
                </span>
            </div>
            <div class="text-xs text-slate-500 mt-1">Delay Rate: <span id="stat-delay-rate" class="font-bold text-slate-700">{{ $delayRate }}%</span> (Threshold: {{ config('cargotrace.delay_threshold_hours', 12) }}h)</div>
        </div>
    </div>

    <!-- Charts Section (Chart.js) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Status Lifecycle Breakdown -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Shipment Lifecycle</h3>
                    <p class="text-xs text-slate-400">Distribution across pipeline stages</p>
                </div>
                <i class="fa-solid fa-chart-pie text-slate-400"></i>
            </div>
            <div class="h-64 relative flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Carrier Partner Volume -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Carrier Partner Volume</h3>
                    <p class="text-xs text-slate-400">Active assignments by logistics provider</p>
                </div>
                <i class="fa-solid fa-chart-simple text-slate-400"></i>
            </div>
            <div class="h-64 relative">
                <canvas id="carrierChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Grid: Recent Shipments + Critical Exceptions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Recent Shipments Table (2 cols) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden lg:col-span-2">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Recent Shipments</h3>
                    <p class="text-xs text-slate-500">Live operational status updates</p>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                    View All <i class="fa-solid fa-chevron-right text-[10px] ml-0.5"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                    <thead class="bg-slate-50/75 text-slate-500 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-3 px-4">Shipment #</th>
                            <th class="py-3 px-4">Carrier</th>
                            <th class="py-3 px-4">Route</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">ETA / Delay</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="shipments-tbody" class="divide-y divide-slate-100">
                        @forelse($recentShipments as $s)
                        <tr class="hover:bg-slate-50/50 transition" data-shipment-id="{{ $s->id }}">
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                <a href="{{ route('shipments.show', $s) }}" class="text-blue-600 hover:underline">
                                    {{ $s->shipment_number }}
                                </a>
                                @if(!auth()->user()->isCustomer())
                                <div class="text-[11px] font-normal text-slate-400">{{ $s->customer?->name }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-700">
                                {{ $s->carrier?->name }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 font-medium text-slate-700">
                                    <span>{{ $s->origin?->code }}</span>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                    <span>{{ $s->destination?->code }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400">{{ $s->origin?->city }} to {{ $s->destination?->city }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $statusClasses = [
                                        'CREATED' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'BOOKED' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'PICKED_UP' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'IN_TRANSIT' => 'bg-blue-50 text-blue-700 border-blue-200 animate-pulse',
                                        'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ];
                                @endphp
                                <span class="status-badge inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClasses[$s->current_status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $s->current_status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($s->is_delayed)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $s->delay_severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : ($s->delay_severity === 'MEDIUM' ? 'bg-amber-100 text-amber-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        <i class="fa-solid fa-clock-rotate-left"></i> +{{ round($s->delay_duration_minutes / 60, 1) }}h ({{ $s->delay_severity }})
                                    </span>
                                @else
                                    <span class="text-emerald-600 font-semibold text-[11px] flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> On Schedule
                                    </span>
                                @endif
                                <div class="text-[10px] text-slate-400">ETA: {{ $s->estimated_arrival?->format('M d, H:i') ?? $s->expected_arrival->format('M d, H:i') }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('shipments.show', $s) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">No shipments found in database.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Open Exceptions Card (1 col) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Active Exceptions</h3>
                    <p class="text-xs text-slate-500">Requires operational attention</p>
                </div>
                <span id="exceptions-pill-badge" class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    <span id="exceptions-count-val">{{ $openExceptionsCount }}</span> Open
                </span>
            </div>

            <div id="exceptions-container" class="space-y-3">
                @forelse($recentExceptions as $exc)
                <div class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-300 transition">
                    <div class="flex items-start justify-between gap-2">
                        <div class="font-bold text-xs text-slate-800">
                            {{ str_replace('_', ' ', $exc->exception_type) }}
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $exc->severity === 'CRITICAL' ? 'bg-rose-500 text-white' : ($exc->severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                            {{ $exc->severity }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $exc->description }}</div>
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-400">
                        <a href="{{ route('shipments.show', $exc->shipment_id) }}" class="font-semibold text-blue-600 hover:underline">
                            #{{ $exc->shipment?->shipment_number }}
                        </a>
                        <span>{{ $exc->detected_at?->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <div class="py-12 text-center text-slate-400">
                    <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-2"></i>
                    <p class="text-xs">All routes clear. No open exceptions recorded.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Live Tracking Activity Stream (Real-Time Feed) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-600"></span>
                </span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Live Activity Stream</h3>
                    <p class="text-xs text-slate-500">Real-time GPS waypoints, waypoint check-ins &amp; transit scans</p>
                </div>
            </div>
            <span class="text-xs text-slate-400" id="live-feed-updated">Connecting…</span>
        </div>

        <div id="live-feed-list" class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
            <div class="py-6 text-center text-slate-400 text-xs">
                <i class="fa-solid fa-satellite-dish animate-pulse text-lg text-blue-500 mb-2"></i>
                <p>Loading real-time waypoint feed…</p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart instances
        let statusChartInstance = null;
        let carrierChartInstance = null;

        // Status Doughnut Chart
        const statusLabels = {!! json_encode(array_keys($statusCounts)) !!};
        const statusData = {!! json_encode(array_values($statusCounts)) !!};

        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        statusChartInstance = new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData.length ? statusData : [1],
                    backgroundColor: [
                        '#94a3b8', // CREATED
                        '#38bdf8', // BOOKED
                        '#6366f1', // PICKED_UP
                        '#2563eb', // IN_TRANSIT
                        '#10b981', // DELIVERED
                        '#f43f5e', // CANCELLED
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    }
                },
                cutout: '70%'
            }
        });

        // Carrier Bar Chart
        const carrierLabels = {!! json_encode(array_keys($carrierCounts)) !!};
        const carrierData = {!! json_encode(array_values($carrierCounts)) !!};

        const ctxCarrier = document.getElementById('carrierChart').getContext('2d');
        carrierChartInstance = new Chart(ctxCarrier, {
            type: 'bar',
            data: {
                labels: carrierLabels.length ? carrierLabels : ['No data'],
                datasets: [{
                    label: 'Active Shipments',
                    data: carrierData.length ? carrierData : [0],
                    backgroundColor: '#3b82f6',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // REAL-TIME POLLING ENGINE
        // ─────────────────────────────────────────────────────────────────
        let isPolling = true;
        let countdownSec = 5;

        const pollPingEl    = document.getElementById('poll-ping');
        const pollDotEl     = document.getElementById('poll-dot');
        const pollStatusEl  = document.getElementById('poll-status');
        const btnToggle     = document.getElementById('btn-toggle-poll');
        const btnToggleIcon = document.getElementById('btn-toggle-icon');

        btnToggle.addEventListener('click', () => {
            isPolling = !isPolling;
            if (isPolling) {
                btnToggleIcon.className = 'fa-solid fa-pause text-[11px]';
                pollDotEl.className = 'relative inline-flex rounded-full h-2 w-2 bg-emerald-500';
                pollPingEl.classList.remove('hidden');
                pollStatusEl.innerHTML = 'Auto-refresh <span id="poll-countdown" class="text-blue-600 font-bold">5s</span>';
                countdownSec = 5;
            } else {
                btnToggleIcon.className = 'fa-solid fa-play text-[11px]';
                pollDotEl.className = 'relative inline-flex rounded-full h-2 w-2 bg-slate-400';
                pollPingEl.classList.add('hidden');
                pollStatusEl.textContent = 'Paused';
            }
        });

        setInterval(() => {
            if (!isPolling) return;
            countdownSec--;
            const cd = document.getElementById('poll-countdown');
            if (cd) cd.textContent = countdownSec + 's';
            if (countdownSec <= 0) {
                countdownSec = 5;
                fetchRealtimeStats();
                fetchLiveFeed();
            }
        }, 1000);

        async function fetchRealtimeStats() {
            try {
                const res = await fetch('{{ route('realtime.dashboard-stats') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();

                updateNumberWithFlash('stat-total', data.total_shipments);
                updateNumberWithFlash('stat-intransit', data.in_transit_count);
                updateNumberWithFlash('stat-delivered', data.delivered_count);
                updateNumberWithFlash('stat-delayed', data.delayed_count);
                updateNumberWithFlash('stat-exceptions', data.open_exceptions_count);
                updateNumberWithFlash('stat-delay-rate', data.delay_rate + '%');

                if (statusChartInstance && data.status_counts) {
                    statusChartInstance.data.labels = Object.keys(data.status_counts);
                    statusChartInstance.data.datasets[0].data = Object.values(data.status_counts);
                    statusChartInstance.update();
                }

                if (carrierChartInstance && data.carrier_counts) {
                    carrierChartInstance.data.labels = Object.keys(data.carrier_counts);
                    carrierChartInstance.data.datasets[0].data = Object.values(data.carrier_counts);
                    carrierChartInstance.update();
                }

                if (data.recent_shipments && data.recent_shipments.length > 0) {
                    renderRecentShipments(data.recent_shipments);
                }

                if (data.recent_exceptions) {
                    renderExceptions(data.recent_exceptions, data.open_exceptions_count);
                }

            } catch (err) {
                console.error('Realtime fetch failed:', err);
            }
        }

        function updateNumberWithFlash(elementId, newValue) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const current = el.textContent.trim();
            const next = String(newValue);
            if (current !== next) {
                el.textContent = next;
                el.classList.add('scale-110', 'text-blue-500');
                setTimeout(() => el.classList.remove('scale-110', 'text-blue-500'), 500);
            }
        }

        function renderRecentShipments(shipments) {
            const tbody = document.getElementById('shipments-tbody');
            if (!tbody) return;

            const statusBadges = {
                'CREATED':    'bg-slate-100 text-slate-700 border-slate-200',
                'BOOKED':     'bg-sky-50 text-sky-700 border-sky-200',
                'PICKED_UP':  'bg-indigo-50 text-indigo-700 border-indigo-200',
                'IN_TRANSIT': 'bg-blue-50 text-blue-700 border-blue-200 animate-pulse',
                'DELIVERED':  'bg-emerald-50 text-emerald-700 border-emerald-200',
                'CANCELLED':  'bg-rose-50 text-rose-700 border-rose-200',
            };

            tbody.innerHTML = shipments.map(s => {
                const badgeClass = statusBadges[s.current_status] || 'bg-slate-100 text-slate-700 border-slate-200';
                const delayBadge = s.is_delayed
                    ? `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold ${s.delay_severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : (s.delay_severity === 'MEDIUM' ? 'bg-amber-100 text-amber-800' : 'bg-yellow-100 text-yellow-800')}">
                         <i class="fa-solid fa-clock-rotate-left"></i> +${s.delay_hours}h (${s.delay_severity})
                       </span>`
                    : `<span class="text-emerald-600 font-semibold text-[11px] flex items-center gap-1">
                         <i class="fa-solid fa-check text-[10px]"></i> On Schedule
                       </span>`;

                return `
                <tr class="hover:bg-slate-50/50 transition" data-shipment-id="${s.id}">
                    <td class="py-3.5 px-4 font-bold text-slate-800">
                        <a href="${s.show_url}" class="text-blue-600 hover:underline">
                            ${s.shipment_number}
                        </a>
                        ${s.customer_name ? `<div class="text-[11px] font-normal text-slate-400">${s.customer_name}</div>` : ''}
                    </td>
                    <td class="py-3.5 px-4 font-medium text-slate-700">${s.carrier_name || '-'}</td>
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-1.5 font-medium text-slate-700">
                            <span>${s.origin_code || '-'}</span>
                            <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                            <span>${s.destination_code || '-'}</span>
                        </div>
                        <div class="text-[10px] text-slate-400">${s.origin_city || ''} to ${s.destination_city || ''}</div>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${badgeClass}">
                            ${s.current_status}
                        </span>
                    </td>
                    <td class="py-3.5 px-4">
                        ${delayBadge}
                        <div class="text-[10px] text-slate-400">ETA: ${s.eta || 'TBD'}</div>
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <a href="${s.show_url}" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </td>
                </tr>`;
            }).join('');
        }

        function renderExceptions(exceptions, totalOpen) {
            const container = document.getElementById('exceptions-container');
            const pillVal = document.getElementById('exceptions-count-val');
            if (pillVal) pillVal.textContent = totalOpen;

            if (!container) return;

            if (exceptions.length === 0) {
                container.innerHTML = `
                    <div class="py-12 text-center text-slate-400">
                        <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-2"></i>
                        <p class="text-xs">All routes clear. No open exceptions recorded.</p>
                    </div>`;
                return;
            }

            container.innerHTML = exceptions.map(e => `
                <div class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-300 transition">
                    <div class="flex items-start justify-between gap-2">
                        <div class="font-bold text-xs text-slate-800">${e.exception_type}</div>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase ${e.severity === 'CRITICAL' ? 'bg-rose-500 text-white' : (e.severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')}">
                            ${e.severity}
                        </span>
                    </div>
                    <div class="text-xs text-slate-600 mt-1 line-clamp-2">${e.description}</div>
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-400">
                        <a href="${e.shipment_url}" class="font-semibold text-blue-600 hover:underline">
                            #${e.shipment_number || '-'}
                        </a>
                        <span>${e.detected_ago || 'just now'}</span>
                    </div>
                </div>
            `).join('');
        }

        async function fetchLiveFeed() {
            try {
                const res = await fetch('{{ route('realtime.live-feed') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();

                const updatedEl = document.getElementById('live-feed-updated');
                if (updatedEl) {
                    const d = new Date();
                    updatedEl.textContent = 'Updated ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }

                const listEl = document.getElementById('live-feed-list');
                if (!listEl) return;

                if (!data.events || data.events.length === 0) {
                    listEl.innerHTML = '<div class="py-6 text-center text-slate-400 text-xs">No recent events recorded.</div>';
                    return;
                }

                listEl.innerHTML = data.events.map(ev => `
                    <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100 hover:bg-white hover:border-slate-200 transition">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <a href="${ev.shipment_url}" class="font-bold text-xs text-blue-600 hover:underline truncate">
                                    #${ev.shipment_number || '-'}
                                </a>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap">${ev.event_time_human}</span>
                            </div>
                            <div class="text-xs font-semibold text-slate-800 truncate">${ev.location}</div>
                            <div class="text-[11px] text-slate-500 line-clamp-1">${ev.description}</div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-200 text-slate-700 whitespace-nowrap">
                            ${ev.status}
                        </span>
                    </div>
                `).join('');

            } catch (err) {
                console.error('Live feed fetch failed:', err);
            }
        }

        fetchLiveFeed();
    });
</script>
@endpush
