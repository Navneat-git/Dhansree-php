<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $order->invoice_number ?? $order->order_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
        }
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                font-size: 11px;
            }
            .no-print {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8">
    <!-- Top Floating Action Bar -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white rounded-xl p-4 shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Order
            </a>
            <span class="text-xs text-slate-500">Tax Invoice for <strong>{{ $order->order_number }}</strong></span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Download PDF
            </button>
        </div>
    </div>

    <!-- Invoice Sheet (A4 Dimensions Container) -->
    <div class="invoice-card max-w-4xl mx-auto bg-white rounded-2xl shadow-md border border-slate-200 p-8 sm:p-12 space-y-6">
        <!-- 1. Header: Brand, Legal & Title -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 font-serif">
                    Dhansree<span class="text-amber-600">.</span>
                </h1>
                <div class="text-xs text-slate-600 mt-2 space-y-0.5">
                    <p class="font-bold text-slate-800">{{ $store->legal_name ?? 'Dhansree Retail Private Limited' }}</p>
                    <p>{{ $store->address_line1 ?? 'G-12, RIICO Industrial Area' }}@if($store->address_line2), {{ $store->address_line2 }}@endif</p>
                    <p>{{ $store->city ?? 'Jaipur' }}, {{ $storeState }} - {{ $store->postal_code ?? '302001' }}</p>
                    <p><strong class="text-slate-800">GSTIN:</strong> <span class="font-mono">{{ $store->gstin ?? '08AAAAA0000A1Z5' }}</span> | <strong class="text-slate-800">State Code:</strong> {{ $store->state_code ?? '08' }}</p>
                    <p>Email: {{ $store->support_email ?? 'care@dhansree.com' }} | Phone: {{ $store->support_phone ?? '+91 98765 43210' }}</p>
                </div>
            </div>

            <div class="text-left sm:text-right">
                <span class="inline-block px-3 py-1 rounded bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold uppercase tracking-wider mb-2">
                    TAX INVOICE
                </span>
                <p class="text-[11px] text-slate-500 italic">Original for Recipient</p>
                <div class="mt-3 text-xs space-y-1">
                    <p><span class="text-slate-500">Invoice No:</span> <strong class="font-mono text-slate-900">{{ $order->invoice_number ?? ('INV-' . substr($order->order_number, 3)) }}</strong></p>
                    <p><span class="text-slate-500">Invoice Date:</span> <strong class="text-slate-900">{{ ($order->invoice_issued_at ?? $order->placed_at)->timezone('Asia/Kolkata')->format('d-m-Y') }}</strong></p>
                    <p><span class="text-slate-500">Order Ref:</span> <strong class="font-mono text-slate-900">{{ $order->order_number }}</strong></p>
                    <p><span class="text-slate-500">Order Date:</span> <strong class="text-slate-900">{{ $order->placed_at->timezone('Asia/Kolkata')->format('d-m-Y') }}</strong></p>
                    <p><span class="text-slate-500">Reverse Charge:</span> <strong class="text-slate-900">No</strong></p>
                </div>
            </div>
        </div>

        <!-- 2. Billing & Shipping Address Blocks -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs border-b border-slate-200 pb-6">
            <!-- Billed To -->
            <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/60 space-y-1">
                <p class="font-bold uppercase tracking-wider text-slate-500 text-[10px] mb-1">Details of Receiver (Billed To):</p>
                <p class="font-bold text-slate-900 text-sm">{{ $order->billingAddress?->recipient_name ?? $order->customer_name }}</p>
                <p class="text-slate-600">{{ $order->billingAddress?->line1 ?? $order->shippingAddress?->line1 }}</p>
                @if($order->billingAddress?->line2)
                    <p class="text-slate-600">{{ $order->billingAddress->line2 }}</p>
                @endif
                <p class="text-slate-600">{{ $order->billingAddress?->city ?? $order->shippingAddress?->city }}, PIN: {{ $order->billingAddress?->postal_code ?? $order->shippingAddress?->postal_code }}</p>
                <p class="text-slate-700"><strong>State:</strong> {{ $placeOfSupplyState }} (Code: {{ $order->place_of_supply_state_code }})</p>
                <p class="text-slate-700"><strong>Contact:</strong> {{ $order->billingAddress?->phone ?? $order->customer_phone }}</p>
            </div>

            <!-- Shipped To -->
            <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/60 space-y-1">
                <p class="font-bold uppercase tracking-wider text-slate-500 text-[10px] mb-1">Details of Consignee (Shipped To):</p>
                <p class="font-bold text-slate-900 text-sm">{{ $order->shippingAddress?->recipient_name ?? $order->customer_name }}</p>
                <p class="text-slate-600">{{ $order->shippingAddress?->line1 }}</p>
                @if($order->shippingAddress?->line2)
                    <p class="text-slate-600">{{ $order->shippingAddress->line2 }}</p>
                @endif
                <p class="text-slate-600">{{ $order->shippingAddress?->city }}, PIN: {{ $order->shippingAddress?->postal_code }}</p>
                <p class="text-slate-700"><strong>Place of Supply:</strong> {{ $placeOfSupplyState }} (Code: {{ $order->place_of_supply_state_code }})</p>
                <p class="text-slate-700"><strong>Contact:</strong> {{ $order->shippingAddress?->phone ?? $order->customer_phone }}</p>
            </div>
        </div>

        <!-- 3. Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border border-slate-200">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-2.5 text-center w-10 border-r border-slate-200">#</th>
                        <th class="p-2.5 border-r border-slate-200">Description of Goods</th>
                        <th class="p-2.5 text-center border-r border-slate-200">HSN</th>
                        <th class="p-2.5 text-center border-r border-slate-200">Qty</th>
                        <th class="p-2.5 text-right border-r border-slate-200">Rate (₹)</th>
                        <th class="p-2.5 text-right border-r border-slate-200">Taxable (₹)</th>
                        @if($order->supply_type->value === 'INTRA_STATE')
                            <th class="p-2.5 text-right border-r border-slate-200">CGST (₹)</th>
                            <th class="p-2.5 text-right border-r border-slate-200">SGST (₹)</th>
                        @else
                            <th class="p-2.5 text-right border-r border-slate-200">IGST (₹)</th>
                        @endif
                        <th class="p-2.5 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($order->items as $idx => $item)
                        <tr>
                            <td class="p-2.5 text-center font-mono border-r border-slate-200 text-slate-500">{{ $idx + 1 }}</td>
                            <td class="p-2.5 border-r border-slate-200">
                                <div class="font-semibold text-slate-900">{{ $item->product_name }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                    @if($item->variant_title)
                                        <span>{{ $item->variant_title }}</span>
                                        <span>&bull;</span>
                                    @endif
                                    <span class="font-mono">SKU: {{ $item->sku }}</span>
                                </div>
                            </td>
                            <td class="p-2.5 text-center font-mono border-r border-slate-200 text-slate-600">{{ $item->hsn_code ?? '6204' }}</td>
                            <td class="p-2.5 text-center font-semibold border-r border-slate-200">{{ $item->quantity }}</td>
                            <td class="p-2.5 text-right font-mono border-r border-slate-200">{{ number_format($item->unit_price_paise / 100, 2) }}</td>
                            <td class="p-2.5 text-right font-mono border-r border-slate-200">{{ number_format($item->taxable_paise / 100, 2) }}</td>
                            @if($order->supply_type->value === 'INTRA_STATE')
                                <td class="p-2.5 text-right font-mono border-r border-slate-200">
                                    {{ number_format($item->cgst_paise / 100, 2) }}
                                    <div class="text-[9px] text-slate-400">{{ round($item->gst_rate_bps / 200, 1) }}%</div>
                                </td>
                                <td class="p-2.5 text-right font-mono border-r border-slate-200">
                                    {{ number_format($item->sgst_paise / 100, 2) }}
                                    <div class="text-[9px] text-slate-400">{{ round($item->gst_rate_bps / 200, 1) }}%</div>
                                </td>
                            @else
                                <td class="p-2.5 text-right font-mono border-r border-slate-200">
                                    {{ number_format($item->igst_paise / 100, 2) }}
                                    <div class="text-[9px] text-slate-400">{{ round($item->gst_rate_bps / 100, 1) }}%</div>
                                </td>
                            @endif
                            <td class="p-2.5 text-right font-mono font-bold text-slate-900">{{ number_format($item->line_total_paise / 100, 2) }}</td>
                        </tr>
                    @endforeach

                    <!-- Shipping Line Item if charged -->
                    @if($order->shipping_paise > 0)
                        <tr class="bg-slate-50/50">
                            <td class="p-2.5 text-center font-mono border-r border-slate-200 text-slate-500">{{ $order->items->count() + 1 }}</td>
                            <td class="p-2.5 border-r border-slate-200">
                                <div class="font-semibold text-slate-900">Freight & Shipping Charges</div>
                                <div class="text-[11px] text-slate-500">Composite supply with principal goods</div>
                            </td>
                            <td class="p-2.5 text-center font-mono border-r border-slate-200 text-slate-600">996812</td>
                            <td class="p-2.5 text-center font-semibold border-r border-slate-200">1</td>
                            <td class="p-2.5 text-right font-mono border-r border-slate-200">{{ number_format($order->shipping_paise / 100, 2) }}</td>
                            <td class="p-2.5 text-right font-mono border-r border-slate-200">{{ number_format($order->shipping_taxable_paise / 100, 2) }}</td>
                            @if($order->supply_type->value === 'INTRA_STATE')
                                <td class="p-2.5 text-right font-mono border-r border-slate-200">
                                    {{ number_format(($order->shipping_tax_paise / 2) / 100, 2) }}
                                    <div class="text-[9px] text-slate-400">{{ round($order->shipping_gst_rate_bps / 200, 1) }}%</div>
                                </td>
                                <td class="p-2.5 text-right font-mono border-r border-slate-200">
                                    {{ number_format(($order->shipping_tax_paise / 2) / 100, 2) }}
                                    <div class="text-[9px] text-slate-400">{{ round($order->shipping_gst_rate_bps / 200, 1) }}%</div>
                                </td>
                            @else
                                <td class="p-2.5 text-right font-mono border-r border-slate-200">
                                    {{ number_format($order->shipping_tax_paise / 100, 2) }}
                                    <div class="text-[9px] text-slate-400">{{ round($order->shipping_gst_rate_bps / 100, 1) }}%</div>
                                </td>
                            @endif
                            <td class="p-2.5 text-right font-mono font-bold text-slate-900">{{ number_format($order->shipping_paise / 100, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- 4. Calculation Summary & Amount in Words -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
            <!-- Left: Amount in Words & Notes -->
            <div class="space-y-4 text-xs">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                    <p class="font-bold uppercase tracking-wider text-slate-500 text-[10px]">Total Invoice Value (in words):</p>
                    <p class="font-bold text-slate-900 italic">
                        {{ \App\Support\Commerce\IndianCurrency::formatWords($order->total_paise) }}
                    </p>
                </div>

                <div class="text-[11px] text-slate-500 space-y-1">
                    <p class="font-bold text-slate-700">Payment Information:</p>
                    <p>Method: <strong>{{ $order->payment_method->value }}</strong> | Status: <strong>{{ $order->payment_status->value }}</strong></p>
                    @if($order->payment_method->value === 'ONLINE' && $order->payments->first())
                        <p>Gateway Ref: <span class="font-mono">{{ $order->payments->first()->provider_payment_id ?? $order->payments->first()->reference }}</span></p>
                    @endif
                </div>
            </div>

            <!-- Right: Subtotal Table -->
            <div class="text-xs space-y-2">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-600">Total Taxable Value:</span>
                    <span class="font-mono font-semibold text-slate-900">₹{{ number_format($order->taxable_paise / 100, 2) }}</span>
                </div>
                @if($order->discount_paise > 0)
                    <div class="flex justify-between py-1 border-b border-slate-100 text-emerald-700">
                        <span>Discount ({{ $order->coupon_code }}):</span>
                        <span class="font-mono font-semibold">-₹{{ number_format($order->discount_rupees, 2) }}</span>
                    </div>
                @endif
                @if($order->cgst_paise > 0)
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Total CGST:</span>
                        <span class="font-mono font-semibold text-slate-900">₹{{ number_format($order->cgst_paise / 100, 2) }}</span>
                    </div>
                @endif
                @if($order->sgst_paise > 0)
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Total SGST:</span>
                        <span class="font-mono font-semibold text-slate-900">₹{{ number_format($order->sgst_paise / 100, 2) }}</span>
                    </div>
                @endif
                @if($order->igst_paise > 0)
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Total IGST:</span>
                        <span class="font-mono font-semibold text-slate-900">₹{{ number_format($order->igst_paise / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between py-2 border-t-2 border-slate-900 text-sm font-bold text-slate-900">
                    <span>Grand Total:</span>
                    <span class="font-mono text-base text-amber-700">₹{{ number_format($order->total_rupees, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- 5. Declaration & Signature Block -->
        <div class="pt-6 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
            <div class="text-slate-500 space-y-1">
                <p class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Declaration:</p>
                <p>We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</p>
                <p class="text-[10px] text-slate-400 mt-2">Subject to Jaipur Jurisdiction. Computer generated invoice.</p>
            </div>
            <div class="text-right flex flex-col justify-between items-end">
                <p class="font-semibold text-slate-800 text-xs">For {{ $store->legal_name ?? 'Dhansree Retail Private Limited' }}</p>
                <div class="mt-8 border-t border-slate-400 pt-1 w-48 text-center text-[10px] text-slate-500">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>
