@extends('layouts.app')

@section('title', 'Create Shipment')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Shipment</h1>
            <p class="text-sm text-slate-500">Register freight booking, carrier, route milestones and cargo manifest.</p>
        </div>
        <a href="{{ route('shipments.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('shipments.store') }}" class="space-y-6">
        @csrf

        <!-- General Info Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-blue-600"></i>
                <span>Shipment Reference &amp; Assignment</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Shipment Number</label>
                    <input type="text" name="shipment_number" value="{{ old('shipment_number', $suggestedNumber) }}" required
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 font-mono font-bold focus:ring-2 focus:ring-blue-500/20">
                    @error('shipment_number') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Customer Client *</label>
                    <select name="customer_id" required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->company ?? $c->email }})
                        </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Carrier Partner *</label>
                    <select name="carrier_id" required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="">-- Select Carrier --</option>
                        @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                            {{ $carrier->name }} ({{ $carrier->code }})
                        </option>
                        @endforeach
                    </select>
                    @error('carrier_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Origin Port / Terminal *</label>
                    <select name="origin_id" required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="">-- Select Origin Hub --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('origin_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}, {{ $loc->country }}
                        </option>
                        @endforeach
                    </select>
                    @error('origin_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Destination Port / Terminal *</label>
                    <select name="destination_id" required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="">-- Select Destination Hub --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('destination_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} ({{ $loc->code }}) - {{ $loc->city }}, {{ $loc->country }}
                        </option>
                        @endforeach
                    </select>
                    @error('destination_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Schedule & Specifications -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-blue-600"></i>
                <span>Schedule &amp; Cargo Specifications</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Cargo Type</label>
                    <select name="cargo_type" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                        @foreach(config('cargotrace.cargo_types', []) as $code => $label)
                        <option value="{{ $code }}" {{ old('cargo_type') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Total Weight (kg) *</label>
                    <input type="number" step="0.01" name="weight" value="{{ old('weight', '12500') }}" required
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                    @error('weight') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Volume (CBM)</label>
                    <input type="number" step="0.01" name="volume" value="{{ old('volume', '32.5') }}"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Initial Status</label>
                    <select name="current_status" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 font-semibold text-slate-700">
                        <option value="CREATED">CREATED</option>
                        <option value="BOOKED">BOOKED</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Expected Departure (ETD) *</label>
                    <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', now()->addDays(1)->format('Y-m-d\TH:i')) }}" required
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                    @error('expected_departure') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Expected Arrival (ETA) *</label>
                    <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', now()->addDays(8)->format('Y-m-d\TH:i')) }}" required
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">
                    @error('expected_arrival') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Operational Handling Notes</label>
                <textarea name="notes" rows="2" placeholder="Temperature requirement, customs notes, special handling..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300">{{ old('notes') }}</textarea>
            </div>
        </div>

        <!-- Manifest Items Repeater -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-boxes-packing text-blue-600"></i>
                    <span>Cargo Manifest Line Items</span>
                </h2>
                <button type="button" onclick="addItemRow()" class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Item
                </button>
            </div>

            <div id="itemsContainer" class="space-y-3">
                <div class="item-row grid grid-cols-1 sm:grid-cols-5 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Description *</label>
                        <input type="text" name="items[0][description]" value="Precision Machinery Parts" required
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Qty &amp; Unit</label>
                        <div class="flex gap-1">
                            <input type="number" name="items[0][quantity]" value="24" min="1"
                                class="w-16 px-2 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                            <select name="items[0][unit]" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                                <option value="PALLETS">PALLETS</option>
                                <option value="CARTONS">CARTONS</option>
                                <option value="CONTAINERS">CONTAINERS</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Weight (kg)</label>
                        <input type="number" step="0.1" name="items[0][weight]" value="12500"
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Value ($ USD)</label>
                        <input type="number" step="0.01" name="items[0][declared_value]" value="145000"
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('shipments.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition">
                Create &amp; Register Shipment
            </button>
        </div>
    </form>
</div>

<script>
    let itemIndex = 1;
    function addItemRow() {
        const container = document.getElementById('itemsContainer');
        const html = `
            <div class="item-row grid grid-cols-1 sm:grid-cols-5 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Description *</label>
                    <input type="text" name="items[${itemIndex}][description]" required placeholder="Item description"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Qty &amp; Unit</label>
                    <div class="flex gap-1">
                        <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1"
                            class="w-16 px-2 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                        <select name="items[${itemIndex}][unit]" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="CARTONS">CARTONS</option>
                            <option value="PALLETS">PALLETS</option>
                            <option value="CONTAINERS">CONTAINERS</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Weight (kg)</label>
                    <input type="number" step="0.1" name="items[${itemIndex}][weight]" value="100"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                </div>
                <div class="relative">
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Value ($ USD)</label>
                    <div class="flex items-center gap-1">
                        <input type="number" step="0.01" name="items[${itemIndex}][declared_value]" value="5000"
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                        <button type="button" onclick="this.closest('.item-row').remove()" class="p-1.5 text-slate-400 hover:text-rose-500">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        itemIndex++;
    }
</script>
@endsection
