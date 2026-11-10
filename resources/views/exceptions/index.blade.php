@extends('layouts.app')

@section('title', 'Operational Exceptions')
@section('header_title', 'Exceptions')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Summary -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Operational Exceptions &amp; Incident Log</h1>
            <p class="text-xs text-slate-400">Monitor active port congestion, customs inspections, weather disruptions &amp; delay incidents</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">
                {{ $openCount }} Active Incidents
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-semibold">
                {{ $criticalCount }} Critical
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">
                {{ $resolvedCount }} Resolved
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('exceptions.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Severity</label>
                <select name="severity" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none">
                    <option value="">All Severities</option>
                    @foreach(['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'] as $sev)
                    <option value="{{ $sev }}" {{ request('severity') === $sev ? 'selected' : '' }}>{{ $sev }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>Open</option>
                    <option value="INVESTIGATING" {{ request('status') === 'INVESTIGATING' ? 'selected' : '' }}>Investigating</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Incident Type</label>
                <select name="type" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none">
                    <option value="">All Incident Types</option>
                    @foreach(['CUSTOMS_HOLD', 'WEATHER_DELAY', 'PORT_CONGESTION', 'VESSEL_BREAKDOWN', 'DOCS_MISSING'] as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ str_replace('_', ' ', $t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <a href="{{ route('exceptions.index') }}" class="w-full py-2 text-center text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200 transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Exceptions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($exceptions as $exc)
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:border-slate-300 transition">
            <div>
                <div class="flex items-start justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-900">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                        {{ str_replace('_', ' ', $exc->exception_type) }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $exc->severity === 'CRITICAL' ? 'bg-rose-500 text-white' : ($exc->severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                        {{ $exc->severity }}
                    </span>
                </div>

                <p class="text-xs text-slate-600 mt-2.5 leading-relaxed">{{ $exc->description }}</p>

                <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-[11px] text-slate-500">
                    <div class="flex items-center justify-between">
                        <span>Shipment:</span>
                        <a href="{{ route('shipments.show', $exc->shipment_id) }}" class="font-bold text-sky-600 hover:underline">
                            #{{ $exc->shipment?->shipment_number }}
                        </a>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Carrier:</span>
                        <span class="font-medium text-slate-700">{{ $exc->shipment?->carrier?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Detected:</span>
                        <span>{{ $exc->detected_at?->format('M d, Y H:i') }}</span>
                    </div>
                    @if($exc->resolved_at)
                    <div class="flex items-center justify-between text-emerald-600 font-medium">
                        <span>Resolved:</span>
                        <span>{{ $exc->resolved_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Resolution Action (Operators / Admins) -->
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $exc->status === 'RESOLVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $exc->status }}
                </span>

                @if(!auth()->user()->isCustomer() && $exc->status !== 'RESOLVED')
                <button type="button" onclick="openResolveModal({{ $exc->id }}, '{{ $exc->shipment?->shipment_number }}')"
                    class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                    Resolve
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
            <i class="fa-solid fa-circle-check text-4xl text-emerald-400 mb-2"></i>
            <p class="text-sm font-semibold text-slate-700">No matching operational exceptions found.</p>
            <p class="text-xs text-slate-400 mt-1">All shipment routes are operating within normal parameters.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div>
        {{ $exceptions->links() }}
    </div>

</div>

<!-- Resolve Modal -->
<div id="resolveModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Resolve Exception (<span id="modalShipmentNum"></span>)</h3>
            <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="resolveForm" method="POST" action="" class="space-y-4 mt-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Resolution Summary *</label>
                <textarea name="resolution_notes" required rows="3" placeholder="e.g. Customs clearance stamped, container released for outbound departure."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md transition">Confirm Resolved</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResolveModal(excId, shipmentNum) {
        document.getElementById('modalShipmentNum').textContent = '#' + shipmentNum;
        document.getElementById('resolveForm').action = `/exceptions/${excId}/resolve`;
        document.getElementById('resolveModal').classList.remove('hidden');
    }
</script>
@endsection
