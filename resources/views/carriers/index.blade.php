@extends('layouts.app')

@section('title', 'Logistics Carriers')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Carrier Partners</h1>
            <p class="text-sm text-slate-500">Global maritime, aviation, and intermodal freight operators</p>
        </div>

        @if(auth()->user()->isAdmin())
        <button type="button" onclick="document.getElementById('modalAddCarrier').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/20 transition">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Register Carrier</span>
        </button>
        @endif
    </div>

    <!-- Carriers Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($carriers as $c)
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-slate-300 transition space-y-3">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-bold text-base text-slate-900">{{ $c->name }}</h3>
                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded bg-slate-100 font-mono text-[10px] font-bold text-slate-700">
                        {{ $c->code }}
                    </span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                    {{ $c->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="space-y-1 text-xs text-slate-500 pt-1">
                @if($c->contact_email)
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-envelope text-[11px] text-slate-400 w-4"></i>
                    <span>{{ $c->contact_email }}</span>
                </div>
                @endif
                @if($c->contact_phone)
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-phone text-[11px] text-slate-400 w-4"></i>
                    <span>{{ $c->contact_phone }}</span>
                </div>
                @endif
                @if($c->website)
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-globe text-[11px] text-slate-400 w-4"></i>
                    <a href="{{ $c->website }}" target="_blank" class="text-blue-600 hover:underline truncate">{{ $c->website }}</a>
                </div>
                @endif
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Handled Consignments</span>
                <span class="font-bold text-slate-800">{{ $c->shipments_count }} Shipments</span>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-slate-400">
            No carriers configured in database.
        </div>
        @endforelse
    </div>

    @if($carriers->hasPages())
    <div class="p-4 bg-white rounded-2xl border border-slate-200">
        {{ $carriers->links() }}
    </div>
    @endif

</div>

<!-- Modal: Register Carrier (Admin Only) -->
@if(auth()->user()->isAdmin())
<div id="modalAddCarrier" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Register Logistics Carrier</h3>
            <button type="button" onclick="document.getElementById('modalAddCarrier').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('carriers.store') }}" class="space-y-4 mt-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Carrier Name *</label>
                <input type="text" name="name" required placeholder="e.g. Maersk Line, Evergreen Marine"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Carrier Code *</label>
                    <input type="text" name="code" required placeholder="e.g. MAEU, EMC"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Phone</label>
                    <input type="text" name="contact_phone" placeholder="+62 21 555-1234"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Email</label>
                <input type="email" name="contact_email" placeholder="support@carrier.com"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Website</label>
                <input type="url" name="website" placeholder="https://www.maersk.com"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalAddCarrier').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    Save Carrier
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
