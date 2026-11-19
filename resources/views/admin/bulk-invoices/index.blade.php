@extends('layouts.admin')

@section('title', 'Bulk & B2B Invoices')
@section('page_title', 'Bulk & B2B Invoices')

@section('content')
<div class="space-y-6">
    <!-- Header with Breadcrumbs & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Bulk Invoices</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Bulk & Wholesale Deal Invoices</h1>
            <p class="text-xs text-slate-500 mt-0.5">Generate, manage, and print official GST-compliant tax invoices for wholesale customers and bulk orders.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.bulk-invoices.create') }}"
               style="background-color: #0f172a !important; color: #ffffff !important;"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm hover:opacity-90 transition-all">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Bulk Invoice
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Invoices -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Invoices</p>
                <span class="size-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ number_format($metrics['total_count']) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">All time generated</p>
        </div>

        <!-- Total B2B Sales -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total B2B Value</p>
                <span class="size-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($metrics['total_sales'], 2) }}</p>
            <p class="text-[11px] text-emerald-600 font-medium mt-1">Excluding cancelled</p>
        </div>

        <!-- GST Collected -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">GST Output Tax</p>
                <span class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                    GST
                </span>
            </div>
            <p class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($metrics['total_tax'], 2) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">CGST + SGST + IGST</p>
        </div>

        <!-- Outstanding Balance -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Outstanding Due</p>
                <span class="size-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold {{ $metrics['unpaid_due'] > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-2">₹{{ number_format($metrics['unpaid_due'], 2) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Unpaid / partial balances</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.bulk-invoices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- Search Query -->
            <div class="lg:col-span-4">
                <label for="q" class="block text-xs font-semibold text-slate-700 mb-1">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="Invoice #, Customer, GSTIN, Phone..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-slate-50/50">
                </div>
            </div>

            <!-- Payment Status -->
            <div class="lg:col-span-2">
                <label for="status" class="block text-xs font-semibold text-slate-700 mb-1">Payment Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-slate-50/50">
                    <option value="ALL">All Statuses</option>
                    <option value="UNPAID" {{ ($filters['status'] ?? '') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                    <option value="PAID" {{ ($filters['status'] ?? '') === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="PARTIALLY_PAID" {{ ($filters['status'] ?? '') === 'PARTIALLY_PAID' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="CANCELLED" {{ ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- From Date -->
            <div class="lg:col-span-2">
                <label for="from_date" class="block text-xs font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}"
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-slate-50/50">
            </div>

            <!-- To Date -->
            <div class="lg:col-span-2">
                <label for="to_date" class="block text-xs font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}"
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-slate-50/50">
            </div>

            <!-- Buttons -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit"
                        style="background-color: #0f172a !important; color: #ffffff !important;"
                        class="flex-1 px-4 py-2 text-xs font-bold rounded-xl shadow-xs hover:opacity-90 transition-all text-center">
                    Filter
                </button>
                @if(!empty(array_filter($filters ?? [])))
                    <a href="{{ route('admin.bulk-invoices.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Date / Due</th>
                        <th class="py-3 px-4">Customer & Business</th>
                        <th class="py-3 px-4">Place of Supply</th>
                        <th class="py-3 px-4 text-center">Items</th>
                        <th class="py-3 px-4 text-right">Taxable</th>
                        <th class="py-3 px-4 text-right">GST</th>
                        <th class="py-3 px-4 text-right">Grand Total</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Invoice # -->
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.bulk-invoices.show', $inv) }}" class="font-mono font-bold text-slate-900 hover:text-amber-600 hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                                @if($inv->payment_reference)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">Ref: {{ $inv->payment_reference }}</div>
                                @endif
                            </td>

                            <!-- Date / Due -->
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800">{{ $inv->invoice_date->format('d M Y') }}</div>
                                @if($inv->due_date)
                                    <div class="text-[10px] text-slate-400">Due: {{ $inv->due_date->format('d M Y') }}</div>
                                @else
                                    <div class="text-[10px] text-slate-400">{{ $inv->payment_terms ?: 'Due on receipt' }}</div>
                                @endif
                            </td>

                            <!-- Customer / Business -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $inv->business_name ?: $inv->customer_name }}
                                </div>
                                @if($inv->business_name && $inv->customer_name !== $inv->business_name)
                                    <div class="text-[11px] text-slate-500">Attn: {{ $inv->customer_name }}</div>
                                @endif
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if($inv->customer_gstin)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            GST: {{ $inv->customer_gstin }}
                                        </span>
                                    @endif
                                    @if($inv->customer_phone)
                                        <span class="text-[11px] text-slate-400">{{ $inv->customer_phone }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Place of Supply -->
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-700">{{ $inv->place_of_supply_state_name }}</div>
                                <div class="text-[10px] font-mono text-slate-400">
                                    Code {{ $inv->place_of_supply_state_code }} &bull; {{ $inv->supply_type === 'INTRA_STATE' ? 'Intra (CGST+SGST)' : 'Inter (IGST)' }}
                                </div>
                            </td>

                            <!-- Items count -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $inv->items_count }} {{ Str::plural('item', $inv->items_count) }}
                                </span>
                            </td>

                            <!-- Taxable -->
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700">
                                ₹{{ number_format($inv->subtotal_taxable_rupees, 2) }}
                            </td>

                            <!-- GST -->
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700">
                                ₹{{ number_format($inv->total_tax_rupees, 2) }}
                            </td>

                            <!-- Grand Total -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="font-mono font-bold text-slate-900 text-sm">
                                    ₹{{ number_format($inv->grand_total_rupees, 2) }}
                                </div>
                                @if($inv->payment_status === 'PARTIALLY_PAID')
                                    <div class="text-[10px] text-amber-600 font-mono">Paid ₹{{ number_format($inv->paid_amount_rupees, 2) }}</div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($inv->payment_status === 'PAID')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                        PAID
                                    </span>
                                @elseif($inv->payment_status === 'PARTIALLY_PAID')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                        PARTIAL
                                    </span>
                                @elseif($inv->payment_status === 'CANCELLED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        CANCELLED
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                                        UNPAID
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Print Tax Invoice -->
                                    <a href="{{ route('admin.bulk-invoices.invoice', $inv) }}" target="_blank"
                                       title="Print Tax Invoice"
                                       class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition-colors">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.656 1.002-3.079 2.68-3.079h5.2a2.85 2.85 0 0 1 2.68 3.079M6.75 6.75h10.5a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 16.5v-7.5a2.25 2.25 0 0 1 2.25-2.25Zm0 0v-1.5A2.25 2.25 0 0 1 9 3h6a2.25 2.25 0 0 1 2.25 2.25v1.5m-10.5 9h10.5" />
                                        </svg>
                                    </a>

                                    <!-- View Details -->
                                    <a href="{{ route('admin.bulk-invoices.show', $inv) }}"
                                       title="View Invoice"
                                       class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.bulk-invoices.edit', $inv) }}"
                                       title="Edit Invoice"
                                       class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </a>

                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('admin.bulk-invoices.destroy', $inv) }}"
                                          onsubmit="return confirm('Are you sure you want to delete Bulk Invoice {{ $inv->invoice_number }}?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Delete Invoice"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center">
                                <div class="max-w-sm mx-auto text-center space-y-3">
                                    <div class="size-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800">No Bulk Invoices Found</h3>
                                    <p class="text-xs text-slate-500">There are no wholesale invoices matching your filters. Create your first bulk deal invoice to get started.</p>
                                    <a href="{{ route('admin.bulk-invoices.create') }}"
                                       style="background-color: #0f172a !important; color: #ffffff !important;"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:opacity-90 transition-all">
                                        + Create Bulk Invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
