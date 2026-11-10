@extends('layouts.app')

@section('title', 'Notifications')
@section('header_title', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notifications</h1>
            <p class="text-sm text-slate-500">Real-time alerts for ETA changes, status transitions, and exceptions.</p>
        </div>

        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                <i class="fa-solid fa-check-double mr-1"></i> Mark All as Read
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm divide-y divide-slate-100 overflow-hidden">
        @forelse($notifications as $notif)
        <div class="p-5 flex items-start justify-between gap-4 {{ $notif->read_at ? 'bg-white' : 'bg-blue-50/30' }}">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm {{ $notif->type === 'EXCEPTION_OPENED' ? 'bg-rose-100 text-rose-600' : ($notif->type === 'DELAY_ALERT' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600') }}">
                    <i class="fa-solid {{ $notif->type === 'EXCEPTION_OPENED' ? 'fa-triangle-exclamation' : ($notif->type === 'DELAY_ALERT' ? 'fa-clock' : 'fa-bell') }}"></i>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-slate-900">{{ $notif->title }}</h3>
                    <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ $notif->message }}</p>
                    <div class="flex items-center gap-3 text-[10px] text-slate-400 mt-2">
                        <span>{{ $notif->created_at->diffForHumans() }}</span>
                        @if($notif->shipment_id)
                        <span>•</span>
                        <a href="{{ route('shipments.show', $notif->shipment_id) }}" class="font-bold text-blue-600 hover:underline">
                            View Shipment
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            @if(!$notif->read_at)
            <form method="POST" action="{{ route('notifications.read', $notif) }}">
                @csrf
                <button type="submit" title="Mark as read" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100">
                    <i class="fa-solid fa-check text-xs"></i>
                </button>
            </form>
            @endif
        </div>
        @empty
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-bell-slash text-3xl text-slate-300 mb-2"></i>
            <p class="text-xs">You have no notifications at this time.</p>
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
    <div>
        {{ $notifications->links() }}
    </div>
    @endif

</div>
@endsection

