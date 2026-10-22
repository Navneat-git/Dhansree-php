@extends('layouts.store')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16 space-y-8">
    <!-- Back to lookup -->
    <div class="flex items-center justify-between">
        <a href="{{ route('store.track-order.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-rich-black/70 hover:text-maroon-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Track Another Order
        </a>
        <span class="text-xs text-rich-black/50">Last updated: {{ now()->timezone('Asia/Kolkata')->format('h:i A, d M Y') }}</span>
    </div>

    <!-- Order Header Card -->
    <div class="bg-white rounded-3xl border border-light-gold shadow-sm p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-light-gold/60">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-rich-black/50 block">Shipment for Order</span>
                <h1 class="text-2xl sm:text-3xl font-mono font-bold text-maroon-800 mt-0.5">{{ $order->order_number }}</h1>
                <p class="text-xs text-rich-black/60 mt-1">
                    Placed on {{ $order->placed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                @php
                    $statusBadge = match($order->status->value) {
                        'CONFIRMED' => 'bg-blue-100 text-blue-900 border-blue-300',
                        'PROCESSING' => 'bg-amber-100 text-amber-900 border-amber-300',
                        'SHIPPED' => 'bg-purple-100 text-purple-900 border-purple-300',
                        'DELIVERED' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                        'CANCELLED' => 'bg-rose-100 text-rose-900 border-rose-300',
                        default => 'bg-slate-100 text-slate-800 border-slate-300',
                    };
                @endphp
                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold border uppercase tracking-wider {{ $statusBadge }}">
                    {{ $order->status->value }}
                </span>
            </div>
        </div>

        <!-- Visual Tracking Stepper -->
        <div class="pt-8">
            <h2 class="text-xs font-bold uppercase tracking-wider text-rich-black/60 mb-6">Fulfillment Progress</h2>

            <!-- Horizontal Desktop Stepper -->
            <div class="hidden md:grid md:grid-cols-6 gap-2 relative">
                @foreach($steps as $idx => $step)
                    <div class="relative flex flex-col items-center text-center">
                        <!-- Connector Line -->
                        @if($idx < count($steps) - 1)
                            <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $steps[$idx + 1]['completed'] ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                        @endif

                        <!-- Step Circle Icon -->
                        <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all shadow-xs
                                    {{ $step['completed'] ? 'bg-emerald-600 text-white' : ($step['current'] ? 'bg-gold-500 text-white ring-4 ring-gold-200 animate-pulse' : 'bg-slate-100 text-slate-400 border border-slate-300') }}">
                            @if($step['completed'])
                                ✓
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>

                        <!-- Step Details -->
                        <div class="mt-3 space-y-1">
                            <h3 class="text-xs font-bold {{ $step['completed'] || $step['current'] ? 'text-rich-black' : 'text-slate-400' }}">
                                {{ $step['title'] }}
                            </h3>
                            <p class="text-[10px] text-rich-black/60 leading-tight">
                                {{ $step['description'] }}
                            </p>
                            @if($step['date'])
                                <p class="text-[9px] text-rich-black/40 font-mono">
                                    {{ $step['date']->timezone('Asia/Kolkata')->format('d M, h:i A') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Vertical Mobile Stepper -->
            <div class="md:hidden space-y-6 relative pl-6 before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                @foreach($steps as $idx => $step)
                    <div class="relative">
                        <!-- Circle icon -->
                        <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shadow-xs
                                     {{ $step['completed'] ? 'bg-emerald-600 text-white' : ($step['current'] ? 'bg-gold-500 text-white ring-4 ring-gold-200' : 'bg-slate-100 text-slate-400 border border-slate-300') }}">
                            @if($step['completed']) ✓ @else {{ $idx + 1 }} @endif
                        </span>
                        <div class="space-y-0.5">
                            <h3 class="text-xs font-bold {{ $step['completed'] || $step['current'] ? 'text-rich-black' : 'text-slate-400' }}">
                                {{ $step['title'] }}
                            </h3>
                            <p class="text-xs text-rich-black/70">{{ $step['description'] }}</p>
                            @if($step['date'])
                                <p class="text-[10px] text-rich-black/50 font-mono">
                                    {{ $step['date']->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Live Courier Details Box -->
        @if($activeShipment && $activeShipment->awb)
            <div class="mt-8 p-5 sm:p-6 rounded-2xl bg-warm-beige/40 border border-light-gold flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] uppercase tracking-wider font-bold text-maroon-800">Assigned Delivery Partner</span>
                    <h3 class="text-base sm:text-lg font-bold text-rich-black flex items-center gap-2">
                        <span>{{ $activeShipment->courier_name ?? 'Express Courier Partner' }}</span>
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-rich-black/70">
                        <span>AWB Tracking Code:</span>
                        <strong class="font-mono text-maroon-800 bg-white/80 px-2 py-0.5 rounded border border-light-gold/60">{{ $activeShipment->awb }}</strong>
                    </div>
                </div>

                @if($activeShipment->tracking_url)
                    <a href="{{ $activeShipment->tracking_url }}" target="_blank"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-maroon-800 hover:bg-maroon-700 text-white text-xs font-bold uppercase tracking-wider transition-all shadow-xs">
                        <span>Track on Courier Site</span>
                        <svg class="w-4 h-4 text-gold-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif
            </div>
        @else
            <div class="mt-8 p-5 rounded-2xl bg-ivory/80 border border-light-gold/80 flex items-center gap-4 text-xs text-rich-black/70">
                <span class="text-2xl">📦</span>
                <div>
                    <h4 class="font-bold text-rich-black">Packaging & Dispatch In Progress</h4>
                    <p class="mt-0.5 text-rich-black/60">Our dispatch team is packaging your items. Courier AWB tracking will update as soon as the courier scans your package.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Order Items & Shipping Address Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Items (2 cols) -->
        <div class="md:col-span-2 bg-white rounded-3xl border border-light-gold shadow-sm p-6 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-rich-black/60">Items in this Delivery</h2>
            <div class="divide-y divide-light-gold/40">
                @foreach($order->items as $item)
                    <div class="py-3 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                        <div class="flex items-center gap-3">
                            @if($item->variant?->product?->primary_image_url)
                                <img src="{{ $item->variant->product->primary_image_url }}" alt="{{ $item->product_name }}"
                                     class="w-14 h-14 rounded-xl object-cover border border-light-gold shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-xl bg-warm-beige/40 border border-light-gold flex items-center justify-center text-rich-black/40 text-lg shrink-0">
                                    👗
                                </div>
                            @endif
                            <div>
                                <h3 class="text-xs sm:text-sm font-semibold text-rich-black line-clamp-1">{{ $item->product_name }}</h3>
                                <div class="text-[11px] text-rich-black/60 mt-0.5">
                                    @if($item->variant_title)
                                        <span class="font-medium text-maroon-800">{{ $item->variant_title }}</span> &bull;
                                    @endif
                                    Qty: <strong>{{ $item->quantity }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="text-right text-xs font-bold text-rich-black">
                            ₹{{ number_format($item->line_total_paise / 100, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Total Paid Bar -->
            <div class="pt-3 border-t border-light-gold/60 flex items-center justify-between text-xs font-bold">
                <span class="text-rich-black/60">Total Order Amount</span>
                <span class="text-maroon-800 text-sm">₹{{ number_format($order->total_rupees, 2) }}</span>
            </div>
        </div>

        <!-- Address & Support (1 col) -->
        <div class="space-y-6">
            <!-- Delivery Address -->
            <div class="bg-white rounded-3xl border border-light-gold shadow-sm p-6 space-y-2 text-xs">
                <h2 class="text-[10px] font-bold uppercase tracking-wider text-rich-black/60">Delivery Address</h2>
                @if($order->shippingAddress)
                    <div class="text-rich-black/80 space-y-0.5 font-medium">
                        <p class="font-bold text-rich-black text-sm">{{ $order->shippingAddress->recipient_name }}</p>
                        <p>{{ $order->shippingAddress->line1 }}</p>
                        @if($order->shippingAddress->line2)
                            <p>{{ $order->shippingAddress->line2 }}</p>
                        @endif
                        <p>{{ $order->shippingAddress->city }}, {{ $stateName }} - {{ $order->shippingAddress->postal_code }}</p>
                        <p class="text-rich-black/50 text-[11px] pt-1">Phone: {{ $order->shippingAddress->phone }}</p>
                    </div>
                @endif
            </div>

            <!-- Customer Support Help -->
            <div class="bg-warm-beige/30 rounded-3xl border border-light-gold p-6 space-y-3 text-xs">
                <h3 class="font-bold text-rich-black">Need assistance with your delivery?</h3>
                <p class="text-rich-black/70 text-[11px] leading-relaxed">
                    Our team is here to help with address corrections, rescheduling, or any delivery inquiries.
                </p>
                @php
                    $supportPhone = preg_replace('/\D/', '', config('store.whatsapp_phone', '919876543210'));
                @endphp
                <a href="https://wa.me/{{ $supportPhone }}?text=Hi%20Dhansree,%20I%20need%20help%20with%20my%20order%20{{ $order->order_number }}"
                   target="_blank"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors shadow-xs">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    Chat with Us on WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
