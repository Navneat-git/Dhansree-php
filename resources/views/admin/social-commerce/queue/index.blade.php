@extends('layouts.admin')

@section('title', 'Publishing Queue')
@section('page_title', 'Publishing Queue (Meta & Social)')

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.history.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
        Publishing History
    </a>
    <a href="{{ route('admin.social-commerce.connections.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 shadow-xs transition-colors">
        Social Connections
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Queue Safety Information Banner -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 sm:p-5">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-xl bg-blue-600 text-white shrink-0">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-blue-950">
                <p class="font-bold">Explicit Approval Gating Active</p>
                <p class="text-xs text-blue-800 mt-0.5 leading-relaxed">
                    Social Commerce Studio will never publish content to your connected Instagram or Facebook accounts automatically without explicit approval. You can review captions, media slides and scheduled timings here before triggering publishing.
                </p>
            </div>
        </div>
    </div>

    <!-- Queue Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        @if($queueItems->isEmpty())
        <div class="p-12 text-center text-slate-500">
            <svg class="size-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="text-sm font-semibold text-slate-900">Publishing Queue is Empty</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Create a reel in the Reel Studio and click "Send to Publishing Queue" to prepare social media releases.
            </p>
            <div class="mt-4">
                <a href="{{ route('admin.social-commerce.reels.index') }}" class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-bold hover:bg-amber-400 shadow-xs inline-flex items-center gap-1">
                    Open Reel Studio &rarr;
                </a>
            </div>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Item #</th>
                        <th class="py-3 px-4">Content</th>
                        <th class="py-3 px-4">Platforms</th>
                        <th class="py-3 px-4">Scheduled For</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($queueItems as $item)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">
                            #{{ $item->id }}
                        </td>

                        <td class="py-3 px-4">
                            <p class="font-bold text-slate-900">{{ $item->title }}</p>
                            <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                {{ $item->caption_instagram ?: $item->caption_facebook }}
                            </p>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                @foreach($item->platforms as $plat)
                                    @if($plat === 'INSTAGRAM')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Instagram
                                        </span>
                                    @elseif($plat === 'FACEBOOK')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Facebook
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap text-slate-500">
                            {{ $item->scheduled_at ? $item->scheduled_at->format('d M Y, H:i') : 'Immediate on approval' }}
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            @php
                                $badgeClass = match($item->status) {
                                    'PUBLISHED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'APPROVED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'SCHEDULED' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'FAILED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                {{ $item->status }}
                            </span>
                        </td>

                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                @if($item->reel)
                                    <a href="{{ route('admin.social-commerce.reels.show', $item->reel) }}" class="p-1 rounded text-slate-400 hover:text-slate-700" title="Preview Reel">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>
                                @endif

                                @if($item->status === 'AWAITING_APPROVAL' || $item->status === 'DRAFT')
                                    <form method="POST" action="{{ route('admin.social-commerce.queue.approve', $item) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow-xs">
                                            Approve
                                        </button>
                                    </form>
                                @endif

                                @if($item->status === 'APPROVED' || $item->status === 'SCHEDULED')
                                    <form method="POST" action="{{ route('admin.social-commerce.queue.publish-now', $item) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 shadow-xs flex items-center gap-1">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                            </svg>
                                            Publish Now
                                        </button>
                                    </form>
                                @endif

                                @if($item->status === 'FAILED' || $item->status === 'PARTIALLY_PUBLISHED')
                                    <form method="POST" action="{{ route('admin.social-commerce.queue.retry', $item) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-xs">
                                            Retry
                                        </button>
                                    </form>
                                @endif

                                @if($item->status === 'PROCESSING')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-700">
                                        <svg class="animate-spin size-3 text-purple-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Publishing...
                                    </span>
                                @endif

                                <form method="POST" action="{{ route('admin.social-commerce.queue.destroy', $item) }}" onsubmit="return confirm('Remove from publishing queue?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600" title="Delete">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $queueItems->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
