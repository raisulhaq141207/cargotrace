@extends('layouts.app')

@section('title', 'Logistics Hubs & Ports')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Hubs, Ports &amp; Terminals</h1>
            <p class="text-sm text-slate-500">Global geocoded freight transfer nodes and waypoints</p>
        </div>

        @if(auth()->user()->isAdmin())
        <button type="button" onclick="document.getElementById('modalAddLocation').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/20 transition">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Register Location</span>
        </button>
        @endif
    </div>

    <!-- Locations Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50/75 text-slate-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3.5 px-4">Location Name</th>
                        <th class="py-3.5 px-4">Code &amp; Type</th>
                        <th class="py-3.5 px-4">City &amp; Country</th>
                        <th class="py-3.5 px-4">Coordinates (Lat / Lng)</th>
                        <th class="py-3.5 px-4">Timezone</th>
                        <th class="py-3.5 px-4 text-right">Shipments Routed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($locations as $loc)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-800">
                            {{ $loc->name }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-mono font-bold text-[11px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">{{ $loc->code }}</span>
                            <span class="ml-1 text-[10px] uppercase font-bold text-slate-500">{{ $loc->type }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-700">
                            {{ $loc->city }}, {{ $loc->country }}
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                            {{ number_format($loc->latitude, 4) }}, {{ number_format($loc->longitude, 4) }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $loc->timezone }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-medium text-slate-700">
                            {{ $loc->origin_shipments_count + $loc->destination_shipments_count }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No hub locations configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $locations->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Modal: Add Location (Admin Only) -->
@if(auth()->user()->isAdmin())
<div id="modalAddLocation" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Register Hub Location</h3>
            <button type="button" onclick="document.getElementById('modalAddLocation').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('locations.store') }}" class="space-y-4 mt-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Hub Name *</label>
                <input type="text" name="name" required placeholder="e.g. Port of Rotterdam"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Code *</label>
                    <input type="text" name="code" required placeholder="e.g. NLRTM"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Facility Type *</label>
                    <select name="type" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="PORT">PORT (Maritime)</option>
                        <option value="AIRPORT">AIRPORT (Air Cargo)</option>
                        <option value="WAREHOUSE">WAREHOUSE / CFS</option>
                        <option value="CHECKPOINT">CHECKPOINT / BORDER</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">City *</label>
                    <input type="text" name="city" required placeholder="Rotterdam"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Country *</label>
                    <input type="text" name="country" required placeholder="Netherlands"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Latitude *</label>
                    <input type="number" step="any" name="latitude" required placeholder="51.9244"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Longitude *</label>
                    <input type="number" step="any" name="longitude" required placeholder="4.4777"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Timezone</label>
                <input type="text" name="timezone" value="UTC" placeholder="Europe/Amsterdam"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddLocation').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    Save Location
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

