<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $invoice->invoice_number }} - Dhansree Trends</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        @media screen {
            .invoice-sheet {
                max-width: 54rem;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 1rem;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
                border: 1px solid #e2e8f0;
                padding: 2rem 2.5rem;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 6mm 8mm 6mm 8mm;
            }

            html, body {
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #000000 !important;
                font-size: 9.5px !important;
                line-height: 1.25 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }

            /* Compact header in print */
            .print-header {
                padding-bottom: 6px !important;
                margin-bottom: 6px !important;
                border-bottom: 2px solid #0f172a !important;
            }
            .print-brand-title {
                font-size: 20px !important;
                line-height: 1.1 !important;
            }
            .print-header-text {
                font-size: 9px !important;
                line-height: 1.2 !important;
            }

            /* Compact address boxes */
            .print-address-grid {
                gap: 6px !important;
                padding-bottom: 6px !important;
                margin-bottom: 6px !important;
                border-bottom: 1px solid #cbd5e1 !important;
            }
            .print-address-box {
                padding: 5px 8px !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
                background-color: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 6px !important;
            }

            /* Compact items table */
            .print-table th {
                padding: 3px 5px !important;
                font-size: 8.5px !important;
                background-color: #f1f5f9 !important;
                border-color: #cbd5e1 !important;
            }
            .print-table td {
                padding: 3px 5px !important;
                font-size: 9px !important;
                line-height: 1.15 !important;
                border-color: #e2e8f0 !important;
            }
            .print-table tfoot td {
                padding: 3px 5px !important;
                font-size: 9px !important;
                background-color: #f8fafc !important;
            }
            .print-item-desc {
                font-size: 8px !important;
                line-height: 1.1 !important;
            }

            /* Compact HSN table */
            .print-hsn-wrap {
                margin-top: 4px !important;
                margin-bottom: 4px !important;
            }
            .print-hsn-table th {
                padding: 2px 4px !important;
                font-size: 8px !important;
            }
            .print-hsn-table td {
                padding: 2px 4px !important;
                font-size: 8.5px !important;
            }

            /* Compact Bottom Blocks */
            .print-summary-grid {
                gap: 8px !important;
                padding-top: 4px !important;
            }
            .print-box-sm {
                padding: 5px 8px !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
                border-radius: 6px !important;
            }
            .print-totals-row {
                padding-top: 1.5px !important;
                padding-bottom: 1.5px !important;
                font-size: 9px !important;
            }
            .print-grand-total {
                padding-top: 3px !important;
                padding-bottom: 2px !important;
                font-size: 11.5px !important;
                border-top: 2px solid #0f172a !important;
            }

            /* Compact Terms & Footer */
            .print-footer-grid {
                padding-top: 6px !important;
                margin-top: 6px !important;
                border-top: 1px solid #cbd5e1 !important;
                gap: 8px !important;
            }
            .print-terms-text {
                font-size: 8px !important;
                line-height: 1.2 !important;
            }
            .print-notice-box {
                padding: 3px 6px !important;
                font-size: 8.5px !important;
                border-radius: 4px !important;
            }
            .print-signatory-box {
                padding-top: 14px !important;
                width: 130px !important;
            }

            /* Prevent awkward splits inside individual components */
            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body class="p-3 sm:p-6">
    <!-- Top Floating Action Bar (Hidden when printing) -->
    <div class="no-print max-w-4xl mx-auto mb-5 flex flex-wrap items-center justify-between bg-white rounded-2xl p-3.5 shadow-sm border border-slate-200 gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.bulk-invoices.show', $invoice) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Invoice
            </a>
            <span class="text-xs text-slate-500">Official GST Tax Invoice: <strong class="text-slate-800 font-mono">{{ $invoice->invoice_number }}</strong></span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    style="background-color: #0f172a !important; color: #ffffff !important;"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold shadow-sm hover:opacity-90 transition-all cursor-pointer">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save PDF (Single Page)
            </button>
        </div>
    </div>

    <!-- Official A4 Tax Invoice Sheet -->
    <div class="invoice-sheet space-y-3.5">
        <!-- 1. Header: Dhansree Branding & Invoice Meta -->
        <div class="print-header flex flex-col sm:flex-row justify-between items-start gap-4 border-b-2 border-slate-900 pb-3">
            <!-- Dhansree Company Details -->
            <div class="space-y-0.5">
                <div class="flex items-baseline gap-2">
                    <h1 class="print-brand-title text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950 font-serif">
                        Dhansree Trends
                    </h1>
                    <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Wholesale</span>
                </div>
                <p class="text-xs font-bold text-slate-800">
                    {{ $invoice->seller_legal_name ?: 'Dhanshree Trends' }}
                </p>
                <div class="print-header-text text-[10.5px] text-slate-600 leading-snug space-y-0.5">
                    <p>{{ $invoice->seller_address ?: 'C-197 Murlipura Scheme' }}, {{ $invoice->seller_city ?: 'Jaipur' }}, {{ $invoice->seller_state_name ?: 'Rajasthan' }} - {{ $invoice->seller_postal_code ?: '302039' }}</p>
                    <p>
                        <strong class="text-slate-800">GSTIN:</strong> <span class="mono font-bold text-slate-900">{{ $invoice->seller_gstin ?: '08CCUPS2872J1ZO' }}</span>
                        &bull; <strong class="text-slate-800">PAN:</strong> <span class="mono text-slate-900">{{ $invoice->seller_pan ?: 'CCUPS2872J' }}</span>
                        &bull; <strong class="text-slate-800">State:</strong> {{ $invoice->seller_state_name ?: 'Rajasthan' }} (Code: <span class="mono font-semibold">{{ $invoice->seller_state_code ?: '08' }}</span>)
                    </p>
                    <p>Phone: {{ $invoice->seller_phone ?: '+919887776777' }} &bull; Email: {{ $invoice->seller_email ?: 'info@dhansree.com' }}</p>
                </div>
            </div>

            <!-- Tax Invoice Badge & Metadata -->
            <div class="text-left sm:text-right space-y-0.5 shrink-0">
                <div class="inline-block px-2.5 py-0.5 rounded bg-slate-900 text-white text-[11px] font-extrabold uppercase tracking-wider">
                    TAX INVOICE
                </div>
                <p class="text-[9px] text-slate-500 uppercase tracking-widest font-semibold">ORIGINAL FOR RECIPIENT</p>

                <div class="print-header-text mt-1 text-[10.5px] space-y-0.5 pt-0.5">
                    <p>
                        <span class="text-slate-500">Invoice No:</span>
                        <strong class="mono text-slate-900 font-bold text-xs">{{ $invoice->invoice_number }}</strong>
                    </p>
                    <p>
                        <span class="text-slate-500">Invoice Date:</span>
                        <strong class="text-slate-900">{{ $invoice->invoice_date->format('d-m-Y') }}</strong>
                    </p>
                    @if($invoice->due_date)
                        <p>
                            <span class="text-slate-500">Due Date:</span>
                            <strong class="text-slate-900">{{ $invoice->due_date->format('d-m-Y') }}</strong>
                        </p>
                    @endif
                    <p>
                        <span class="text-slate-500">Payment Terms:</span>
                        <strong class="text-slate-900">{{ $invoice->payment_terms ?: 'Due on Receipt' }}</strong>
                    </p>
                    <p>
                        <span class="text-slate-500">Reverse Charge:</span>
                        <strong class="text-slate-900">{{ $invoice->reverse_charge ? 'Yes' : 'No' }}</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. Details of Receiver (Billed To) & Consignee (Shipped To) -->
        <div class="print-address-grid grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs border-b border-slate-200 pb-2.5">
            <!-- Details of Receiver (Billed To) -->
            <div class="print-address-box p-2.5 rounded-lg bg-slate-50/80 border border-slate-200 space-y-0.5">
                <p class="font-bold uppercase tracking-wider text-slate-400 text-[9px]">Details of Receiver (Billed To):</p>
                <p class="font-bold text-slate-900 text-xs">
                    {{ $invoice->business_name ?: $invoice->customer_name }}
                </p>
                @if($invoice->business_name && $invoice->customer_name !== $invoice->business_name)
                    <p class="text-[10px] text-slate-600 font-medium">Attn: {{ $invoice->customer_name }}</p>
                @endif
                <p class="text-slate-600 text-[10.5px]">
                    {{ $invoice->billing_line1 }}@if($invoice->billing_line2), {{ $invoice->billing_line2 }}@endif
                </p>
                <p class="text-slate-600 text-[10.5px]">
                    {{ $invoice->billing_city }}, {{ $invoice->billing_state_name }} - {{ $invoice->billing_postal_code }}
                </p>
                <div class="pt-0.5 text-[10px] space-y-0.5">
                    @if($invoice->customer_gstin)
                        <p><strong class="text-slate-700">GSTIN:</strong> <span class="mono font-bold text-slate-900">{{ $invoice->customer_gstin }}</span></p>
                    @else
                        <p class="text-slate-400 italic">Unregistered Buyer (Retail / Consumer)</p>
                    @endif
                    @if($invoice->customer_pan)
                        <p><strong class="text-slate-700">PAN:</strong> <span class="mono text-slate-900">{{ $invoice->customer_pan }}</span></p>
                    @endif
                    <p><strong class="text-slate-700">State:</strong> {{ $invoice->billing_state_name }} (Code: <span class="mono font-semibold">{{ $invoice->billing_state_code }}</span>)</p>
                    @if($invoice->customer_phone)
                        <p><strong class="text-slate-700">Phone:</strong> {{ $invoice->customer_phone }} @if($invoice->customer_email)&bull; {{ $invoice->customer_email }}@endif</p>
                    @endif
                </div>
            </div>

            <!-- Details of Consignee (Shipped To) -->
            <div class="print-address-box p-2.5 rounded-lg bg-slate-50/80 border border-slate-200 space-y-0.5">
                <p class="font-bold uppercase tracking-wider text-slate-400 text-[9px]">Details of Consignee (Shipped To):</p>
                <p class="font-bold text-slate-900 text-xs">
                    {{ $invoice->shipping_recipient_name ?: ($invoice->business_name ?: $invoice->customer_name) }}
                </p>
                <p class="text-slate-600 text-[10.5px]">
                    {{ $invoice->shipping_line1 }}@if($invoice->shipping_line2), {{ $invoice->shipping_line2 }}@endif
                </p>
                <p class="text-slate-600 text-[10.5px]">
                    {{ $invoice->shipping_city }}, {{ $invoice->shipping_state_name }} - {{ $invoice->shipping_postal_code }}
                </p>
                <div class="pt-0.5 text-[10px] space-y-0.5">
                    <p>
                        <strong class="text-slate-700">Place of Supply:</strong>
                        <span class="font-semibold text-slate-900">{{ $invoice->place_of_supply_state_name }}</span>
                        (State Code: <span class="mono font-bold">{{ $invoice->place_of_supply_state_code }}</span>)
                    </p>
                    <p>
                        <strong class="text-slate-700">Nature of Supply:</strong>
                        <span class="font-semibold text-slate-900">{{ $invoice->supply_type === 'INTRA_STATE' ? 'Intra-State Supply (CGST + SGST)' : 'Inter-State Supply (IGST)' }}</span>
                    </p>
                    @if($invoice->customer_phone)
                        <p><strong class="text-slate-700">Contact:</strong> {{ $invoice->customer_phone }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Line Items Table -->
        <div class="overflow-x-auto">
            <table class="print-table w-full text-xs text-left border border-slate-300">
                <thead class="bg-slate-100 text-slate-800 font-bold uppercase text-[9.5px] tracking-wider border-b border-slate-300">
                    <tr>
                        <th class="p-1.5 text-center w-7 border-r border-slate-300">#</th>
                        <th class="p-1.5 border-r border-slate-300">Description of Goods</th>
                        <th class="p-1.5 text-center w-14 border-r border-slate-300">HSN</th>
                        <th class="p-1.5 text-center w-12 border-r border-slate-300">Qty</th>
                        <th class="p-1.5 text-center w-10 border-r border-slate-300">Unit</th>
                        <th class="p-1.5 text-right w-16 border-r border-slate-300">Rate (₹)</th>
                        <th class="p-1.5 text-right w-14 border-r border-slate-300">Disc (₹)</th>
                        <th class="p-1.5 text-right w-18 border-r border-slate-300">Taxable (₹)</th>
                        @if($invoice->supply_type === 'INTRA_STATE')
                            <th class="p-1.5 text-right w-16 border-r border-slate-300">CGST (₹)</th>
                            <th class="p-1.5 text-right w-16 border-r border-slate-300">SGST (₹)</th>
                        @else
                            <th class="p-1.5 text-right w-20 border-r border-slate-300">IGST (₹)</th>
                        @endif
                        <th class="p-1.5 text-right w-20">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($invoice->items as $idx => $item)
                        <tr class="align-top">
                            <td class="p-1.5 text-center mono text-slate-500 border-r border-slate-200">{{ $idx + 1 }}</td>
                            <td class="p-1.5 border-r border-slate-200">
                                <div class="font-bold text-slate-900 leading-tight">{{ $item->item_name }}</div>
                                @if($item->description)
                                    <div class="print-item-desc text-[9px] text-slate-500 mt-0.5 leading-tight">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="p-1.5 text-center mono text-slate-600 border-r border-slate-200">{{ $item->hsn_code }}</td>
                            <td class="p-1.5 text-center font-bold text-slate-800 border-r border-slate-200">{{ $item->quantity }}</td>
                            <td class="p-1.5 text-center text-slate-600 border-r border-slate-200">{{ $item->unit }}</td>
                            <td class="p-1.5 text-right mono border-r border-slate-200">{{ number_format($item->unit_price_rupees, 2) }}</td>
                            <td class="p-1.5 text-right mono border-r border-slate-200 text-slate-500">{{ number_format($item->discount_rupees, 2) }}</td>
                            <td class="p-1.5 text-right mono font-semibold border-r border-slate-200">{{ number_format($item->taxable_amount_rupees, 2) }}</td>

                            @if($invoice->supply_type === 'INTRA_STATE')
                                <td class="p-1.5 text-right mono border-r border-slate-200">
                                    {{ number_format($item->cgst_amount_rupees, 2) }}
                                    <span class="text-[8.5px] text-slate-400 block">{{ round($item->cgst_rate_bps / 100, 1) }}%</span>
                                </td>
                                <td class="p-1.5 text-right mono border-r border-slate-200">
                                    {{ number_format($item->sgst_amount_rupees, 2) }}
                                    <span class="text-[8.5px] text-slate-400 block">{{ round($item->sgst_rate_bps / 100, 1) }}%</span>
                                </td>
                            @else
                                <td class="p-1.5 text-right mono border-r border-slate-200">
                                    {{ number_format($item->igst_amount_rupees, 2) }}
                                    <span class="text-[8.5px] text-slate-400 block">{{ round($item->igst_rate_bps / 100, 1) }}%</span>
                                </td>
                            @endif

                            <td class="p-1.5 text-right mono font-bold text-slate-900">{{ number_format($item->line_total_rupees, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-300">
                    <tr>
                        <td colspan="3" class="p-1.5 text-right uppercase text-[9px] text-slate-600 border-r border-slate-300">Total Quantities & Subtotal:</td>
                        <td class="p-1.5 text-center font-bold text-slate-900 border-r border-slate-300">{{ $invoice->items->sum('quantity') }}</td>
                        <td colspan="3" class="p-1.5 border-r border-slate-300"></td>
                        <td class="p-1.5 text-right mono font-bold text-slate-900 border-r border-slate-300">₹{{ number_format($invoice->subtotal_taxable_rupees, 2) }}</td>
                        @if($invoice->supply_type === 'INTRA_STATE')
                            <td class="p-1.5 text-right mono font-bold text-slate-900 border-r border-slate-300">₹{{ number_format($invoice->cgst_rupees, 2) }}</td>
                            <td class="p-1.5 text-right mono font-bold text-slate-900 border-r border-slate-300">₹{{ number_format($invoice->sgst_rupees, 2) }}</td>
                        @else
                            <td class="p-1.5 text-right mono font-bold text-slate-900 border-r border-slate-300">₹{{ number_format($invoice->igst_rupees, 2) }}</td>
                        @endif
                        <td class="p-1.5 text-right mono font-bold text-slate-900">₹{{ number_format(($invoice->subtotal_taxable_rupees + $invoice->total_tax_rupees), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 4. HSN Tax Summary Table (Rule 46 Requirement) -->
        @if(!empty($hsnSummary))
            <div class="print-hsn-wrap avoid-break">
                <p class="font-bold uppercase tracking-wider text-slate-500 text-[9px] mb-0.5">GST Tax Summary (by HSN/SAC):</p>
                <table class="print-hsn-table w-full text-[10px] text-left border border-slate-200">
                    <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[8.5px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="p-1 border-r border-slate-200">HSN/SAC</th>
                            <th class="p-1 text-right border-r border-slate-200">Taxable Value (₹)</th>
                            @if($invoice->supply_type === 'INTRA_STATE')
                                <th class="p-1 text-right border-r border-slate-200">CGST Rate</th>
                                <th class="p-1 text-right border-r border-slate-200">CGST Amount (₹)</th>
                                <th class="p-1 text-right border-r border-slate-200">SGST Rate</th>
                                <th class="p-1 text-right border-r border-slate-200">SGST Amount (₹)</th>
                            @else
                                <th class="p-1 text-right border-r border-slate-200">IGST Rate</th>
                                <th class="p-1 text-right border-r border-slate-200">IGST Amount (₹)</th>
                            @endif
                            <th class="p-1 text-right">Total Tax (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($hsnSummary as $row)
                            <tr>
                                <td class="p-1 mono font-semibold border-r border-slate-200">{{ $row['hsn'] }}</td>
                                <td class="p-1 text-right mono border-r border-slate-200">{{ number_format($row['taxable_rupees'], 2) }}</td>
                                @if($invoice->supply_type === 'INTRA_STATE')
                                    <td class="p-1 text-right mono border-r border-slate-200">{{ $row['cgst_rate'] }}%</td>
                                    <td class="p-1 text-right mono border-r border-slate-200">{{ number_format($row['cgst_rupees'], 2) }}</td>
                                    <td class="p-1 text-right mono border-r border-slate-200">{{ $row['sgst_rate'] }}%</td>
                                    <td class="p-1 text-right mono border-r border-slate-200">{{ number_format($row['sgst_rupees'], 2) }}</td>
                                @else
                                    <td class="p-1 text-right mono border-r border-slate-200">{{ $row['igst_rate'] }}%</td>
                                    <td class="p-1 text-right mono border-r border-slate-200">{{ number_format($row['igst_rupees'], 2) }}</td>
                                @endif
                                <td class="p-1 text-right mono font-bold">{{ number_format($row['total_tax_rupees'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- 5. Amount in Words, Bank Remittance & Calculation Summary -->
        <div class="print-summary-grid grid grid-cols-1 sm:grid-cols-2 gap-4 pt-0.5 avoid-break">
            <!-- Left: Amount in Words & Bank Details -->
            <div class="space-y-2 text-xs">
                <!-- Amount in Words -->
                <div class="print-box-sm p-2 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                    <p class="font-bold uppercase tracking-wider text-slate-400 text-[8.5px]">Total Invoice Value (in words):</p>
                    <p class="font-bold text-slate-900 italic leading-snug text-[10.5px]">
                        {{ $invoice->amount_in_words }}
                    </p>
                </div>

                <!-- Bank Remittance Details -->
                <div class="print-box-sm p-2 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5 text-[10.5px]">
                    <p class="font-bold uppercase tracking-wider text-slate-700 text-[8.5px]">Bank Remittance / NEFT / RTGS Details:</p>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 pt-0.5 text-slate-600">
                        <p><span class="text-slate-400">Bank Name:</span> <strong>{{ $invoice->bank_name ?: 'Bandhan Bank' }}</strong></p>
                        <p><span class="text-slate-400">Branch:</span> <strong>{{ $invoice->bank_branch ?: 'Jaipur, Tonk Road' }}</strong></p>
                        <p><span class="text-slate-400">A/C Name:</span> <strong>{{ $invoice->bank_account_name ?: ($invoice->seller_legal_name ?: 'Dhanshree Trends') }}</strong></p>
                        <p><span class="text-slate-400">A/C No:</span> <strong class="mono text-slate-900">{{ $invoice->bank_account_number ?: '20100080581870' }}</strong></p>
                        <p><span class="text-slate-400">IFSC Code:</span> <strong class="mono text-slate-900">{{ $invoice->bank_ifsc_code ?: 'BDBL0001886' }}</strong></p>
                        @if($invoice->bank_upi_id)
                            <p><span class="text-slate-400">UPI ID:</span> <strong class="mono text-slate-900">{{ $invoice->bank_upi_id }}</strong></p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Subtotal & Tax Calculation -->
            <div class="text-xs space-y-1">
                <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-700">
                    <span>Taxable Subtotal:</span>
                    <span class="mono font-semibold text-slate-900">₹{{ number_format($invoice->subtotal_taxable_rupees, 2) }}</span>
                </div>

                @if($invoice->supply_type === 'INTRA_STATE')
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-700">
                        <span>Central Tax (CGST):</span>
                        <span class="mono font-semibold text-slate-900">₹{{ number_format($invoice->cgst_rupees, 2) }}</span>
                    </div>
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-700">
                        <span>State Tax (SGST):</span>
                        <span class="mono font-semibold text-slate-900">₹{{ number_format($invoice->sgst_rupees, 2) }}</span>
                    </div>
                @else
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-700">
                        <span>Integrated Tax (IGST):</span>
                        <span class="mono font-semibold text-slate-900">₹{{ number_format($invoice->igst_rupees, 2) }}</span>
                    </div>
                @endif

                @if($invoice->shipping_charges_paise > 0)
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-700">
                        <span>Freight / Shipping Charges:</span>
                        <span class="mono font-semibold text-slate-900">₹{{ number_format($invoice->shipping_charges_rupees, 2) }}</span>
                    </div>
                @endif

                @if($invoice->packaging_charges_paise > 0)
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-700">
                        <span>Packaging & Handling:</span>
                        <span class="mono font-semibold text-slate-900">₹{{ number_format($invoice->packaging_charges_rupees, 2) }}</span>
                    </div>
                @endif

                @if($invoice->total_discount_paise > 0)
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-emerald-700">
                        <span>Additional Discount:</span>
                        <span class="mono font-semibold">-₹{{ number_format($invoice->total_discount_rupees, 2) }}</span>
                    </div>
                @endif

                @if($invoice->round_off_paise != 0)
                    <div class="print-totals-row flex justify-between py-0.5 border-b border-slate-100 text-slate-600">
                        <span>Round Off:</span>
                        <span class="mono">{{ $invoice->round_off_rupees >= 0 ? '+' : '' }}₹{{ number_format($invoice->round_off_rupees, 2) }}</span>
                    </div>
                @endif

                <!-- Grand Total -->
                <div class="print-grand-total flex justify-between py-1.5 border-t-2 border-slate-950 text-xs font-bold text-slate-900">
                    <span class="text-slate-950">Grand Total (INR):</span>
                    <span class="mono text-sm sm:text-base font-extrabold text-slate-950">₹{{ number_format($invoice->grand_total_rupees, 2) }}</span>
                </div>

                @if($invoice->paid_amount_paise > 0)
                    <div class="print-totals-row flex justify-between py-0.5 border-t border-slate-200 text-slate-600 text-[10px]">
                        <span>Amount Paid:</span>
                        <span class="mono font-semibold text-emerald-700">₹{{ number_format($invoice->paid_amount_rupees, 2) }}</span>
                    </div>
                    <div class="print-totals-row flex justify-between py-0.5 text-slate-900 font-bold text-[11px]">
                        <span>Balance Due:</span>
                        <span class="mono {{ $invoice->balance_due_rupees > 0 ? 'text-rose-600' : 'text-slate-900' }}">₹{{ number_format($invoice->balance_due_rupees, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- 6. Terms, Notes & SYSTEM GENERATED SIGNATURE DISCLAIMER -->
        <div class="print-footer-grid pt-2.5 border-t border-slate-300 grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs avoid-break">
            <!-- Left: Terms & Conditions (8 cols) -->
            <div class="sm:col-span-8 space-y-1 text-slate-600">
                <p class="font-bold uppercase tracking-wider text-slate-700 text-[8.5px]">Terms & Conditions:</p>
                <p class="print-terms-text text-[9px] leading-tight whitespace-pre-line text-slate-500">{{ $invoice->terms_and_conditions }}</p>

                <!-- System Generated Disclaimer requested by user -->
                <div class="pt-1">
                    <div class="print-notice-box p-1.5 rounded-md bg-slate-100 border border-slate-200 text-slate-800 text-[9.5px] font-semibold flex items-center gap-1.5">
                        <svg class="size-3.5 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>{{ $invoice->disclaimer ?: 'This is a system generated invoice and does not require a physical signature.' }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Authorized Signatory Block (4 cols) -->
            <div class="sm:col-span-4 flex flex-col justify-between items-end text-right">
                <div class="space-y-0.5">
                    <p class="text-[9px] text-slate-400 uppercase font-semibold">For & On Behalf of:</p>
                    <p class="font-bold text-slate-900 text-[11px]">
                        {{ $invoice->seller_legal_name ?: 'Dhanshree Trends' }}
                    </p>
                </div>

                <div class="print-signatory-box pt-4 w-36 text-center">
                    <div class="border-t border-slate-400 pt-0.5 text-[9px] text-slate-500 font-medium">
                        Authorized Signatory
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="pt-1 border-t border-slate-200 text-center text-[9px] text-slate-400 no-print">
            Thank you for doing business with Dhansree Trends &bull; <a href="https://dhansree.com" class="hover:underline">www.dhansree.com</a>
        </div>
    </div>
</body>
</html>
