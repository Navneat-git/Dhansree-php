@extends('layouts.admin')

@section('title', 'Inventory & Stock Levels')

@section('content')
<div class="space-y-6" x-data="{ adjustingVariant: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventory Management</h1>
                <span class="rounded-full bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                    {{ $variants->total() }} SKUs
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Multi-location on-hand inventory, reserved units for pending orders, and restock tracking.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by SKU or product name..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-amber-500">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 px-2 cursor-pointer select-none">
                <input type="checkbox" name="low_stock" value="1" {{ $lowStock ? 'checked' : '' }}
                       class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                Low Stock (&le; 5 units)
            </label>

            <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-semibold uppercase tracking-wider transition-colors shadow-2xs">
                Filter
            </button>

            @if($search || $lowStock)
                <a href="{{ route('admin.inventory.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs font-medium text-center transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Product & Variant</th>
                        <th class="py-3 px-4">SKU</th>
                        <th class="py-3 px-4">Price</th>
                        <th class="py-3 px-4 text-center">On-Hand</th>
                        <th class="py-3 px-4 text-center">Reserved</th>
                        <th class="py-3 px-4 text-center">Available</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($variants as $variant)
                        @php
                            $onHand = $variant->inventoryLevels->sum('on_hand');
                            $reserved = $variant->inventoryLevels->sum('reserved');
                            $available = max(0, $onHand - $reserved);
                            $optNames = $variant->optionValues->pluck('value')->implode(' / ');
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.products.edit', $variant->product) }}" class="font-bold text-slate-900 hover:text-amber-600 transition-colors">
                                    {{ $variant->product?->name }}
                                </a>
                                @if($optNames)
                                    <div class="text-[11px] text-amber-700 font-semibold mt-0.5">{{ $optNames }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700 uppercase">
                                {{ $variant->sku }}
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                ₹{{ number_format(($variant->price_paise ?? 0) / 100, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-mono font-semibold text-slate-700">
                                {{ $onHand }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-mono text-slate-500">
                                {{ $reserved }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-mono">
                                @if($available <= 0)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        OUT OF STOCK (0)
                                    </span>
                                @elseif($available <= 5)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        LOW ({{ $available }})
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $available }} IN STOCK
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                @if($canAdjust)
                                    <button type="button" @click="adjustingVariant = {
                                        id: {{ $variant->id }},
                                        title: '{{ addslashes($variant->product?->name . ' (' . ($optNames ?: $variant->sku) . ')') }}',
                                        currentOnHand: {{ $onHand }}
                                    }" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-[11px] shadow-2xs transition-colors">
                                        Adjust Stock
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                No variants found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($variants->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $variants->links() }}
            </div>
        @endif
    </div>

    <!-- Stock Adjustment Modal -->
    <div x-show="adjustingVariant" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="adjustingVariant = null" aria-hidden="true"></div>

        <!-- Centered Dialog Container -->
        <div class="relative z-10 flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="relative w-full max-w-md my-8 bg-white rounded-2xl text-left shadow-2xl border border-slate-200 overflow-hidden transform transition-all"
                 @click.stop>
                <form method="POST" :action="'{{ url('/admin/inventory/adjust') }}/' + (adjustingVariant ? adjustingVariant.id : '')">
                    @csrf
                    <div class="bg-white px-6 pt-5 pb-4 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Adjust Stock Level</h3>
                            <button type="button" @click="adjustingVariant = null" class="text-slate-400 hover:text-slate-700">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500 font-semibold mb-1">Product Variant:</p>
                            <p class="text-xs font-bold text-slate-900" x-text="adjustingVariant ? adjustingVariant.title : ''"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Inventory Location</label>
                            <select name="location_id" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Quantity</label>
                            <input type="number" name="quantity" required placeholder="+10 or -5"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                            <p class="text-[11px] text-slate-400 mt-1">Enter positive number (e.g. +10) to add stock, or negative (e.g. -2) to deduct.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Note</label>
                            <input type="text" name="reason" placeholder="e.g. Received new shipment from supplier"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="adjustingVariant = null"
                                class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs">
                            Apply Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
