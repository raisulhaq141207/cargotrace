@extends('layouts.app')

@section('title', 'Shipments Directory')
@section('header_title', 'Shipments')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Shipments Directory</h1>
            <p class="text-sm text-slate-500">Manage and track your global multimodal freight shipments</p>
        </div>
        @if(!auth()->user()->isCustomer())
        <a href="{{ route('shipments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/20 transition">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Create New Shipment</span>
        </a>
        @endif
    </div>

    <!-- Filter & Search Toolbar (Slide 10) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('shipments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Reference</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="CT-2026-..., notes..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="">All Statuses</option>
                    @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Carrier Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Carrier</label>
                <select name="carrier_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $c)
                    <option value="{{ $c->id }}" {{ request('carrier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Delay Filter -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Schedule Delay</label>
                <select name="is_delayed" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="">All Timelines</option>
                    <option value="1" {{ request('is_delayed') === '1' ? 'selected' : '' }}>Delayed Only</option>
                    <option value="0" {{ request('is_delayed') === '0' ? 'selected' : '' }}>On Schedule Only</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('shipments.index') }}" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition" title="Reset filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Shipments Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50/75 text-slate-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3.5 px-4">Shipment #</th>
                        <th class="py-3.5 px-4">Carrier & Cargo</th>
                        <th class="py-3.5 px-4">Origin &rarr; Destination</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">ETA &amp; Delay Status</th>
                        @if(!auth()->user()->isCustomer())
                        <th class="py-3.5 px-4">Customer</th>
                        @endif
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shipments as $s)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-4 px-4 font-bold text-slate-800">
                            <a href="{{ route('shipments.show', $s) }}" class="text-blue-600 hover:underline flex items-center gap-1.5 font-bold">
                                <span>{{ $s->shipment_number }}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>
                            </a>
                            <div class="text-[10px] font-normal text-slate-400 mt-0.5">Created {{ $s->created_at->format('M d, Y') }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-semibold text-slate-800">{{ $s->carrier?->name }}</div>
                            <div class="text-[10px] text-slate-500">{{ $s->cargo_type }} • {{ number_format($s->weight, 0) }} kg</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-1.5 font-medium text-slate-800">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-bold">{{ $s->origin?->code }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-bold">{{ $s->destination?->code }}</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $s->origin?->city }} to {{ $s->destination?->city }}</div>
                        </td>
                        <td class="py-4 px-4">
                            @php
                                $statusClasses = [
                                    'CREATED' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'BOOKED' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'PICKED_UP' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'IN_TRANSIT' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusClasses[$s->current_status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $s->current_status }}
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            @if($s->is_delayed)
                                <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold {{ $s->delay_severity === 'HIGH' ? 'bg-rose-100 text-rose-800' : ($s->delay_severity === 'MEDIUM' ? 'bg-amber-100 text-amber-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Delayed +{{ round($s->delay_duration_minutes / 60, 1) }}h ({{ $s->delay_severity }})
                                </div>
                            @else
                                <div class="text-emerald-600 font-semibold text-[11px] flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i> On Schedule
                                </div>
                            @endif
                            <div class="text-[10px] text-slate-400 mt-0.5">ETA: {{ $s->estimated_arrival?->format('M d, Y H:i') ?? $s->expected_arrival->format('M d, Y H:i') }}</div>
                        </td>
                        @if(!auth()->user()->isCustomer())
                        <td class="py-4 px-4">
                            <div class="font-medium text-slate-800">{{ $s->customer?->name }}</div>
                            <div class="text-[10px] text-slate-400">{{ $s->customer?->company ?? $s->customer?->email }}</div>
                        </td>
                        @endif
                        <td class="py-4 px-4 text-right space-x-1">
                            <a href="{{ route('shipments.show', $s) }}" class="inline-flex items-center p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" title="View details and live map">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
                            @if(!auth()->user()->isCustomer())
                            <a href="{{ route('shipments.edit', $s) }}" class="inline-flex items-center p-2 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition" title="Edit shipment">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isCustomer() ? 6 : 7 }}" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-3xl text-slate-300 mb-2"></i>
                            <p class="text-xs">No matching shipments found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipments->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $shipments->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

