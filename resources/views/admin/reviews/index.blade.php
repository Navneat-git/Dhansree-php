@extends('layouts.admin')

@section('title', 'Product Reviews')

@section('content')
<div class="space-y-6" x-data="{
    moderateModalOpen: false,
    selectedReviewId: null,
    selectedAction: '',
    actionTitle: '',
    moderationNote: '',
    openModal(id, action, title) {
        this.selectedReviewId = id;
        this.selectedAction = action;
        this.actionTitle = title;
        this.moderationNote = '';
        this.moderateModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Product Reviews</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Verified customer reviews on delivered purchases. Only approved reviews appear on the storefront.
            </p>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Moderation Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Reviews</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white border {{ $stats['pending'] > 0 ? 'border-amber-300 bg-amber-50/50' : 'border-slate-200/80' }} shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold {{ $stats['pending'] > 0 ? 'text-amber-800' : 'text-slate-500' }} uppercase tracking-wider">Waiting Review</span>
                @if($stats['pending'] > 0)
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-bold {{ $stats['pending'] > 0 ? 'text-amber-700' : 'text-slate-900' }} mt-1">
                {{ number_format($stats['pending']) }}
            </div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Published</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($stats['approved']) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rejected / Hidden</div>
            <div class="text-2xl font-bold text-slate-700 mt-1">{{ number_format($stats['rejected'] + $stats['hidden']) }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-3 sm:space-y-0 sm:flex sm:items-center sm:gap-3">
        <div class="flex-1">
            <label for="search" class="sr-only">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search by review text, product, reviewer or order..."
                    class="w-full pl-9 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                >
            </div>
        </div>

        <div class="w-full sm:w-44">
            <select name="status" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                <option value="">All Statuses</option>
                <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>Waiting for Review</option>
                <option value="APPROVED" {{ ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' }}>Published</option>
                <option value="REJECTED" {{ ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                <option value="HIDDEN" {{ ($filters['status'] ?? '') === 'HIDDEN' ? 'selected' : '' }}>Hidden</option>
            </select>
        </div>

        <div class="w-full sm:w-36">
            <select name="rating" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                <option value="">All Ratings</option>
                <option value="5" {{ ($filters['rating'] ?? '') === '5' ? 'selected' : '' }}>★ 5 Stars</option>
                <option value="4" {{ ($filters['rating'] ?? '') === '4' ? 'selected' : '' }}>★ 4 Stars</option>
                <option value="3" {{ ($filters['rating'] ?? '') === '3' ? 'selected' : '' }}>★ 3 Stars</option>
                <option value="2" {{ ($filters['rating'] ?? '') === '2' ? 'selected' : '' }}>★ 2 Stars</option>
                <option value="1" {{ ($filters['rating'] ?? '') === '1' ? 'selected' : '' }}>★ 1 Star</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-xs">
                Filter
            </button>
            @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['rating']))
                <a href="{{ route('admin.reviews.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-800 font-medium transition-colors">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Reviews Table -->
    <div class="rounded-xl bg-white border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Rating</th>
                        <th class="px-6 py-4">Review Content</th>
                        <th class="px-6 py-4">Product & Order</th>
                        <th class="px-6 py-4">Reviewer</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-slate-50/70 transition-colors {{ $review->status->value === 'PENDING' ? 'bg-amber-50/40' : '' }}">
                            <!-- Rating -->
                            <td class="px-6 py-4 whitespace-nowrap align-top">
                                <div class="flex items-center gap-1 text-amber-500">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                    <span class="ml-1 text-xs font-semibold text-slate-700">({{ $review->rating }}/5)</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    {{ $review->created_at->format('d M Y, h:i A') }}
                                </div>
                            </td>

                            <!-- Review Content -->
                            <td class="px-6 py-4 align-top max-w-md">
                                @if($review->title)
                                    <div class="font-bold text-slate-900 text-sm mb-1">
                                        {{ $review->title }}
                                    </div>
                                @endif
                                <p class="text-slate-700 text-xs leading-relaxed whitespace-pre-line">
                                    {{ $review->body }}
                                </p>
                                @if($review->moderation_note)
                                    <div class="mt-2 text-[11px] p-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-600">
                                        <span class="font-bold text-slate-800">Internal Note:</span> {{ $review->moderation_note }}
                                        @if($review->moderatedByAdminUser)
                                            <span class="text-slate-500">({{ $review->moderatedByAdminUser->name }})</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Product & Order -->
                            <td class="px-6 py-4 align-top">
                                <div class="font-semibold text-slate-900 text-xs">
                                    {{ $review->product?->name ?? 'Product #' . $review->product_id }}
                                </div>
                                @if($review->orderItem?->variant_title && $review->orderItem->variant_title !== 'Default')
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Variant: {{ $review->orderItem->variant_title }}
                                    </div>
                                @endif
                                <div class="mt-1">
                                    <a href="{{ url('/admin/orders') }}?search={{ $review->order?->order_number }}" class="text-[11px] font-mono text-amber-700 hover:underline">
                                        {{ $review->order?->order_number ?? '#' . $review->order_id }}
                                    </a>
                                </div>
                            </td>

                            <!-- Reviewer -->
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-900">
                                    {{ $review->customer?->name ?? 'Customer #' . $review->customer_id }}
                                </div>
                                <div class="text-[11px] text-emerald-700 font-medium flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Verified Buyer
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @if($review->status->value === 'APPROVED')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Published
                                    </span>
                                @elseif($review->status->value === 'PENDING')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Waiting Review
                                    </span>
                                @elseif($review->status->value === 'REJECTED')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Hidden
                                    </span>
                                @endif

                                @if($review->published_at)
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        Pub: {{ $review->published_at->format('d M, H:i') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 align-top text-right whitespace-nowrap">
                                @if($canModerate)
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($review->status->value !== 'APPROVED')
                                            <button
                                                type="button"
                                                @click="openModal({{ $review->id }}, 'APPROVE', 'Approve & Publish Review #{{ $review->id }}')"
                                                class="px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 rounded-lg transition-colors shadow-2xs"
                                                title="Approve and make public on storefront"
                                            >
                                                Approve
                                            </button>
                                        @endif

                                        @if($review->status->value !== 'REJECTED')
                                            <button
                                                type="button"
                                                @click="openModal({{ $review->id }}, 'REJECT', 'Reject Review #{{ $review->id }}')"
                                                class="px-2.5 py-1 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-300 hover:bg-rose-100 rounded-lg transition-colors shadow-2xs"
                                                title="Reject review"
                                            >
                                                Reject
                                            </button>
                                        @endif

                                        @if($review->status->value === 'APPROVED')
                                            <button
                                                type="button"
                                                @click="openModal({{ $review->id }}, 'HIDE', 'Hide Review #{{ $review->id }} from Storefront')"
                                                class="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors shadow-2xs"
                                                title="Hide review"
                                            >
                                                Hide
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Read only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5Z"/></svg>
                                No reviews match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginator->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>

    <!-- Moderation Modal -->
    <div
        x-show="moderateModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs"
    >
        <div
            @click.away="moderateModalOpen = false"
            class="w-full max-w-md bg-white border border-slate-200 rounded-2xl p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900" x-text="actionTitle"></h3>
                <button type="button" @click="moderateModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/admin/reviews/' + selectedReviewId + '/moderate'" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" :value="selectedAction">

                <div>
                    <label for="note" class="block text-xs font-semibold text-slate-700 mb-1">
                        Internal Moderator Note (Optional)
                    </label>
                    <textarea
                        id="note"
                        name="note"
                        x-model="moderationNote"
                        rows="3"
                        placeholder="e.g. Verified quality feedback, or Spurious abuse content..."
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"
                    ></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">This note is visible only to internal staff.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="moderateModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 bg-white border border-slate-300 rounded-lg transition-colors shadow-2xs"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-xs"
                    >
                        Confirm Decision
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
