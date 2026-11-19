@extends('layouts.admin')

@section('title', 'Edit Invoice ' . $invoice->invoice_number)
@section('page_title', 'Edit Bulk Invoice')

@section('content')
@php
    $initialItems = $invoice->items->map(function ($it) {
        return [
            'product_id' => $it->product_id,
            'item_name' => $it->item_name,
            'description' => $it->description ?? '',
            'hsn_code' => $it->hsn_code ?? '6204',
            'quantity' => $it->quantity,
            'unit' => $it->unit ?? 'PCS',
            'unit_price' => $it->unit_price_rupees,
            'discount' => $it->discount_rupees,
            'gst_rate' => $it->gst_rate_percent,
        ];
    })->values();
@endphp

<div x-data="invoiceForm()" class="space-y-6">
    <!-- Header with Breadcrumbs & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.bulk-invoices.index') }}" class="hover:text-slate-800">Bulk Invoices</a>
                <span>/</span>
                <a href="{{ route('admin.bulk-invoices.show', $invoice) }}" class="hover:text-slate-800 font-mono">{{ $invoice->invoice_number }}</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Edit</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Wholesale Invoice: <span class="font-mono">{{ $invoice->invoice_number }}</span></h1>
            <p class="text-xs text-slate-500 mt-0.5">Modify line items, wholesale pricing, customer details or taxes.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.bulk-invoices.show', $invoice) }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="button" @click="$refs.invoiceSubmitBtn.click()"
                    style="background-color: #0f172a !important; color: #ffffff !important;"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm hover:opacity-90 transition-all">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Update Invoice
            </button>
        </div>
    </div>

    <!-- Main Form -->
    <form method="POST" action="{{ route('admin.bulk-invoices.update', $invoice) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. Invoice Meta & Seller Info -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="size-8 rounded-lg bg-amber-50 text-amber-600 font-bold text-xs flex items-center justify-center">
                        01
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Invoice Details</h2>
                        <p class="text-[11px] text-slate-400">Invoice numbering and dates.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Invoice Number -->
                <div>
                    <label for="invoice_number" class="block text-xs font-semibold text-slate-700 mb-1">
                        Invoice Number <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="invoice_number" id="invoice_number"
                           value="{{ old('invoice_number', $invoice->invoice_number) }}" required
                           class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    @error('invoice_number') <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Invoice Date -->
                <div>
                    <label for="invoice_date" class="block text-xs font-semibold text-slate-700 mb-1">
                        Invoice Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="invoice_date" id="invoice_date"
                           value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    @error('invoice_date') <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Due Date -->
                <div>
                    <label for="due_date" class="block text-xs font-semibold text-slate-700 mb-1">
                        Payment Due Date
                    </label>
                    <input type="date" name="due_date" id="due_date"
                           value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                </div>

                <!-- Payment Terms -->
                <div>
                    <label for="payment_terms" class="block text-xs font-semibold text-slate-700 mb-1">
                        Payment Terms
                    </label>
                    <select name="payment_terms" id="payment_terms"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        <option value="Due on Receipt" {{ old('payment_terms', $invoice->payment_terms) === 'Due on Receipt' ? 'selected' : '' }}>Due on Receipt</option>
                        <option value="100% Advance" {{ old('payment_terms', $invoice->payment_terms) === '100% Advance' ? 'selected' : '' }}>100% Advance Payment</option>
                        <option value="50% Advance, 50% on Delivery" {{ old('payment_terms', $invoice->payment_terms) === '50% Advance, 50% on Delivery' ? 'selected' : '' }}>50% Advance, 50% on Delivery</option>
                        <option value="Net 7 Days" {{ old('payment_terms', $invoice->payment_terms) === 'Net 7 Days' ? 'selected' : '' }}>Net 7 Days</option>
                        <option value="Net 15 Days" {{ old('payment_terms', $invoice->payment_terms) === 'Net 15 Days' ? 'selected' : '' }}>Net 15 Days</option>
                        <option value="Net 30 Days" {{ old('payment_terms', $invoice->payment_terms) === 'Net 30 Days' ? 'selected' : '' }}>Net 30 Days</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Customer & Address Information -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <span class="size-8 rounded-lg bg-amber-50 text-amber-600 font-bold text-xs flex items-center justify-center">
                    02
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Customer & Billing Information</h2>
                    <p class="text-[11px] text-slate-400">Buyer identity, business details, GSTIN, and delivery addresses.</p>
                </div>
            </div>

            <!-- Customer Identity -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Customer Name -->
                <div>
                    <label for="customer_name" class="block text-xs font-semibold text-slate-700 mb-1">
                        Customer / Contact Person <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="customer_name" id="customer_name"
                           value="{{ old('customer_name', $invoice->customer_name) }}" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    @error('customer_name') <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Business / Firm Name -->
                <div>
                    <label for="business_name" class="block text-xs font-semibold text-slate-700 mb-1">
                        Business / Trade Name
                    </label>
                    <input type="text" name="business_name" id="business_name"
                           value="{{ old('business_name', $invoice->business_name) }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                </div>

                <!-- Customer GSTIN -->
                <div>
                    <label for="customer_gstin" class="block text-xs font-semibold text-slate-700 mb-1">
                        Buyer GSTIN
                    </label>
                    <input type="text" name="customer_gstin" id="customer_gstin"
                           value="{{ old('customer_gstin', $invoice->customer_gstin) }}"
                           x-model="customerGstin"
                           @input="onGstinInput()"
                           maxlength="15"
                           class="w-full px-3 py-2 text-xs font-mono uppercase rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                </div>

                <!-- Phone -->
                <div>
                    <label for="customer_phone" class="block text-xs font-semibold text-slate-700 mb-1">
                        Phone / Mobile
                    </label>
                    <input type="text" name="customer_phone" id="customer_phone"
                           value="{{ old('customer_phone', $invoice->customer_phone) }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                </div>

                <!-- Email -->
                <div>
                    <label for="customer_email" class="block text-xs font-semibold text-slate-700 mb-1">
                        Email Address
                    </label>
                    <input type="email" name="customer_email" id="customer_email"
                           value="{{ old('customer_email', $invoice->customer_email) }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                </div>

                <!-- PAN -->
                <div>
                    <label for="customer_pan" class="block text-xs font-semibold text-slate-700 mb-1">
                        Buyer PAN
                    </label>
                    <input type="text" name="customer_pan" id="customer_pan"
                           value="{{ old('customer_pan', $invoice->customer_pan) }}"
                           x-model="customerPan"
                           maxlength="10"
                           class="w-full px-3 py-2 text-xs font-mono uppercase rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                </div>
            </div>

            <!-- Billing Address -->
            <div class="space-y-3 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Billing Address</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="billing_line1" class="block text-xs font-semibold text-slate-700 mb-1">
                            Address Line 1 <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="billing_line1" id="billing_line1"
                               value="{{ old('billing_line1', $invoice->billing_line1) }}" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>

                    <div>
                        <label for="billing_line2" class="block text-xs font-semibold text-slate-700 mb-1">
                            Address Line 2
                        </label>
                        <input type="text" name="billing_line2" id="billing_line2"
                               value="{{ old('billing_line2', $invoice->billing_line2) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>

                    <div>
                        <label for="billing_city" class="block text-xs font-semibold text-slate-700 mb-1">
                            City <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="billing_city" id="billing_city"
                               value="{{ old('billing_city', $invoice->billing_city) }}" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>

                    <div>
                        <label for="billing_state_code" class="block text-xs font-semibold text-slate-700 mb-1">
                            State <span class="text-rose-500">*</span>
                        </label>
                        <select name="billing_state_code" id="billing_state_code"
                                x-model="billingState"
                                required
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                            @foreach($states as $code => $name)
                                <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="billing_postal_code" class="block text-xs font-semibold text-slate-700 mb-1">
                            PIN Code <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="billing_postal_code" id="billing_postal_code"
                               value="{{ old('billing_postal_code', $invoice->billing_postal_code) }}" required
                               maxlength="10"
                               class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>
                </div>
            </div>

            <!-- Place of Supply & Tax Routing -->
            <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200/80 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <label for="place_of_supply_state_code" class="block text-xs font-bold text-slate-900">
                            Place of Supply (POS) <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-600 mt-0.5">
                            Determines GST Tax category. Rajasthan (08) = Intra-State (CGST + SGST). Other States = Inter-State (IGST).
                        </p>
                    </div>
                    <div class="sm:w-64">
                        <select name="place_of_supply_state_code" id="place_of_supply_state_code"
                                x-model="posState"
                                @change="recomputeTotals()"
                                required
                                class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-amber-300 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-white">
                            @foreach($states as $code => $name)
                                <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-2 border-t border-amber-200/60">
                    <span class="text-slate-700 font-medium">Supply Type:</span>
                    <span x-show="isIntraState()" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                        Intra-State (CGST + SGST)
                    </span>
                    <span x-show="!isIntraState()" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800">
                        Inter-State (IGST)
                    </span>
                </div>
            </div>

            <!-- Shipping Same as Billing Toggle -->
            <div class="pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="shipping_same_as_billing" value="1"
                           x-model="sameShipping"
                           class="size-4 rounded text-slate-900 focus:ring-slate-900 border-slate-300">
                    <span class="text-xs font-semibold text-slate-800">Shipping address is same as billing address</span>
                </label>
            </div>

            <!-- Shipping Address -->
            <div x-show="!sameShipping" x-cloak class="space-y-3 pt-3 border-t border-slate-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Shipping Address (Consignee)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="shipping_recipient_name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Consignee / Recipient Name
                        </label>
                        <input type="text" name="shipping_recipient_name" id="shipping_recipient_name"
                               value="{{ old('shipping_recipient_name', $invoice->shipping_recipient_name) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="shipping_line1" class="block text-xs font-semibold text-slate-700 mb-1">
                            Address Line 1
                        </label>
                        <input type="text" name="shipping_line1" id="shipping_line1"
                               value="{{ old('shipping_line1', $invoice->shipping_line1) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>

                    <div>
                        <label for="shipping_city" class="block text-xs font-semibold text-slate-700 mb-1">
                            City
                        </label>
                        <input type="text" name="shipping_city" id="shipping_city"
                               value="{{ old('shipping_city', $invoice->shipping_city) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>

                    <div>
                        <label for="shipping_state_code" class="block text-xs font-semibold text-slate-700 mb-1">
                            State
                        </label>
                        <select name="shipping_state_code" id="shipping_state_code"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                            @foreach($states as $code => $name)
                                <option value="{{ $code }}" {{ old('shipping_state_code', $invoice->shipping_state_code) === $code ? 'selected' : '' }}>{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="shipping_postal_code" class="block text-xs font-semibold text-slate-700 mb-1">
                            PIN Code
                        </label>
                        <input type="text" name="shipping_postal_code" id="shipping_postal_code"
                               value="{{ old('shipping_postal_code', $invoice->shipping_postal_code) }}"
                               maxlength="10"
                               class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Line Items (Dynamic Repeater) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="size-8 rounded-lg bg-amber-50 text-amber-600 font-bold text-xs flex items-center justify-center">
                        03
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Products & Bulk Line Items</h2>
                        <p class="text-[11px] text-slate-400">Modify wholesale prices, quantities, and GST rates.</p>
                    </div>
                </div>
                <button type="button" @click="addItem()"
                        style="background-color: #f1f5f9; color: #0f172a;"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 hover:bg-slate-200 transition-colors">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Line Item
                </button>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3 w-10 text-center">#</th>
                            <th class="py-2.5 px-3 min-w-[240px]">Product / Description</th>
                            <th class="py-2.5 px-3 w-24">HSN</th>
                            <th class="py-2.5 px-3 w-20 text-center">Qty</th>
                            <th class="py-2.5 px-3 w-20">Unit</th>
                            <th class="py-2.5 px-3 w-28 text-right">Price (₹)</th>
                            <th class="py-2.5 px-3 w-24 text-right">Disc (₹)</th>
                            <th class="py-2.5 px-3 w-24 text-center">GST %</th>
                            <th class="py-2.5 px-3 w-28 text-right">Taxable (₹)</th>
                            <th class="py-2.5 px-3 w-28 text-right">Total (₹)</th>
                            <th class="py-2.5 px-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-3 text-center font-mono text-slate-400 font-semibold" x-text="index + 1"></td>

                                <td class="py-3 px-3 space-y-1.5">
                                    <select @change="pickProduct(index, $event.target.value)"
                                            class="w-full px-2.5 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 text-slate-600 focus:outline-hidden focus:ring-1 focus:ring-slate-900">
                                        <option value="">-- Choose from Catalog (or enter manually) --</option>
                                        <template x-for="prod in catalogProducts" :key="prod.id">
                                            <option :value="prod.id" :selected="prod.id == item.product_id" x-text="prod.name + ' (₹' + (prod.price_paise / 100).toFixed(2) + ')'"></option>
                                        </template>
                                    </select>
                                    <input type="hidden" :name="'items[' + index + '][product_id]'" x-model="item.product_id">

                                    <input type="text" :name="'items[' + index + '][item_name]'"
                                           x-model="item.item_name" required
                                           placeholder="Item / Product Name *"
                                           class="w-full px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-white">

                                    <input type="text" :name="'items[' + index + '][description]'"
                                           x-model="item.description"
                                           placeholder="Optional description / fabric / color notes"
                                           class="w-full px-2.5 py-1 text-[11px] rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white text-slate-600">
                                </td>

                                <td class="py-3 px-3">
                                    <input type="text" :name="'items[' + index + '][hsn_code]'"
                                           x-model="item.hsn_code"
                                           class="w-full px-2 py-1.5 text-xs font-mono rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white">
                                </td>

                                <td class="py-3 px-3">
                                    <input type="number" :name="'items[' + index + '][quantity]'"
                                           x-model.number="item.quantity"
                                           @input="recomputeTotals()"
                                           min="1" required
                                           class="w-full px-2 py-1.5 text-xs text-center font-bold rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white">
                                </td>

                                <td class="py-3 px-3">
                                    <select :name="'items[' + index + '][unit]'" x-model="item.unit"
                                            class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white">
                                        <option value="PCS">PCS</option>
                                        <option value="SET">SET</option>
                                        <option value="METERS">MTR</option>
                                        <option value="BOX">BOX</option>
                                    </select>
                                </td>

                                <td class="py-3 px-3">
                                    <input type="number" step="0.01" :name="'items[' + index + '][unit_price]'"
                                           x-model.number="item.unit_price"
                                           @input="recomputeTotals()"
                                           min="0" required
                                           class="w-full px-2 py-1.5 text-xs text-right font-mono font-semibold rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white">
                                </td>

                                <td class="py-3 px-3">
                                    <input type="number" step="0.01" :name="'items[' + index + '][discount]'"
                                           x-model.number="item.discount"
                                           @input="recomputeTotals()"
                                           min="0"
                                           class="w-full px-2 py-1.5 text-xs text-right font-mono rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white">
                                </td>

                                <td class="py-3 px-3">
                                    <select :name="'items[' + index + '][gst_rate]'"
                                            x-model.number="item.gst_rate"
                                            @change="recomputeTotals()"
                                            class="w-full px-2 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900 bg-white">
                                        <option :value="5">5%</option>
                                        <option :value="12">12%</option>
                                        <option :value="18">18%</option>
                                        <option :value="28">28%</option>
                                        <option :value="0">0%</option>
                                    </select>
                                </td>

                                <td class="py-3 px-3 text-right font-mono font-medium text-slate-700" x-text="'₹' + itemTaxable(item).toFixed(2)"></td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-900" x-text="'₹' + itemTotal(item).toFixed(2)"></td>

                                <td class="py-3 px-3 text-center">
                                    <button type="button" @click="removeItem(index)"
                                            :disabled="items.length <= 1"
                                            :class="items.length <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:text-rose-600'"
                                            class="text-slate-400 p-1 rounded-md transition-colors">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="pt-2">
                <button type="button" @click="addItem()"
                        class="w-full py-2 border-2 border-dashed border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900 hover:bg-slate-50 transition-all flex items-center justify-center gap-1.5">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Another Product Line Item
                </button>
            </div>
        </div>

        <!-- 4. Extra Charges & Bank Details -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 space-y-6">
                <!-- Payment Status -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="size-7 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-xs flex items-center justify-center">₹</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Payment Status & Details</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="payment_status" class="block text-xs font-semibold text-slate-700 mb-1">
                                Payment Status <span class="text-rose-500">*</span>
                            </label>
                            <select name="payment_status" id="payment_status"
                                    x-model="paymentStatus"
                                    class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                                <option value="UNPAID">UNPAID (Pending Payment)</option>
                                <option value="PAID">PAID (Full Payment Received)</option>
                                <option value="PARTIALLY_PAID">PARTIALLY PAID (Advance Received)</option>
                                <option value="CANCELLED">CANCELLED</option>
                            </select>
                        </div>

                        <div>
                            <label for="paid_amount" class="block text-xs font-semibold text-slate-700 mb-1">
                                Paid Amount (₹)
                            </label>
                            <input type="number" step="0.01" name="paid_amount" id="paid_amount"
                                   x-model.number="paidAmount"
                                   class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>

                        <div>
                            <label for="payment_method" class="block text-xs font-semibold text-slate-700 mb-1">
                                Payment Method
                            </label>
                            <select name="payment_method" id="payment_method"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                                <option value="BANK_TRANSFER" {{ old('payment_method', $invoice->payment_method) === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer (NEFT / RTGS / IMPS)</option>
                                <option value="UPI" {{ old('payment_method', $invoice->payment_method) === 'UPI' ? 'selected' : '' }}>UPI / QR Code</option>
                                <option value="CASH" {{ old('payment_method', $invoice->payment_method) === 'CASH' ? 'selected' : '' }}>Cash</option>
                                <option value="CHEQUE" {{ old('payment_method', $invoice->payment_method) === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                                <option value="ONLINE" {{ old('payment_method', $invoice->payment_method) === 'ONLINE' ? 'selected' : '' }}>Online Gateway</option>
                            </select>
                        </div>

                        <div>
                            <label for="payment_reference" class="block text-xs font-semibold text-slate-700 mb-1">
                                Reference / UTR Number
                            </label>
                            <input type="text" name="payment_reference" id="payment_reference"
                                   value="{{ old('payment_reference', $invoice->payment_reference) }}"
                                   placeholder="e.g. UTR / Transaction ID"
                                   class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="size-7 rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs flex items-center justify-center">🏦</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Dhansree Remittance Bank Details</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="bank_name" class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $invoice->bank_name) }}"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="bank_account_name" class="block text-xs font-semibold text-slate-700 mb-1">Account Holder Name</label>
                            <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $invoice->bank_account_name) }}"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="bank_account_number" class="block text-xs font-semibold text-slate-700 mb-1">Account Number</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $invoice->bank_account_number) }}"
                                   class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="bank_ifsc_code" class="block text-xs font-semibold text-slate-700 mb-1">IFSC Code</label>
                            <input type="text" name="bank_ifsc_code" id="bank_ifsc_code" value="{{ old('bank_ifsc_code', $invoice->bank_ifsc_code) }}"
                                   class="w-full px-3 py-2 text-xs font-mono uppercase rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="bank_branch" class="block text-xs font-semibold text-slate-700 mb-1">Branch</label>
                            <input type="text" name="bank_branch" id="bank_branch" value="{{ old('bank_branch', $invoice->bank_branch) }}"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="bank_upi_id" class="block text-xs font-semibold text-slate-700 mb-1">UPI ID</label>
                            <input type="text" name="bank_upi_id" id="bank_upi_id" value="{{ old('bank_upi_id', $invoice->bank_upi_id) }}"
                                   class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                        </div>
                    </div>
                </div>

                <!-- Terms & Disclaimer -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                    <div>
                        <label for="terms_and_conditions" class="block text-xs font-semibold text-slate-700 mb-1">Terms & Conditions</label>
                        <textarea name="terms_and_conditions" id="terms_and_conditions" rows="3"
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">{{ old('terms_and_conditions', $invoice->terms_and_conditions) }}</textarea>
                    </div>

                    <div>
                        <label for="customer_notes" class="block text-xs font-semibold text-slate-700 mb-1">Customer Notes</label>
                        <textarea name="customer_notes" id="customer_notes" rows="2"
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">{{ old('customer_notes', $invoice->customer_notes) }}</textarea>
                    </div>

                    <div>
                        <label for="disclaimer" class="block text-xs font-semibold text-slate-700 mb-1">
                            Invoice Footer Disclaimer
                        </label>
                        <input type="text" name="disclaimer" id="disclaimer"
                               value="{{ old('disclaimer', $invoice->disclaimer) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 bg-slate-50/50">
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5 sticky top-20">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Tax & Invoice Summary
                    </h3>

                    <div class="space-y-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <label for="shipping_charges" class="text-xs text-slate-600">Shipping / Freight (₹)</label>
                            <input type="number" step="0.01" name="shipping_charges" id="shipping_charges"
                                   x-model.number="shippingCharges"
                                   @input="recomputeTotals()"
                                   min="0"
                                   class="w-28 px-2 py-1 text-xs text-right font-mono rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900">
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label for="packaging_charges" class="text-xs text-slate-600">Packaging Charges (₹)</label>
                            <input type="number" step="0.01" name="packaging_charges" id="packaging_charges"
                                   x-model.number="packagingCharges"
                                   @input="recomputeTotals()"
                                   min="0"
                                   class="w-28 px-2 py-1 text-xs text-right font-mono rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900">
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label for="additional_discount" class="text-xs text-slate-600">Order Discount (₹)</label>
                            <input type="number" step="0.01" name="additional_discount" id="additional_discount"
                                   x-model.number="additionalDiscount"
                                   @input="recomputeTotals()"
                                   min="0"
                                   class="w-28 px-2 py-1 text-xs text-right font-mono rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900">
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label for="round_off" class="text-xs text-slate-600">Round Off Adjustment (₹)</label>
                            <input type="number" step="0.01" name="round_off" id="round_off"
                                   x-model.number="roundOff"
                                   class="w-28 px-2 py-1 text-xs text-right font-mono rounded-lg border border-slate-200 focus:outline-hidden focus:ring-1 focus:ring-slate-900">
                        </div>
                    </div>

                    <!-- Computed Breakdown -->
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Taxable Value:</span>
                            <span class="font-mono font-semibold text-slate-900" x-text="'₹' + totalTaxable().toFixed(2)"></span>
                        </div>

                        <template x-if="isIntraState()">
                            <div class="space-y-1.5 pt-1 border-t border-slate-100">
                                <div class="flex justify-between text-slate-600">
                                    <span>CGST (Central Tax):</span>
                                    <span class="font-mono font-semibold text-slate-900" x-text="'₹' + totalCgst().toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>SGST (State Tax):</span>
                                    <span class="font-mono font-semibold text-slate-900" x-text="'₹' + totalSgst().toFixed(2)"></span>
                                </div>
                            </div>
                        </template>

                        <template x-if="!isIntraState()">
                            <div class="pt-1 border-t border-slate-100">
                                <div class="flex justify-between text-slate-600">
                                    <span>IGST (Integrated Tax):</span>
                                    <span class="font-mono font-semibold text-slate-900" x-text="'₹' + totalIgst().toFixed(2)"></span>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-100">
                            <span>Total Tax (GST):</span>
                            <span class="font-mono font-semibold text-indigo-700" x-text="'₹' + totalTax().toFixed(2)"></span>
                        </div>

                        <div x-show="shippingCharges > 0" class="flex justify-between text-slate-600">
                            <span>Shipping / Freight:</span>
                            <span class="font-mono" x-text="'₹' + (shippingCharges || 0).toFixed(2)"></span>
                        </div>

                        <div x-show="packagingCharges > 0" class="flex justify-between text-slate-600">
                            <span>Packaging Charges:</span>
                            <span class="font-mono" x-text="'₹' + (packagingCharges || 0).toFixed(2)"></span>
                        </div>

                        <div x-show="additionalDiscount > 0" class="flex justify-between text-emerald-700">
                            <span>Discount:</span>
                            <span class="font-mono" x-text="'-₹' + (additionalDiscount || 0).toFixed(2)"></span>
                        </div>

                        <div class="pt-3 border-t-2 border-slate-900 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-slate-900">Grand Total:</span>
                            <span class="text-xl font-extrabold font-mono text-amber-700" x-text="'₹' + grandTotal().toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Hidden Submit Button -->
                    <button type="submit" x-ref="invoiceSubmitBtn" class="hidden"></button>

                    <!-- Visible Submit Button -->
                    <button type="button" @click="$refs.invoiceSubmitBtn.click()"
                            style="background-color: #0f172a !important; color: #ffffff !important;"
                            class="w-full py-3 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition-all text-center flex items-center justify-center gap-2">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Update Invoice
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function invoiceForm() {
    return {
        billingState: '{{ old('billing_state_code', $invoice->billing_state_code) }}',
        posState: '{{ old('place_of_supply_state_code', $invoice->place_of_supply_state_code) }}',
        customerGstin: '{{ old('customer_gstin', $invoice->customer_gstin) }}',
        customerPan: '{{ old('customer_pan', $invoice->customer_pan) }}',
        sameShipping: {{ old('shipping_same_as_billing', $invoice->shipping_same_as_billing ? '1' : '0') ? 'true' : 'false' }},
        paymentStatus: '{{ old('payment_status', $invoice->payment_status) }}',
        paidAmount: {{ old('paid_amount', $invoice->paid_amount_rupees) }},
        shippingCharges: {{ old('shipping_charges', $invoice->shipping_charges_rupees) }},
        packagingCharges: {{ old('packaging_charges', $invoice->packaging_charges_rupees) }},
        additionalDiscount: {{ old('additional_discount', $invoice->total_discount_rupees) }},
        roundOff: {{ old('round_off', $invoice->round_off_rupees) }},

        catalogProducts: @json($products),
        items: @json($initialItems),

        isIntraState() {
            return this.posState === '08';
        },

        onGstinInput() {
            if (this.customerGstin && this.customerGstin.length >= 2) {
                const code = this.customerGstin.substring(0, 2);
                if (/^\d{2}$/.test(code)) {
                    this.billingState = code;
                    this.posState = code;
                }
            }
            if (this.customerGstin && this.customerGstin.length >= 12) {
                this.customerPan = this.customerGstin.substring(2, 12).toUpperCase();
            }
        },

        addItem() {
            this.items.push({
                product_id: null,
                item_name: '',
                description: '',
                hsn_code: '6204',
                quantity: 1,
                unit: 'PCS',
                unit_price: 0,
                discount: 0,
                gst_rate: 5
            });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        pickProduct(index, productId) {
            if (!productId) return;
            const prod = this.catalogProducts.find(p => p.id == productId);
            if (prod) {
                this.items[index].product_id = prod.id;
                this.items[index].item_name = prod.name;
                this.items[index].unit_price = prod.price_paise / 100;
            }
        },

        itemTaxable(item) {
            const qty = Number(item.quantity) || 0;
            const price = Number(item.unit_price) || 0;
            const disc = Number(item.discount) || 0;
            return Math.max(0, (qty * price) - disc);
        },

        itemTax(item) {
            const taxable = this.itemTaxable(item);
            const rate = Number(item.gst_rate) || 0;
            return (taxable * rate) / 100;
        },

        itemTotal(item) {
            return this.itemTaxable(item) + this.itemTax(item);
        },

        totalTaxable() {
            return this.items.reduce((sum, item) => sum + this.itemTaxable(item), 0);
        },

        totalTax() {
            return this.items.reduce((sum, item) => sum + this.itemTax(item), 0);
        },

        totalCgst() {
            return this.isIntraState() ? this.totalTax() / 2 : 0;
        },

        totalSgst() {
            return this.isIntraState() ? this.totalTax() / 2 : 0;
        },

        totalIgst() {
            return !this.isIntraState() ? this.totalTax() : 0;
        },

        grandTotal() {
            const sub = this.totalTaxable() + this.totalTax();
            const ship = Number(this.shippingCharges) || 0;
            const pack = Number(this.packagingCharges) || 0;
            const disc = Number(this.additionalDiscount) || 0;
            const ro = Number(this.roundOff) || 0;
            return Math.max(0, sub + ship + pack - disc + ro);
        },

        recomputeTotals() {
            // Reactive Alpine update
        }
    };
}
</script>
@endsection
