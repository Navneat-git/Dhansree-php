@extends('layouts.store')

@section('title', 'My Account - Dhansree')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-2 mb-6">
        <div>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
                Hello, {{ explode(' ', trim($customer['name']))[0] ?: 'there' }}
            </h1>
            <p class="text-xs sm:text-sm text-rich-black/60 mt-1">
                Your orders, addresses and profile in one place.
            </p>
        </div>
        <p class="text-xs text-rich-black/50">
            Account email: <strong class="text-rich-black font-medium">{{ $customer['email'] }}</strong>
        </p>
    </div>

    @include('store.account.partials.nav')

    <!-- Flash messages -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('storefront.account.orders') }}" class="group p-5 rounded-2xl bg-white border border-light-gold shadow-xs hover:border-gold-400 hover:shadow-md transition-all">
            <span class="text-2xl sm:text-3xl font-serif text-maroon-800 font-semibold tabular-nums block group-hover:scale-105 transition-transform origin-left">
                {{ $orderCount }}
            </span>
            <span class="text-xs font-semibold text-rich-black/60 uppercase tracking-wider mt-1 block">
                Total Orders &rarr;
            </span>
        </a>

        <a href="{{ route('storefront.account.addresses') }}" class="group p-5 rounded-2xl bg-white border border-light-gold shadow-xs hover:border-gold-400 hover:shadow-md transition-all">
            <span class="text-2xl sm:text-3xl font-serif text-maroon-800 font-semibold tabular-nums block group-hover:scale-105 transition-transform origin-left">
                {{ $addressCount }}
            </span>
            <span class="text-xs font-semibold text-rich-black/60 uppercase tracking-wider mt-1 block">
                Saved Addresses &rarr;
            </span>
        </a>

        <div class="p-5 rounded-2xl bg-white border border-light-gold shadow-xs">
            <span class="text-2xl sm:text-3xl font-serif text-maroon-800 font-semibold tabular-nums block">
                {{ $reviewCount }}
            </span>
            <span class="text-xs font-semibold text-rich-black/60 uppercase tracking-wider mt-1 block">
                Reviews Written
            </span>
        </div>
    </div>

    @if($awaitingReview > 0)
        <div class="mb-8 p-4 sm:p-5 rounded-2xl bg-gold-200/40 border border-gold-400 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-semibold text-maroon-900">
                    You have {{ $awaitingReview }} delivered {{ $awaitingReview === 1 ? 'item' : 'items' }} awaiting your feedback!
                </p>
                <p class="text-xs text-rich-black/70">
                    Share your experience to help fellow shoppers make the right choice.
                </p>
            </div>
            <a href="{{ route('storefront.account.orders') }}" class="shrink-0 px-4 py-2 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors">
                View Orders to Review
            </a>
        </div>
    @endif

    <!-- Recent Orders Section -->
    <section class="space-y-4 mb-10">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-serif text-maroon-800 font-semibold">
                Recent Orders
            </h2>
            @if($orderCount > count($recentOrders))
                <a href="{{ route('storefront.account.orders') }}" class="text-xs font-semibold text-maroon-700 hover:text-maroon-900 underline underline-offset-4">
                    See all {{ $orderCount }} orders &rarr;
                </a>
            @endif
        </div>

        @if(empty($recentOrders))
            <div class="p-8 sm:p-12 text-center rounded-3xl bg-white border border-light-gold shadow-xs space-y-4">
                <svg class="w-12 h-12 text-gold-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <div class="space-y-1">
                    <p class="font-medium text-sm text-rich-black">You haven't placed any orders yet</p>
                    <p class="text-xs text-rich-black/60 max-w-sm mx-auto">
                        Explore our handcrafted festive kurtas, coord sets, and smart living essentials.
                    </p>
                </div>
                <a href="{{ url('/shop') }}" class="inline-block px-5 py-2.5 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors">
                    Explore Shop
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($recentOrders as $order)
                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-light-gold shadow-xs hover:border-gold-400 transition-all">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-light-gold/60">
                            <div>
                                <span class="font-mono text-xs font-bold text-rich-black">{{ $order['orderNumber'] }}</span>
                                <span class="text-xs text-rich-black/50 ml-2">Placed {{ \Carbon\Carbon::parse($order['placedAt'])->format('d M Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[0.6875rem] font-semibold border
                                    @if($order['status'] === 'DELIVERED') bg-emerald-50 text-emerald-800 border-emerald-200
                                    @elseif($order['status'] === 'CANCELLED') bg-rose-50 text-rose-800 border-rose-200
                                    @else bg-amber-50 text-amber-800 border-amber-200 @endif">
                                    {{ $order['statusLabel'] }}
                                </span>
                                <span class="font-bold text-sm text-rich-black tabular-nums">
                                    ₹{{ number_format($order['totalPaise'] / 100, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                @foreach($order['items'] as $item)
                                    <div class="relative w-12 h-14 rounded-lg overflow-hidden border border-light-gold bg-cream shrink-0" title="{{ $item['productName'] }}">
                                        @if($item['imageUrl'])
                                            <img src="{{ $item['imageUrl'] }}" alt="{{ $item['productName'] }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-[0.65rem] text-rich-black/40">DS</div>
                                        @endif
                                    </div>
                                @endforeach
                                <span class="text-xs text-rich-black/60">
                                    {{ $order['itemCount'] }} {{ $order['itemCount'] === 1 ? 'item' : 'items' }}
                                </span>
                            </div>

                            <a href="{{ route('storefront.account.order-detail', $order['orderNumber']) }}" class="px-4 py-2 rounded-xl border border-maroon-700 text-maroon-800 hover:bg-maroon-50 text-xs font-semibold tracking-wide transition-colors">
                                View Order Details &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Quick Profile Card -->
    <section class="rounded-3xl bg-white border border-light-gold p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-serif text-maroon-800 font-semibold">
                Profile & Contact Details
            </h2>
            <a href="{{ route('storefront.account.profile') }}" class="text-xs font-semibold text-maroon-700 hover:text-maroon-900 underline underline-offset-4">
                Edit &rarr;
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-cream/60 border border-light-gold/80">
                <span class="text-[0.6875rem] font-semibold text-rich-black/50 uppercase tracking-wider block">Full Name</span>
                <span class="text-sm font-medium text-rich-black mt-0.5 block">{{ $customer['name'] }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-cream/60 border border-light-gold/80">
                <span class="text-[0.6875rem] font-semibold text-rich-black/50 uppercase tracking-wider block">Primary Mobile</span>
                <span class="text-sm font-medium text-rich-black mt-0.5 block">{{ $customer['phone'] ?: 'No phone added yet' }}</span>
            </div>
        </div>
    </section>
</div>
@endsection
