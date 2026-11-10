@extends('layouts.app')

@section('title', 'Edit Shipment #' . $shipment->shipment_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Shipment #{{ $shipment->shipment_number }}</h1>
            <p class="text-sm text-slate-500">Update carrier assignment, schedule timestamps, or cargo parameters.</p>
        </div>
        <a href="{{ route('shipments.show', $shipment) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to Detail
        </a>
    </div>

    <form method="POST" action="{{ route('shipments.update', $shipment) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                Route &amp; Assignment
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Carrier</label>
                    <select name="carrier_id" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        @foreach($carriers as $c)
                        <option value="{{ $c->id }}" {{ old('carrier_id', $shipment->carrier_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Origin Hub</label>
                    <select name="origin_id" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('origin_id', $shipment->origin_id) == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} ({{ $loc->code }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Destination Hub</label>
                    <select name="destination_id" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('destination_id', $shipment->destination_id) == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} ({{ $loc->code }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Cargo Type</label>
                    <select name="cargo_type" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        @foreach(config('cargotrace.cargo_types', []) as $code => $label)
                        <option value="{{ $code }}" {{ old('cargo_type', $shipment->cargo_type) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Weight (kg)</label>
                    <input type="number" step="0.01" name="weight" value="{{ old('weight', $shipment->weight) }}" required
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Volume (CBM)</label>
                    <input type="number" step="0.01" name="volume" value="{{ old('volume', $shipment->volume) }}"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                Schedule &amp; Delay Recalibration
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Expected Departure</label>
                    <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', $shipment->expected_departure->format('Y-m-d\TH:i')) }}"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Actual Departure</label>
                    <input type="datetime-local" name="actual_departure" value="{{ old('actual_departure', $shipment->actual_departure?->format('Y-m-d\TH:i')) }}"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Expected Arrival</label>
                    <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', $shipment->expected_arrival->format('Y-m-d\TH:i')) }}"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Estimated Arrival (ETA)</label>
                    <input type="datetime-local" name="estimated_arrival" value="{{ old('estimated_arrival', $shipment->estimated_arrival?->format('Y-m-d\TH:i')) }}"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Handling Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">{{ old('notes', $shipment->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <!-- Delete Shipment Button -->
                @if(auth()->user()->isAdmin() || (auth()->user()->isOperator() && in_array($shipment->current_status, ['CREATED', 'BOOKED'])))
                <button type="button" onclick="if(confirm('Are you sure you want to delete this shipment?')) document.getElementById('deleteShipmentForm').submit()"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Delete Shipment
                </button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('shipments.show', $shipment) }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    <form id="deleteShipmentForm" method="POST" action="{{ route('shipments.destroy', $shipment) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

