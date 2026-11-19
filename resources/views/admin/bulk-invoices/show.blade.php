@extends('layouts.admin')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page_title', 'Bulk Invoice Details')

@section('content')
<div x-data="{ paymentModalOpen: false }" class="space-y-6">
    <!-- Header with Breadcrumbs & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.bulk-invoices.index') }}" class="hover:text-slate-800">Bulk Invoices</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">{{ $invoice->invoice_number }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 font-mono">{{ $invoice->invoice_number }}</h1>
                @if($invoice->payment_status === 'PAID')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                        PAID
                    </span>
                @elseif($invoice->payment_status === 'PARTIALLY_PAID')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                        PARTIALLY PAID
                    </span>
                @elseif($invoice->payment_status === 'CANCELLED')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        CANCELLED
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                        UNPAID
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Back -->
            <a href="{{ route('admin.bulk-invoices.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                &larr; Back
            </a>

            <!-- Update Payment -->
            <button type="button" @click="paymentModalOpen = true"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-xs">
                <svg class="size-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Record Payment
            </button>

            <!-- Edit -->
            <a href="{{ route('admin.bulk-invoices.edit', $invoice) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-800 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-xs">
                <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                Edit
            </a>

            <!-- Print / PDF -->
            <a href="{{ route('admin.bulk-invoices.invoice', $invoice) }}" target="_blank"
               style="background-color: #0f172a !important; color: #ffffff !important;"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold shadow-sm hover:opacity-90 transition-all">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.656 1.002-3.079 2.68-3.079h5.2a2.85 2.85 0 0 1 2.68 3.079M6.75 6.75h10.5a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 16.5v-7.5a2.25 2.25 0 0 1 2.25-2.25Zm0 0v-1.5A2.25 2.25 0 0 1 9 3h6a2.25 2.25 0 0 1 2.25 2.25v1.5m-10.5 9h10.5" />
                </svg>
                Print Tax Invoice (A4)
            </a>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Parties, Items, Notes (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Party Addresses Grid -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">
                    Parties to the Transaction
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                    <!-- Seller (Dhansree) -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5">
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Seller (Supplier):</p>
                        <p class="font-bold text-slate-900 text-sm">{{ $invoice->seller_name }}</p>
                        <p class="text-[11px] text-slate-500 font-medium">{{ $invoice->seller_legal_name }}</p>
                        <p class="text-slate-600">{{ $invoice->seller_address }}</p>
                        <p class="text-slate-600">{{ $invoice->seller_city }}, {{ $invoice->seller_state_name }} - {{ $invoice->seller_postal_code }}</p>
                        <div class="pt-1.5 text-[11px] text-slate-700 space-y-0.5">
                            <p><strong>GSTIN:</strong> <span class="font-mono font-bold">{{ $invoice->seller_gstin }}</span></p>
                            <p><strong>State:</strong> {{ $invoice->seller_state_name }} (Code {{ $invoice->seller_state_code }})</p>
                            <p><strong>Contact:</strong> {{ $invoice->seller_phone }} &bull; {{ $invoice->seller_email }}</p>
                        </div>
                    </div>

                    <!-- Buyer (Billed To) -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5">
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Buyer (Billed To):</p>
                        <p class="font-bold text-slate-900 text-sm">{{ $invoice->business_name ?: $invoice->customer_name }}</p>
                        @if($invoice->business_name && $invoice->customer_name !== $invoice->business_name)
                            <p class="text-[11px] text-slate-500 font-medium">Attn: {{ $invoice->customer_name }}</p>
                        @endif
                        <p class="text-slate-600">{{ $invoice->billing_line1 }}@if($invoice->billing_line2), {{ $invoice->billing_line2 }}@endif</p>
                        <p class="text-slate-600">{{ $invoice->billing_city }}, {{ $invoice->billing_state_name }} - {{ $invoice->billing_postal_code }}</p>
                        <div class="pt-1.5 text-[11px] text-slate-700 space-y-0.5">
                            @if($invoice->customer_gstin)
                                <p><strong>GSTIN:</strong> <span class="font-mono font-bold text-indigo-700">{{ $invoice->customer_gstin }}</span></p>
                            @else
                                <p class="text-slate-500 italic">Unregistered Buyer (Retail / Consumer)</p>
                            @endif
                            <p><strong>Place of Supply:</strong> {{ $invoice->place_of_supply_state_name }} (Code {{ $invoice->place_of_supply_state_code }})</p>
                            @if($invoice->customer_phone)
                                <p><strong>Contact:</strong> {{ $invoice->customer_phone }} @if($invoice->customer_email)&bull; {{ $invoice->customer_email }}@endif</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Consignee if different -->
                @if(!$invoice->shipping_same_as_billing)
                    <div class="p-4 rounded-xl bg-amber-50/50 border border-amber-200/60 text-xs space-y-1">
                        <p class="font-bold uppercase tracking-wider text-amber-700 text-[10px]">Shipped To (Consignee):</p>
                        <p class="font-bold text-slate-900">{{ $invoice->shipping_recipient_name }}</p>
                        <p class="text-slate-600">{{ $invoice->shipping_line1 }}@if($invoice->shipping_line2), {{ $invoice->shipping_line2 }}@endif, {{ $invoice->shipping_city }}, {{ $invoice->shipping_state_name }} - {{ $invoice->shipping_postal_code }}</p>
                    </div>
                @endif
            </div>

            <!-- Line Items Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Line Items ({{ $invoice->items->count() }})
                    </h2>
                    <span class="text-[11px] font-semibold text-slate-500">
                        Supply: {{ $invoice->supply_type === 'INTRA_STATE' ? 'Intra-State (CGST + SGST)' : 'Inter-State (IGST)' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 text-center w-8">#</th>
                                <th class="py-2.5 px-3">Item & Description</th>
                                <th class="py-2.5 px-3 text-center">HSN</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Price (₹)</th>
                                <th class="py-2.5 px-3 text-right">Taxable (₹)</th>
                                @if($invoice->supply_type === 'INTRA_STATE')
                                    <th class="py-2.5 px-3 text-right">CGST</th>
                                    <th class="py-2.5 px-3 text-right">SGST</th>
                                @else
                                    <th class="py-2.5 px-3 text-right">IGST</th>
                                @endif
                                <th class="py-2.5 px-3 text-right">Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoice->items as $idx => $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-slate-900">{{ $item->item_name }}</div>
                                        @if($item->description)
                                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $item->description }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono text-slate-600">{{ $item->hsn_code }}</td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-800">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="py-3 px-3 text-right font-mono">₹{{ number_format($item->unit_price_rupees, 2) }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-medium text-slate-800">₹{{ number_format($item->taxable_amount_rupees, 2) }}</td>
                                    @if($invoice->supply_type === 'INTRA_STATE')
                                        <td class="py-3 px-3 text-right font-mono text-slate-600">
                                            ₹{{ number_format($item->cgst_amount_rupees, 2) }}
                                            <span class="text-[9px] text-slate-400 block">{{ round($item->cgst_rate_bps / 100, 1) }}%</span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono text-slate-600">
                                            ₹{{ number_format($item->sgst_amount_rupees, 2) }}
                                            <span class="text-[9px] text-slate-400 block">{{ round($item->sgst_rate_bps / 100, 1) }}%</span>
                                        </td>
                                    @else
                                        <td class="py-3 px-3 text-right font-mono text-slate-600">
                                            ₹{{ number_format($item->igst_amount_rupees, 2) }}
                                            <span class="text-[9px] text-slate-400 block">{{ round($item->igst_rate_bps / 100, 1) }}%</span>
                                        </td>
                                    @endif
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">₹{{ number_format($item->line_total_rupees, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes, Terms & Disclaimer -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">
                    Terms & Declarations
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1">Terms & Conditions:</p>
                        <p class="text-slate-600 whitespace-pre-line bg-slate-50 p-3 rounded-xl border border-slate-100 font-mono text-[11px]">{{ $invoice->terms_and_conditions }}</p>
                    </div>

                    <div class="space-y-3">
                        @if($invoice->customer_notes)
                            <div>
                                <p class="font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1">Customer Notes:</p>
                                <p class="text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 text-[11px]">{{ $invoice->customer_notes }}</p>
                            </div>
                        @endif

                        <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/70 text-slate-700">
                            <p class="font-bold uppercase tracking-wider text-amber-800 text-[10px] mb-0.5">System Notice:</p>
                            <p class="italic font-medium text-[11px]">{{ $invoice->disclaimer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Summary & Payment Details (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Totals Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">
                    Invoice Value
                </h2>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Taxable Value:</span>
                        <span class="font-mono font-semibold text-slate-900">₹{{ number_format($invoice->subtotal_taxable_rupees, 2) }}</span>
                    </div>

                    @if($invoice->supply_type === 'INTRA_STATE')
                        <div class="flex justify-between text-slate-600">
                            <span>CGST:</span>
                            <span class="font-mono font-semibold text-slate-900">₹{{ number_format($invoice->cgst_rupees, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>SGST:</span>
                            <span class="font-mono font-semibold text-slate-900">₹{{ number_format($invoice->sgst_rupees, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between text-slate-600">
                            <span>IGST:</span>
                            <span class="font-mono font-semibold text-slate-900">₹{{ number_format($invoice->igst_rupees, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-100">
                        <span>Total GST:</span>
                        <span class="font-mono font-bold text-indigo-700">₹{{ number_format($invoice->total_tax_rupees, 2) }}</span>
                    </div>

                    @if($invoice->shipping_charges_paise > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Shipping / Freight:</span>
                            <span class="font-mono font-semibold text-slate-900">₹{{ number_format($invoice->shipping_charges_rupees, 2) }}</span>
                        </div>
                    @endif

                    @if($invoice->packaging_charges_paise > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Packaging Charges:</span>
                            <span class="font-mono font-semibold text-slate-900">₹{{ number_format($invoice->packaging_charges_rupees, 2) }}</span>
                        </div>
                    @endif

                    @if($invoice->total_discount_paise > 0)
                        <div class="flex justify-between text-emerald-700">
                            <span>Discount:</span>
                            <span class="font-mono font-semibold">-₹{{ number_format($invoice->total_discount_rupees, 2) }}</span>
                        </div>
                    @endif

                    @if($invoice->round_off_paise != 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Round Off:</span>
                            <span class="font-mono">{{ $invoice->round_off_rupees >= 0 ? '+' : '' }}₹{{ number_format($invoice->round_off_rupees, 2) }}</span>
                        </div>
                    @endif

                    <div class="pt-3 border-t-2 border-slate-900 flex justify-between items-baseline">
                        <span class="text-sm font-bold text-slate-900">Grand Total:</span>
                        <span class="text-xl font-extrabold font-mono text-amber-700">₹{{ number_format($invoice->grand_total_rupees, 2) }}</span>
                    </div>
                </div>

                <!-- In Words -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 space-y-0.5">
                    <p class="font-bold uppercase tracking-wider text-slate-400 text-[9px]">Amount in Words:</p>
                    <p class="font-semibold text-slate-900 italic">{{ $invoice->amount_in_words }}</p>
                </div>
            </div>

            <!-- Payment Status & Balance Due Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Payment Overview
                    </h2>
                    <button type="button" @click="paymentModalOpen = true" class="text-xs text-amber-700 font-semibold hover:underline">
                        Edit
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Status:</span>
                        <span class="font-bold text-slate-900">{{ $invoice->payment_status }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Paid Amount:</span>
                        <span class="font-mono font-bold text-emerald-700">₹{{ number_format($invoice->paid_amount_rupees, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Balance Due:</span>
                        <span class="font-mono font-bold {{ $invoice->balance_due_rupees > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                            ₹{{ number_format($invoice->balance_due_rupees, 2) }}
                        </span>
                    </div>

                    @if($invoice->payment_method)
                        <div class="flex justify-between items-center text-[11px] text-slate-500">
                            <span>Method:</span>
                            <span class="font-medium text-slate-700">{{ $invoice->payment_method }}</span>
                        </div>
                    @endif

                    @if($invoice->payment_reference)
                        <div class="flex justify-between items-center text-[11px] text-slate-500">
                            <span>Reference / UTR:</span>
                            <span class="font-mono font-medium text-slate-800">{{ $invoice->payment_reference }}</span>
                        </div>
                    @endif

                    @if($invoice->paid_at)
                        <div class="flex justify-between items-center text-[11px] text-slate-500">
                            <span>Paid On:</span>
                            <span>{{ $invoice->paid_at->format('d M Y, h:i A') }}</span>
                        </div>
                    @endif
                </div>

                <button type="button" @click="paymentModalOpen = true"
                        class="w-full py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 transition-colors text-center">
                    Update Payment Status
                </button>
            </div>

            <!-- Remittance Bank Details -->
            @if($invoice->bank_name)
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">
                        Dhansree Bank Details (On Invoice)
                    </h2>
                    <div class="text-xs space-y-1.5 text-slate-600">
                        <p><span class="text-slate-400">Bank:</span> <strong class="text-slate-800">{{ $invoice->bank_name }}</strong></p>
                        <p><span class="text-slate-400">A/C Name:</span> <strong class="text-slate-800">{{ $invoice->bank_account_name }}</strong></p>
                        <p><span class="text-slate-400">A/C Number:</span> <span class="font-mono font-bold text-slate-900">{{ $invoice->bank_account_number }}</span></p>
                        <p><span class="text-slate-400">IFSC Code:</span> <span class="font-mono font-bold text-slate-900">{{ $invoice->bank_ifsc_code }}</span></p>
                        @if($invoice->bank_upi_id)
                            <p><span class="text-slate-400">UPI ID:</span> <span class="font-mono font-bold text-slate-900">{{ $invoice->bank_upi_id }}</span></p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Invoice Meta info -->
            <div class="p-4 rounded-xl bg-slate-50 text-[11px] text-slate-500 space-y-1 border border-slate-200/60">
                <p>Created: <strong>{{ $invoice->created_at->format('d M Y, h:i A') }}</strong></p>
                @if($invoice->createdBy)
                    <p>Created by: <strong>{{ $invoice->createdBy->name }}</strong></p>
                @endif
                <p>Terms: <strong>{{ $invoice->payment_terms ?: 'Due on receipt' }}</strong></p>
            </div>
        </div>
    </div>

    <!-- Payment Update Modal -->
    <div x-show="paymentModalOpen" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="paymentModalOpen = false"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">Update Payment - {{ $invoice->invoice_number }}</h3>
                        <button type="button" @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.bulk-invoices.payment', $invoice) }}" class="space-y-4 text-xs">
                        @csrf

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payment Status</label>
                            <select name="payment_status" required
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900">
                                <option value="PAID" {{ $invoice->payment_status === 'PAID' ? 'selected' : '' }}>PAID (Full payment cleared)</option>
                                <option value="PARTIALLY_PAID" {{ $invoice->payment_status === 'PARTIALLY_PAID' ? 'selected' : '' }}>PARTIALLY PAID (Partial advance)</option>
                                <option value="UNPAID" {{ $invoice->payment_status === 'UNPAID' ? 'selected' : '' }}>UNPAID (Pending)</option>
                                <option value="CANCELLED" {{ $invoice->payment_status === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Amount Paid (₹)</label>
                            <input type="number" step="0.01" name="paid_amount"
                                   value="{{ $invoice->paid_amount_rupees ?: $invoice->grand_total_rupees }}"
                                   class="w-full px-3 py-2 font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900">
                            <p class="text-[10px] text-slate-400 mt-1">Invoice grand total is ₹{{ number_format($invoice->grand_total_rupees, 2) }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                            <select name="payment_method"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900">
                                <option value="BANK_TRANSFER" {{ $invoice->payment_method === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer (NEFT / RTGS / IMPS)</option>
                                <option value="UPI" {{ $invoice->payment_method === 'UPI' ? 'selected' : '' }}>UPI / QR Code</option>
                                <option value="CASH" {{ $invoice->payment_method === 'CASH' ? 'selected' : '' }}>Cash</option>
                                <option value="CHEQUE" {{ $invoice->payment_method === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                                <option value="ONLINE" {{ $invoice->payment_method === 'ONLINE' ? 'selected' : '' }}>Online Gateway</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">UTR / Bank Reference / Receipt #</label>
                            <input type="text" name="payment_reference" value="{{ $invoice->payment_reference }}"
                                   placeholder="e.g. UTR / Transaction number"
                                   class="w-full px-3 py-2 font-mono rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="paymentModalOpen = false"
                                    class="px-4 py-2 rounded-xl font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
                                Cancel
                            </button>
                            <button type="submit"
                                    style="background-color: #0f172a !important; color: #ffffff !important;"
                                    class="px-5 py-2 rounded-xl font-bold shadow-sm hover:opacity-90">
                                Save Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
