@extends('layouts.store')

@section('title', 'My Reviews - Dhansree')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{
    editModalOpen: false,
    selectedReviewId: null,
    rating: 5,
    title: '',
    body: '',
    openEdit(id, currentRating, currentTitle, currentBody) {
        this.selectedReviewId = id;
        this.rating = currentRating;
        this.title = currentTitle || '';
        this.body = currentBody;
        this.editModalOpen = true;
    }
}">
    <!-- Account Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/60 mb-6">
        <a href="{{ route('store.home') }}" class="hover:text-maroon-800">Home</a>
        <span>/</span>
        <a href="{{ route('storefront.account.dashboard') }}" class="hover:text-maroon-800">My Account</a>
        <span>/</span>
        <span class="text-maroon-900 font-medium">My Reviews</span>
    </nav>

    <!-- Account Navigation Tabs -->
    @include('store.account.partials.nav')

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Reviews Section -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-serif text-2xl font-bold text-rich-black">My Reviews & Ratings</h1>
                <p class="text-xs text-rich-black/60 mt-1">Feedback you've written on purchases delivered to you.</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-gold-200/50 text-maroon-900 border border-gold-400">
                {{ $reviews->total() }} {{ Str::plural('Review', $reviews->total()) }}
            </span>
        </div>

        @if($reviews->count() > 0)
            <div class="grid grid-cols-1 gap-4">
                @foreach($reviews as $review)
                    <div class="p-6 rounded-2xl bg-white border border-light-gold/70 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gold-200/40 border border-gold-400/60 flex items-center justify-center shrink-0 text-maroon-900 font-bold text-base">
                                    ★
                                </div>
                                <div>
                                    <h3 class="font-semibold text-rich-black text-sm">
                                        @if($review->product && $review->product->status === 'ACTIVE')
                                            <a href="{{ route('store.product', $review->product->slug) }}" class="hover:text-maroon-800 transition-colors">
                                                {{ $review->orderItem?->product_name ?? $review->product->name }}
                                            </a>
                                        @else
                                            {{ $review->orderItem?->product_name ?? 'Product' }}
                                        @endif
                                    </h3>
                                    @if($review->orderItem?->variant_title && $review->orderItem->variant_title !== 'Default')
                                        <div class="text-xs text-rich-black/50 mt-0.5">
                                            Variant: {{ $review->orderItem->variant_title }}
                                        </div>
                                    @endif
                                    <div class="text-[11px] text-rich-black/40 mt-1">
                                        Purchased on Order <span class="font-mono font-medium">{{ $review->order?->order_number }}</span> • {{ $review->created_at->format('d M Y') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="sm:text-right">
                                @if($review->status->value === 'APPROVED')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Published
                                    </span>
                                @elseif($review->status->value === 'PENDING')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Waiting for approval
                                    </span>
                                @elseif($review->status->value === 'REJECTED')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-600">
                                        Hidden
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Rating & Review Body -->
                        <div class="p-4 rounded-xl bg-ivory/60 border border-light-gold/50 space-y-2">
                            <div class="flex items-center gap-1 text-gold-600">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'fill-gold-500 text-gold-500' : 'text-zinc-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                                <span class="ml-1 text-xs font-bold text-rich-black/80">{{ $review->rating }} out of 5</span>
                            </div>

                            @if($review->title)
                                <div class="font-semibold text-rich-black text-sm">
                                    {{ $review->title }}
                                </div>
                            @endif

                            <p class="text-xs text-rich-black/80 leading-relaxed whitespace-pre-line">
                                {{ $review->body }}
                            </p>
                        </div>

                        <!-- Edit Button (if within 30 days) -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            <div class="text-rich-black/50 text-[11px]">
                                @if($review->isEditable())
                                    <span>Editable for 30 days after submission</span>
                                @else
                                    <span class="italic text-rich-black/40">Edit window closed</span>
                                @endif
                            </div>

                            @if($review->isEditable())
                                <button
                                    type="button"
                                    @click="openEdit({{ $review->id }}, {{ $review->rating }}, '{{ addslashes($review->title ?? '') }}', '{{ addslashes(str_replace(["\r", "\n"], [' ', ' '], $review->body)) }}')"
                                    class="px-3 py-1.5 text-xs font-semibold text-maroon-800 bg-gold-200/40 hover:bg-gold-200 border border-gold-400 rounded-lg transition-colors"
                                >
                                    Edit Review
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($reviews->hasPages())
                <div class="pt-6">
                    {{ $reviews->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center rounded-2xl bg-white border border-light-gold/70 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-gold-200/40 text-gold-600 flex items-center justify-center text-2xl font-bold">
                    ★
                </div>
                <div class="max-w-md mx-auto">
                    <h3 class="font-serif text-lg font-bold text-rich-black">No Reviews Written Yet</h3>
                    <p class="text-xs text-rich-black/60 mt-1">
                        When you purchase items on Dhansree and they are delivered to your address, you can share your feedback to help other buyers.
                    </p>
                </div>
                <div>
                    <a
                        href="{{ route('storefront.account.orders') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-maroon-800 hover:bg-maroon-900 text-white font-semibold text-xs shadow-md transition-colors"
                    >
                        <span>View Delivered Orders</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Edit Review Modal -->
    <div
        x-show="editModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/70 flex items-center justify-center p-4"
    >
        <div
            @click.away="editModalOpen = false"
            class="w-full max-w-lg bg-white rounded-2xl p-6 shadow-2xl space-y-5 border border-light-gold"
        >
            <div class="flex items-center justify-between border-b border-light-gold/60 pb-3">
                <h3 class="font-serif text-lg font-bold text-rich-black">Edit Your Review</h3>
                <button type="button" @click="editModalOpen = false" class="text-rich-black/40 hover:text-rich-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/account/reviews/' + selectedReviewId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- Rating Stars -->
                <div>
                    <label class="block text-xs font-semibold text-rich-black mb-1.5">Rating (1 to 5 Stars)</label>
                    <div class="flex items-center gap-2">
                        <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                            <button
                                type="button"
                                @click="rating = star"
                                class="text-2xl transition-transform hover:scale-110"
                                :class="star <= rating ? 'text-gold-500' : 'text-zinc-300'"
                            >
                                ★
                            </button>
                        </template>
                        <input type="hidden" name="rating" :value="rating">
                        <span class="text-xs font-bold text-rich-black/70 ml-2" x-text="rating + ' of 5 Stars'"></span>
                    </div>
                </div>

                <!-- Review Title -->
                <div>
                    <label for="modal_title" class="block text-xs font-semibold text-rich-black mb-1">Headline / Title (Optional)</label>
                    <input
                        type="text"
                        id="modal_title"
                        name="title"
                        x-model="title"
                        maxlength="80"
                        placeholder="e.g. Beautiful fabric and perfect fitting!"
                        class="w-full px-3 py-2 text-xs border border-light-gold rounded-xl focus:outline-none focus:border-maroon-700 text-rich-black"
                    >
                </div>

                <!-- Review Body -->
                <div>
                    <label for="modal_body" class="block text-xs font-semibold text-rich-black mb-1">Your Review (Min 10 characters)</label>
                    <textarea
                        id="modal_body"
                        name="body"
                        x-model="body"
                        rows="4"
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="What did you like or dislike? How was the quality, fit, and finish?"
                        class="w-full px-3 py-2 text-xs border border-light-gold rounded-xl focus:outline-none focus:border-maroon-700 text-rich-black"
                    ></textarea>
                    <div class="flex items-center justify-between text-[11px] text-rich-black/50 mt-1">
                        <span>Minimum 10 characters</span>
                        <span x-text="body.length + ' / 2000'"></span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                    <p class="font-semibold">Note on Review Edits:</p>
                    <p class="mt-0.5 text-[11px] text-amber-800">Editing will temporarily hide this review from the store until staff reviews and re-approves the updated text.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="editModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-rich-black/60 hover:text-rich-black"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2 text-xs font-semibold text-white bg-maroon-800 hover:bg-maroon-900 rounded-xl transition-colors shadow-sm"
                    >
                        Save Updated Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
