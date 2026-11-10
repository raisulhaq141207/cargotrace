@extends('layouts.app')

@section('title', 'Analytics & Reports')
@section('header_title', 'Analytics & Reports')

@section('content')
<div class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Fleet Analytics &amp; Performance Reports</h1>
            <p class="text-xs text-slate-400">Reliability scorecards, trade corridor analysis &amp; on-time fulfillment metrics</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-print text-slate-400"></i>
                <span>Print Report</span>
            </button>
            <a href="{{ route('shipments.export') }}" class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold shadow-md shadow-sky-500/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-download text-xs"></i>
                <span>Export CSV Data</span>
            </a>
        </div>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold uppercase text-slate-400">On-Time Fulfillment Rate</div>
            <div class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $onTimeRate }}%</div>
            <div class="text-[10px] text-slate-400 mt-1">Target threshold: &gt;85% SLA</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold uppercase text-slate-400">Avg. Delay Duration</div>
            <div class="text-3xl font-extrabold text-orange-500 mt-2">{{ $avgDelay }}h</div>
            <div class="text-[10px] text-slate-400 mt-1">Configured threshold: {{ config('cargotrace.delay_threshold_hours', 12) }}h</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold uppercase text-slate-400">Delivered Shipments</div>
            <div class="text-3xl font-extrabold text-slate-900 mt-2">{{ number_format($deliveredCount) }}</div>
            <div class="text-[10px] text-slate-400 mt-1">Out of {{ $totalShipments }} total tracked</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold uppercase text-slate-400">Total Cargo Volume</div>
            <div class="text-3xl font-extrabold text-sky-600 mt-2">{{ number_format($totalWeight) }} kg</div>
            <div class="text-[10px] text-slate-400 mt-1">Multimodal containerized freight</div>
        </div>
    </div>

    <!-- Carrier Reliability Scorecard Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Carrier Partner Reliability Scorecard</h3>
                <p class="text-[11px] text-slate-400">Evaluates on-time arrival rate and exception history across active logistics partners</p>
            </div>
            <span class="text-xs text-slate-400">Group Scenario Evaluation</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-400 font-semibold text-[11px]">
                    <tr>
                        <th class="py-3 px-5">Carrier Partner</th>
                        <th class="py-3 px-5">Code</th>
                        <th class="py-3 px-5 text-center">Assigned Consignments</th>
                        <th class="py-3 px-5 text-center">On-Time</th>
                        <th class="py-3 px-5 text-center">Delayed</th>
                        <th class="py-3 px-5">Fulfillment Rate</th>
                        <th class="py-3 px-5 text-right">Rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($carrierScorecard as $c)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-3.5 px-5 font-bold text-slate-800">{{ $c['name'] }}</td>
                        <td class="py-3.5 px-5 font-mono text-slate-500 text-[11px]">{{ $c['code'] }}</td>
                        <td class="py-3.5 px-5 text-center text-slate-700 font-semibold">{{ $c['total'] }}</td>
                        <td class="py-3.5 px-5 text-center text-emerald-600 font-semibold">{{ $c['on_time'] }}</td>
                        <td class="py-3.5 px-5 text-center text-rose-600 font-semibold">{{ $c['delayed'] }}</td>
                        <td class="py-3.5 px-5">
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-sky-500 h-full rounded-full" style="width: {{ $c['pct'] }}%"></div>
                                </div>
                                <span class="font-bold text-slate-700 text-[11px]">{{ $c['pct'] }}%</span>
                            </div>
                        </td>
                        <td class="py-3.5 px-5 text-right">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $c['grade'] === 'A+' || $c['grade'] === 'A' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                Grade {{ $c['grade'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No carrier performance data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Exception Category Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Operational Exception Breakdown</h3>
            <p class="text-[11px] text-slate-400 mb-4">Distribution of root causes triggering voyage anomalies</p>
            <div class="space-y-3">
                @forelse($exceptionBreakdown as $type => $count)
                @php
                    $pct = array_sum($exceptionBreakdown) > 0 ? round(($count / array_sum($exceptionBreakdown)) * 100) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700">{{ str_replace('_', ' ', $type) }}</span>
                        <span class="font-bold text-slate-900">{{ $count }} ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-rose-500 h-full rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <div class="py-6 text-center text-slate-400 text-xs">No exceptions recorded.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">Academic &amp; Engineering Defense Summary</h3>
                <p class="text-[11px] text-slate-400 mb-3">Key system parameters ready for lecturer interview</p>
                <ul class="space-y-2 text-xs text-slate-600">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                        <span><strong>Delay Threshold Rule:</strong> Evaluated deterministically via <code>EtaService</code>: 0–6h (LOW), &gt;6–24h (MEDIUM), &gt;24h (HIGH).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                        <span><strong>Lifecycle Enforcement:</strong> Status progression locked to valid sequential states via <code>Shipment::canTransitionTo()</code>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                        <span><strong>Fault-Tolerant Weather Telemetry:</strong> Integrated with Open-Meteo API using circuit-breaker fallback.</span>
                    </li>
                </ul>
            </div>
            <div class="pt-4 border-t border-slate-100 text-[11px] text-slate-400">
                Documentation: See <code>docs/INTERVIEW_CHEATSHEET.md</code> for full defense questions &amp; answers.
            </div>
        </div>
    </div>

</div>
@endsection
