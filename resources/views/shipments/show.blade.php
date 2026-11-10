@extends('layouts.app')

@section('title', 'Shipment #' . $shipment->shipment_number)
@section('header_title', 'Tracking Details')

@section('content')
<div class="space-y-6">

    <!-- Top Shipment Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2">
                        <span>#{{ $shipment->shipment_number }}</span>
                    </h1>
                    
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
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusClasses[$shipment->current_status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $shipment->current_status }}
                    </span>

                    @if($shipment->is_delayed)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $shipment->delay_severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : ($shipment->delay_severity === 'MEDIUM' ? 'bg-amber-100 text-amber-800' : 'bg-yellow-100 text-yellow-800') }}">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        DELAYED (+{{ round($shipment->delay_duration_minutes / 60, 1) }}h - {{ $shipment->delay_severity }})
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i> On Schedule
                    </span>
                    @endif
                </div>
                
                <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                    <span><i class="fa-solid fa-user text-slate-400 mr-1"></i> Customer: <strong>{{ $shipment->customer?->name }}</strong> ({{ $shipment->customer?->company ?? 'Direct' }})</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-truck text-slate-400 mr-1"></i> Carrier: <strong>{{ $shipment->carrier?->name }}</strong></span>
                    <span>•</span>
                    <span><i class="fa-solid fa-box text-slate-400 mr-1"></i> {{ $shipment->cargo_type }} ({{ number_format($shipment->weight, 0) }} kg)</span>
                </p>
            </div>

            <!-- Actions buttons -->
            <div class="flex items-center gap-2">
                @if(!auth()->user()->isCustomer())
                <a href="{{ route('shipments.edit', $shipment) }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </a>
                @endif
                <a href="{{ route('shipments.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>

        <!-- Progress Lifecycle Pipeline Bar -->
        <div class="mt-6 pt-6 border-t border-slate-100">
            @php
                $stages = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
                $currentIndex = array_search($shipment->current_status, $stages);
                if ($currentIndex === false) $currentIndex = -1;
            @endphp
            <div class="grid grid-cols-5 gap-2 text-center text-xs">
                @foreach($stages as $idx => $st)
                @php
                    $isPassed = $currentIndex !== false && $idx <= $currentIndex;
                    $isCurrent = $currentIndex !== false && $idx === $currentIndex;
                @endphp
                <div class="relative">
                    <div class="h-2 rounded-full mb-2 {{ $isPassed ? 'bg-blue-600' : 'bg-slate-200' }} {{ $isCurrent ? 'ring-2 ring-blue-400 ring-offset-2' : '' }}"></div>
                    <div class="font-bold text-[11px] {{ $isPassed ? 'text-blue-600' : 'text-slate-400' }}">{{ $st }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Grid: Interactive Map + Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Leaflet Map & Shipment Manifest Detail -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Map Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-blue-600 text-base"></i>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Live Route &amp; Geo-Tracking</h2>
                    </div>
                    <span class="text-xs text-slate-400">Leaflet.js &amp; OpenStreetMap</span>
                </div>
                
                <!-- Map Container -->
                <div id="shipmentMap" class="w-full h-96 bg-slate-100"></div>

                <!-- Map Legend & Route Stats Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Origin</div>
                        <div class="font-bold text-slate-800">{{ $shipment->origin?->name }} ({{ $shipment->origin?->code }})</div>
                        <div class="text-[10px] text-slate-500">{{ $shipment->origin?->city }}, {{ $shipment->origin?->country }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Destination</div>
                        <div class="font-bold text-slate-800">{{ $shipment->destination?->name }} ({{ $shipment->destination?->code }})</div>
                        <div class="text-[10px] text-slate-500">{{ $shipment->destination?->city }}, {{ $shipment->destination?->country }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Expected Departure</div>
                        <div class="font-bold text-slate-800">{{ $shipment->expected_departure->format('M d, Y H:i') }}</div>
                        <div class="text-[10px] text-slate-500">Actual: {{ $shipment->actual_departure?->format('M d, H:i') ?? 'Pending' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Scheduled Arrival</div>
                        <div class="font-bold text-slate-800">{{ $shipment->expected_arrival->format('M d, Y H:i') }}</div>
                        <div class="text-[10px] text-slate-500">ETA: {{ $shipment->estimated_arrival?->format('M d, H:i') ?? $shipment->expected_arrival->format('M d, H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Manifest Cargo Items Breakdown -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-boxes-packing text-slate-400"></i>
                        <span>Cargo Manifest Breakdown</span>
                    </h2>
                    <span class="text-xs text-slate-500">{{ $shipment->items->count() }} items listed</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold">
                            <tr>
                                <th class="py-2.5 px-3">Description</th>
                                <th class="py-2.5 px-3">Quantity / Unit</th>
                                <th class="py-2.5 px-3">Weight (kg)</th>
                                <th class="py-2.5 px-3">HS Tariff Code</th>
                                <th class="py-2.5 px-3 text-right">Declared Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($shipment->items as $item)
                            <tr>
                                <td class="py-3 px-3 font-semibold text-slate-800">{{ $item->description }}</td>
                                <td class="py-3 px-3">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="py-3 px-3">{{ number_format($item->weight, 1) }}</td>
                                <td class="py-3 px-3 font-mono text-[11px] text-slate-600">{{ $item->hs_code ?? '-' }}</td>
                                <td class="py-3 px-3 text-right font-medium text-slate-800">
                                    {{ $item->declared_value ? '$' . number_format($item->declared_value, 2) : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Standard bulk consignment manifest.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Operational Exceptions Management Panel (Slide 08) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Operational Exceptions Log</h2>
                    </div>

                    @if(!auth()->user()->isCustomer())
                    <button type="button" onclick="document.getElementById('modalAddException').classList.remove('hidden')"
                        class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold border border-amber-200 transition">
                        <i class="fa-solid fa-plus mr-1"></i> Log Exception
                    </button>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse($shipment->exceptions as $exc)
                    <div class="p-4 rounded-xl border {{ $exc->status === 'RESOLVED' ? 'bg-slate-50 border-slate-200' : 'bg-amber-50/40 border-amber-200' }}">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $exc->status === 'RESOLVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-200 text-amber-900' }}">
                                    {{ $exc->status }}
                                </span>
                                <span class="font-bold text-sm text-slate-900">{{ str_replace('_', ' ', $exc->exception_type) }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $exc->severity === 'CRITICAL' ? 'bg-rose-500 text-white' : ($exc->severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $exc->severity }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Detected: {{ $exc->detected_at->format('M d, Y H:i') }}
                            </div>
                        </div>

                        <div class="text-xs text-slate-700 mt-2">{{ $exc->description }}</div>

                        @if($exc->status === 'RESOLVED')
                        <div class="mt-2.5 pt-2 border-t border-slate-200 text-xs text-emerald-800">
                            <strong>Resolution Note:</strong> {{ $exc->resolution_notes }}
                            <div class="text-[10px] text-slate-400 mt-0.5">Resolved by {{ $exc->resolver?->name ?? 'Staff' }} on {{ $exc->resolved_at?->format('M d, Y H:i') }}</div>
                        </div>
                        @elseif(!auth()->user()->isCustomer())
                        <!-- Resolve Exception Form -->
                        <form method="POST" action="{{ route('exceptions.resolve', $exc) }}" class="mt-3 pt-3 border-t border-amber-200/70 flex flex-col sm:flex-row items-center gap-2">
                            @csrf
                            <input type="text" name="resolution_notes" placeholder="Enter resolution notes..." required
                                class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <button type="submit" class="w-full sm:w-auto px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition">
                                <i class="fa-solid fa-check mr-1"></i> Mark Resolved
                            </button>
                        </form>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-6 text-xs text-slate-400">
                        No operational exceptions recorded on this shipment.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Actions + Tracking Timeline -->
        <div class="space-y-6">
            
            <!-- Quick Operator Status Transition Card -->
            @if(!auth()->user()->isCustomer() && !$shipment->isTerminal())
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">Lifecycle Management</h3>
                <p class="text-xs text-slate-500 mb-4">Advance status according to business transition rules.</p>

                @php
                    $validNext = \App\Models\Shipment::VALID_TRANSITIONS[$shipment->current_status] ?? [];
                @endphp

                @if(count($validNext) > 0)
                <div class="space-y-2">
                    @foreach($validNext as $nextStatus)
                    <form method="POST" action="{{ route('shipments.transition', $shipment) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                        <button type="submit" onclick="return confirm('Confirm advancing status to {{ $nextStatus }}?')"
                            class="w-full py-2 px-3 rounded-xl text-xs font-semibold text-white {{ $nextStatus === 'CANCELLED' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-blue-600 hover:bg-blue-700' }} shadow-sm transition flex items-center justify-between">
                            <span>Advance to <strong>{{ $nextStatus }}</strong></span>
                            <i class="fa-solid fa-arrow-right text-[11px]"></i>
                        </button>
                    </form>
                    @endforeach
                </div>
                @else
                <div class="text-xs text-slate-400 bg-slate-50 p-3 rounded-xl text-center">
                    No further forward transitions available for this status.
                </div>
                @endif

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalAddTracking').classList.remove('hidden')"
                        class="w-full py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-location-crosshairs"></i>
                        <span>Log Tracking Checkpoint</span>
                    </button>
                </div>
            </div>
            @endif

            <!-- Chronological Tracking Timeline (Slide 07) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                        <span>Journey Timeline</span>
                    </h3>
                    <span class="text-xs font-semibold text-slate-500">{{ $shipment->trackingEvents->count() }} Checkpoints</span>
                </div>

                <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
                    @forelse($shipment->trackingEvents as $ev)
                    <div class="relative">
                        <!-- Bullet Icon -->
                        <div class="absolute -left-6 top-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] {{ $ev->is_exception ? 'bg-amber-500 text-white ring-4 ring-amber-100' : 'bg-blue-600 text-white ring-4 ring-blue-100' }}">
                            <i class="fa-solid {{ $ev->is_exception ? 'fa-triangle-exclamation' : 'fa-location-dot' }}"></i>
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="font-bold text-xs text-slate-900">{{ $ev->location_name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $ev->event_time->format('M d, H:i') }}</span>
                            </div>

                            <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold uppercase {{ $ev->is_exception ? 'bg-amber-100 text-amber-800' : 'bg-blue-50 text-blue-700' }}">
                                {{ $ev->status }}
                            </span>

                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $ev->description }}</p>

                            <!-- Weather integration snapshot badge (Slide 09) -->
                            @if($ev->weather_snapshot)
                            <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-800 border border-sky-100 text-[10px]">
                                <i class="fa-solid fa-cloud-sun text-sky-500"></i>
                                <span><strong>{{ $ev->weather_snapshot['temperature'] ?? '-' }}°C</strong>, {{ $ev->weather_snapshot['condition'] ?? 'Maritime' }}</span>
                                <span class="text-slate-400">({{ $ev->weather_snapshot['source'] ?? 'Weather API' }})</span>
                            </div>
                            @endif

                            <div class="text-[9px] text-slate-400 mt-1">Logged by {{ $ev->creator?->name ?? 'System' }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-xs text-slate-400 py-4">No tracking checkpoints recorded yet.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Modal: Log Tracking Event (Operator / Admin) -->
@if(!auth()->user()->isCustomer())
<div id="modalAddTracking" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Record Tracking Checkpoint</h3>
            <button type="button" onclick="document.getElementById('modalAddTracking').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('shipments.tracking.store', $shipment) }}" class="space-y-4 mt-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Preset Hub / Location</label>
                <select name="location_id" id="eventLocationSelect" onchange="autoFillCoords(this)" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                    <option value="">-- Custom Waypoint --</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" data-name="{{ $loc->name }} ({{ $loc->code }})" data-lat="{{ $loc->latitude }}" data-lng="{{ $loc->longitude }}">
                        {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}, {{ $loc->country }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Checkpoint Name *</label>
                <input type="text" name="location_name" id="eventLocationName" required placeholder="e.g. Strait of Malacca Waypoint Alpha"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Status / Milestone *</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="IN_TRANSIT">IN_TRANSIT</option>
                        <option value="PICKED_UP">PICKED_UP</option>
                        <option value="ARRIVED_PORT">ARRIVED_PORT</option>
                        <option value="CUSTOMS_CLEARANCE">CUSTOMS_CLEARANCE</option>
                        <option value="OUT_FOR_DELIVERY">OUT_FOR_DELIVERY</option>
                        <option value="DELIVERED">DELIVERED</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Event Timestamp *</label>
                    <input type="datetime-local" name="event_time" value="{{ now()->format('Y-m-d\TH:i') }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Latitude</label>
                    <input type="number" step="any" name="latitude" id="eventLat" placeholder="1.2902"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Longitude</label>
                    <input type="number" step="any" name="longitude" id="eventLng" placeholder="103.8519"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Updated ETA (Opt.)</label>
                <input type="datetime-local" name="estimated_arrival" value="{{ $shipment->estimated_arrival?->format('Y-m-d\TH:i') }}"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                <span class="text-[10px] text-slate-400">If route duration extended, delay rule will trigger automatically.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Description / Log Note *</label>
                <textarea name="description" rows="3" required placeholder="Vessel cleared southern passage, continuing towards hub under steady maritime speed..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddTracking').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    Record Checkpoint
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Log Operational Exception (Operator / Admin) -->
<div id="modalAddException" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                <span>Log Operational Exception</span>
            </h3>
            <button type="button" onclick="document.getElementById('modalAddException').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('shipments.exceptions.store', $shipment) }}" class="space-y-4 mt-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Exception Category *</label>
                <select name="exception_type" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                    <option value="CUSTOMS_HOLD">CUSTOMS_HOLD (Customs Inspection Hold)</option>
                    <option value="WEATHER_DELAY">WEATHER_DELAY (Typhoon / Severe Storm)</option>
                    <option value="PORT_CONGESTION">PORT_CONGESTION (Berth / Terminal Queue)</option>
                    <option value="DAMAGED">DAMAGED (Cargo Inspection Required)</option>
                    <option value="MISROUTED">MISROUTED (Carrier Rerouting)</option>
                    <option value="DOCUMENTATION_ISSUE">DOCUMENTATION_ISSUE (Manifest / HS Discrepancy)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Severity *</label>
                    <select name="severity" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="LOW">LOW</option>
                        <option value="MEDIUM" selected>MEDIUM</option>
                        <option value="HIGH">HIGH</option>
                        <option value="CRITICAL">CRITICAL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Detected At *</label>
                    <input type="datetime-local" name="detected_at" value="{{ now()->format('Y-m-d\TH:i') }}" required
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Estimated Delay Impact (Hours)</label>
                <input type="number" name="delay_hours_impact" min="0" value="12" placeholder="e.g. 12 or 24"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                <span class="text-[10px] text-slate-400">Automatically recalculates shipment ETA and delay severity.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Incident Description *</label>
                <textarea name="description" rows="3" required placeholder="Provide concise details on the anomaly..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddException').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white shadow-sm">
                    Register Exception
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    function autoFillCoords(selectEl) {
        const option = selectEl.options[selectEl.selectedIndex];
        if (option && option.value) {
            document.getElementById('eventLocationName').value = option.getAttribute('data-name') || '';
            document.getElementById('eventLat').value = option.getAttribute('data-lat') || '';
            document.getElementById('eventLng').value = option.getAttribute('data-lng') || '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Prepare Leaflet Map data
        const origin = {
            name: {!! json_encode($shipment->origin?->name) !!},
            lat: {{ $shipment->origin?->latitude ?? 0 }},
            lng: {{ $shipment->origin?->longitude ?? 0 }},
        };

        const destination = {
            name: {!! json_encode($shipment->destination?->name) !!},
            lat: {{ $shipment->destination?->latitude ?? 0 }},
            lng: {{ $shipment->destination?->longitude ?? 0 }},
        };

        const checkpoints = [
            @foreach($shipment->trackingEvents as $ev)
                @if($ev->latitude && $ev->longitude)
                {
                    name: {!! json_encode($ev->location_name) !!},
                    status: {!! json_encode($ev->status) !!},
                    time: {!! json_encode($ev->event_time->format('M d, H:i')) !!},
                    desc: {!! json_encode($ev->description) !!},
                    lat: {{ $ev->latitude }},
                    lng: {{ $ev->longitude }},
                    is_exception: {{ $ev->is_exception ? 'true' : 'false' }},
                    weather: {!! json_encode($ev->weather_snapshot) !!}
                },
                @endif
            @endforeach
        ];

        // Initialize Map
        const map = L.map('shipmentMap').setView([origin.lat, origin.lng], 4);

        // OpenStreetMap Tile Layer with mandatory attribution (Slide 09)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Markers & Route coordinates array
        const routeCoords = [];

        // 1. Origin Marker (Green)
        if (origin.lat && origin.lng) {
            routeCoords.push([origin.lat, origin.lng]);
            const originMarker = L.circleMarker([origin.lat, origin.lng], {
                radius: 8,
                fillColor: '#10b981',
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(map);
            originMarker.bindPopup(`<b>Origin Hub:</b> ${origin.name}<br><small>Scheduled Departure</small>`);
        }

        // 2. Waypoint / Tracking Event Markers
        checkpoints.forEach((cp, idx) => {
            routeCoords.push([cp.lat, cp.lng]);
            const color = cp.is_exception ? '#f59e0b' : '#2563eb';
            const marker = L.circleMarker([cp.lat, cp.lng], {
                radius: cp.is_exception ? 7 : 6,
                fillColor: color,
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.85
            }).addTo(map);

            let weatherHtml = '';
            if (cp.weather && cp.weather.temperature) {
                weatherHtml = `<div style="margin-top:4px; font-size:11px; color:#0284c7;"><b>Weather:</b> ${cp.weather.temperature}°C, ${cp.weather.condition}</div>`;
            }

            marker.bindPopup(`
                <b>${cp.name}</b><br>
                <small>${cp.status} • ${cp.time}</small>
                <div style="margin-top:3px; font-size:11px; color:#475569;">${cp.desc}</div>
                ${weatherHtml}
            `);
        });

        // 3. Destination Marker (Purple/Indigo)
        if (destination.lat && destination.lng) {
            routeCoords.push([destination.lat, destination.lng]);
            const destMarker = L.circleMarker([destination.lat, destination.lng], {
                radius: 8,
                fillColor: '#7c3aed',
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(map);
            destMarker.bindPopup(`<b>Destination Port:</b> ${destination.name}<br><small>Arrival Target</small>`);
        }

        // 4. Polyline Route
        if (routeCoords.length > 1) {
            const polyline = L.polyline(routeCoords, {
                color: '#3b82f6',
                weight: 3,
                opacity: 0.75,
                dashArray: '6, 8',
                lineJoin: 'round'
            }).addTo(map);

            map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
        }

        // ─────────────────────────────────────────────────────────────────
        // REAL-TIME AUTO-POLLING FOR SHIPMENT STATUS & EVENTS
        // ─────────────────────────────────────────────────────────────────
        const shipmentId = {{ $shipment->id }};
        let lastEventCount = {{ $shipment->trackingEvents->count() }};

        async function pollShipmentUpdates() {
            try {
                const res = await fetch(`/realtime/shipment/${shipmentId}/tracking`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();

                // If new tracking events have been logged, reload page to refresh map & timeline
                if (data.tracking_events && data.tracking_events.length > lastEventCount) {
                    console.log('New tracking checkpoint detected, reloading view...');
                    window.location.reload();
                }
            } catch (err) {
                // Ignore transient network errors
            }
        }

        // Poll every 8 seconds on the shipment details view
        setInterval(pollShipmentUpdates, 8000);
    });
</script>
@endpush

