@extends('layouts.app')

@section('title', 'Operations Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Top Greeting & Header Bar -->
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

    <!-- 4 Summary KPI Metric Cards (Database-driven, Slide 10) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Shipments -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tracked</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">{{ number_format($totalShipments) }}</div>
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
            <div class="text-3xl font-extrabold text-indigo-600 mt-2">{{ number_format($inTransitCount) }}</div>
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
            <div class="text-3xl font-extrabold text-emerald-600 mt-2">{{ number_format($deliveredCount) }}</div>
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
                <span class="text-3xl font-extrabold text-rose-600">{{ number_format($delayedCount) }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $openExceptionsCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $openExceptionsCount }} Open Exc.
                </span>
            </div>
            <div class="text-xs text-slate-500 mt-1">Delay Rate: <span class="font-bold text-slate-700">{{ $delayRate }}%</span> (Threshold: {{ config('cargotrace.delay_threshold_hours', 12) }}h)</div>
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
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentShipments as $s)
                        <tr class="hover:bg-slate-50/50 transition">
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClasses[$s->current_status] ?? 'bg-slate-100 text-slate-700' }}">
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
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    {{ $openExceptionsCount }} Open
                </span>
            </div>

            <div class="space-y-3">
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

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Status Doughnut Chart
        const statusLabels = {!! json_encode(array_keys($statusCounts)) !!};
        const statusData = {!! json_encode(array_values($statusCounts)) !!};

        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
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
        new Chart(ctxCarrier, {
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
    });
</script>
@endpush
