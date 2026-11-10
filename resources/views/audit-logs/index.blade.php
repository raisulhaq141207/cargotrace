@extends('layouts.app')

@section('title', 'Security & Audit Logs')
@section('header_title', 'Audit Logs')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Security &amp; Audit Trail</h1>
        <p class="text-sm text-slate-500">Immutable chronological log of all sensitive user actions and model mutations</p>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50/75 text-slate-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3.5 px-4">Timestamp</th>
                        <th class="py-3.5 px-4">Actor</th>
                        <th class="py-3.5 px-4">Action</th>
                        <th class="py-3.5 px-4">Target Entity</th>
                        <th class="py-3.5 px-4">IP &amp; Device</th>
                        <th class="py-3.5 px-4 text-right">Payload Diff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-3.5 px-4 text-slate-600 font-sans">
                            <div class="font-bold text-slate-800">{{ $log->created_at->format('M d, Y H:i:s') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="py-3.5 px-4 font-sans">
                            @if($log->user)
                            <div class="font-bold text-slate-800">{{ $log->user->name }}</div>
                            <div class="text-[10px] text-slate-400">{{ $log->user->email }} ({{ $log->user->role }})</div>
                            @else
                            <span class="text-slate-400 italic">System / Anonymous</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-sans">
                            @php
                                $actionColors = [
                                    'CREATE' => 'bg-emerald-100 text-emerald-800',
                                    'UPDATE' => 'bg-blue-100 text-blue-800',
                                    'DELETE' => 'bg-rose-100 text-rose-800',
                                    'STATUS_CHANGE' => 'bg-indigo-100 text-indigo-800',
                                    'EXCEPTION_CREATED' => 'bg-amber-100 text-amber-800',
                                    'EXCEPTION_RESOLVED' => 'bg-emerald-100 text-emerald-800',
                                    'LOGIN' => 'bg-slate-100 text-slate-700',
                                    'LOGOUT' => 'bg-slate-100 text-slate-600',
                                ];
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $actionColors[$log->action] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-700 font-sans">
                            <span class="font-bold">{{ class_basename($log->auditable_type) }}</span>
                            @if($log->auditable_id)
                            <span class="text-slate-400">#{{ $log->auditable_id }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 font-sans">
                            <div class="font-mono text-xs">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                            <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ $log->user_agent }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-right font-sans">
                            @if($log->old_values || $log->new_values)
                            <details class="cursor-pointer text-left inline-block">
                                <summary class="text-xs font-semibold text-blue-600 hover:underline">View Diff</summary>
                                <div class="mt-2 p-3 bg-slate-900 text-slate-200 rounded-xl text-[10px] font-mono max-w-md overflow-x-auto shadow-xl">
                                    @if($log->old_values)
                                    <div class="text-rose-400 font-bold mb-1">Old Values:</div>
                                    <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                    @if($log->new_values)
                                    <div class="text-emerald-400 font-bold mt-2 mb-1">New Values:</div>
                                    <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                </div>
                            </details>
                            @else
                            <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 font-sans">No audit logs recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-4 border-t border-slate-100 font-sans">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

