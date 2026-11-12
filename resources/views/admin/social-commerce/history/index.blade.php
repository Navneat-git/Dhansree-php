@extends('layouts.admin')

@section('title', 'Publishing History')
@section('page_title', 'Social Media Publishing History')

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.queue.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
        Publishing Queue
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Stats Ribbon -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Published</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">{{ $stats['total_published'] }}</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-semibold text-rose-600 uppercase tracking-wider block">Instagram Reels</span>
            <span class="text-2xl font-bold text-rose-700 mt-1 block">{{ $stats['instagram_count'] }}</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-semibold text-blue-600 uppercase tracking-wider block">Facebook Reels</span>
            <span class="text-2xl font-bold text-blue-700 mt-1 block">{{ $stats['facebook_count'] }}</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Failed Uploads</span>
            <span class="text-2xl font-bold text-slate-700 mt-1 block">{{ $stats['failed_count'] }}</span>
        </div>
    </div>

    <!-- History Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        @if($logs->isEmpty())
        <div class="p-12 text-center text-slate-500">
            <svg class="size-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="text-sm font-semibold text-slate-900">No publishing history yet</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Once reels or posts are published to your connected Meta channels, their outcome logs, URLs and post IDs will appear here.
            </p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Platform</th>
                        <th class="py-3 px-4">Title / Reel</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Post ID / URL</th>
                        <th class="py-3 px-4">Published At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($logs as $log)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($log->platform === 'INSTAGRAM')
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Instagram
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Facebook
                                </span>
                            @endif
                        </td>

                        <td class="py-3 px-4">
                            <p class="font-bold text-slate-900">{{ $log->queueItem?->title ?? 'Social Post' }}</p>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            @php
                                $badgeClass = match($log->status) {
                                    'SUCCESS' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'FAILED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                {{ $log->status }}
                            </span>
                        </td>

                        <td class="py-3 px-4">
                            @if($log->post_url)
                                <a href="{{ $log->post_url }}" target="_blank" class="text-blue-600 hover:underline font-mono text-[11px] flex items-center gap-1">
                                    View Live Post &rarr;
                                </a>
                            @elseif($log->post_id)
                                <span class="font-mono text-[11px] text-slate-500">{{ $log->post_id }}</span>
                            @elseif($log->error_message)
                                <span class="text-rose-600 text-[11px] line-clamp-1" title="{{ $log->error_message }}">{{ $log->error_message }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap text-slate-500">
                            {{ $log->published_at ? $log->published_at->format('d M Y, H:i') : $log->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
