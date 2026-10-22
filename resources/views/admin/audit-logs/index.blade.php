@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Audit Logs</h1>
                <span class="rounded-full bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                    {{ $logs->total() }} Events
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Immutable security activity audit: sign-ins, product mutations, orders, settings, and staff actions.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <!-- Search -->
            <div class="sm:col-span-2 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search action, entity ID, or IP..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-amber-500">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Action Prefix -->
            <select name="action" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Action Types</option>
                <option value="admin.auth" {{ str_starts_with($currentAction ?? '', 'admin.auth') ? 'selected' : '' }}>Sign-in & 2FA</option>
                <option value="catalog" {{ str_starts_with($currentAction ?? '', 'catalog') ? 'selected' : '' }}>Catalog & Products</option>
                <option value="orders" {{ str_starts_with($currentAction ?? '', 'orders') ? 'selected' : '' }}>Orders & Shipments</option>
                <option value="settings" {{ str_starts_with($currentAction ?? '', 'settings') ? 'selected' : '' }}>Store Settings</option>
            </select>

            <!-- Actor Filter -->
            <select name="admin_user_id" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Staff Members</option>
                @foreach($adminUsers as $u)
                    <option value="{{ $u->id }}" {{ $currentActor == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>

            <!-- Submit -->
            <div class="sm:col-span-4 flex items-center justify-end gap-2 pt-1">
                @if($search || $currentAction || $currentActor)
                    <a href="{{ route('admin.audit-logs.index') }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs font-medium transition-colors">
                        Clear Filters
                    </a>
                @endif
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-2xs transition-colors">
                    Filter Logs
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Timestamp (IST)</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Actor</th>
                        <th class="py-3 px-4">Entity</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200" x-data="{ expanded: null }">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $log->occurred_at ? $log->occurred_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i:s A') : '—' }}
                            </td>

                            <td class="py-3 px-4">
                                <span class="font-mono font-semibold text-slate-900 px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                @if($log->actorAdminUser)
                                    <div class="font-semibold text-slate-800">{{ $log->actorAdminUser->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $log->actorAdminUser->email }}</div>
                                @elseif($log->actor_type?->value === 'SYSTEM')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">SYSTEM</span>
                                @else
                                    <span class="text-slate-400 italic">Guest / External</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 font-mono text-slate-700">
                                @if($log->entity_type)
                                    <span class="text-slate-500">{{ $log->entity_type }}</span>
                                    @if($log->entity_id)
                                        <span class="font-bold text-slate-900">#{{ $log->entity_id }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 font-mono text-slate-600 text-[11px]">
                                {{ $log->ip_address ?: '—' }}
                            </td>

                            <td class="py-3 px-4 text-right">
                                @if(!empty($log->metadata))
                                    <button type="button" @click="expanded = expanded === {{ $log->id }} ? null : {{ $log->id }}"
                                            class="px-2 py-1 rounded border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-[11px] font-medium transition-colors">
                                        <span x-text="expanded === {{ $log->id }} ? 'Hide' : 'Inspect'"></span>
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Metadata row -->
                        @if(!empty($log->metadata))
                            <tr x-show="expanded === {{ $log->id }}" x-cloak class="bg-slate-900 text-slate-200">
                                <td colspan="6" class="p-4 font-mono text-[11px]">
                                    <div class="mb-1 text-slate-400 font-semibold uppercase text-[10px]">Action Metadata Payload:</div>
                                    <pre class="overflow-x-auto whitespace-pre-wrap">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No audit log records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
