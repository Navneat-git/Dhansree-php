<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkInvoice;
use App\Models\BulkInvoiceItem;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Support\Commerce\IndianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AdminBulkInvoiceWebController extends Controller
{
    /**
     * Display a listing of bulk / wholesale B2B invoices.
     */
    public function index(Request $request): View
    {
        $query = BulkInvoice::withCount('items')->latest('invoice_date')->latest('id');

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('invoice_number', 'LIKE', "%{$q}%")
                    ->orWhere('customer_name', 'LIKE', "%{$q}%")
                    ->orWhere('business_name', 'LIKE', "%{$q}%")
                    ->orWhere('customer_gstin', 'LIKE', "%{$q}%")
                    ->orWhere('customer_phone', 'LIKE', "%{$q}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'ALL') {
            $query->where('payment_status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->input('to_date'));
        }

        // Summary metrics
        $allInvoices = BulkInvoice::all(['payment_status', 'grand_total_paise', 'total_tax_paise', 'paid_amount_paise']);
        $metrics = [
            'total_count' => $allInvoices->count(),
            'total_sales' => round($allInvoices->where('payment_status', '!=', 'CANCELLED')->sum('grand_total_paise') / 100, 2),
            'total_tax' => round($allInvoices->where('payment_status', '!=', 'CANCELLED')->sum('total_tax_paise') / 100, 2),
            'unpaid_due' => round($allInvoices->whereIn('payment_status', ['UNPAID', 'PARTIALLY_PAID'])->sum(function ($inv) {
                return max(0, $inv->grand_total_paise - $inv->paid_amount_paise);
            }) / 100, 2),
        ];

        $invoices = $query->paginate(15)->withQueryString();

        return view('admin.bulk-invoices.index', [
            'invoices' => $invoices,
            'metrics' => $metrics,
            'filters' => $request->only(['q', 'status', 'from_date', 'to_date']),
        ]);
    }

    /**
     * Show form to create a new wholesale / bulk deal invoice.
     */
    public function create(): View
    {
        $store = StoreSettings::current();
        $nextInvoiceNumber = BulkInvoice::generateNextInvoiceNumber();
        $states = IndianStates::STATES;

        // Fetch active catalog products for fast line-item picker
        $products = Product::where('status', 'ACTIVE')
            ->with('defaultVariant:id,product_id,price_paise')
            ->orderBy('name')
            ->get(['id', 'name', 'hsn_code', 'gst_rate_bps'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price_paise' => $p->defaultVariant?->price_paise ?? 0,
                    'hsn_code' => $p->hsn_code ?? '6204',
                    'gst_rate' => $p->gst_rate_bps ? ($p->gst_rate_bps / 100) : 5,
                ];
            });

        return view('admin.bulk-invoices.create', [
            'store' => $store,
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'states' => $states,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created bulk invoice in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInvoice($request);

        try {
            DB::beginTransaction();

            $invoice = $this->saveInvoiceData(new BulkInvoice(), $validated, $request);

            DB::commit();

            return redirect()->route('admin.bulk-invoices.show', $invoice)
                ->with('success', "Bulk Invoice {$invoice->invoice_number} created successfully.");
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice details in the admin portal.
     */
    public function show(BulkInvoice $bulkInvoice): View
    {
        $bulkInvoice->load(['items.product', 'createdBy']);

        return view('admin.bulk-invoices.show', [
            'invoice' => $bulkInvoice,
        ]);
    }

    /**
     * Dedicated printable / PDF GST Tax Invoice view.
     */
    public function invoice(BulkInvoice $bulkInvoice): View
    {
        $bulkInvoice->load(['items.product']);

        // Group line items by HSN for the standard GST HSN summary table
        $hsnSummary = [];
        foreach ($bulkInvoice->items as $item) {
            $hsn = $item->hsn_code ?: '6204';
            if (!isset($hsnSummary[$hsn])) {
                $hsnSummary[$hsn] = [
                    'hsn' => $hsn,
                    'taxable_rupees' => 0.0,
                    'cgst_rate' => $item->cgst_rate_bps / 100,
                    'cgst_rupees' => 0.0,
                    'sgst_rate' => $item->sgst_rate_bps / 100,
                    'sgst_rupees' => 0.0,
                    'igst_rate' => $item->igst_rate_bps / 100,
                    'igst_rupees' => 0.0,
                    'total_tax_rupees' => 0.0,
                ];
            }
            $hsnSummary[$hsn]['taxable_rupees'] += $item->taxable_amount_rupees;
            $hsnSummary[$hsn]['cgst_rupees'] += $item->cgst_amount_rupees;
            $hsnSummary[$hsn]['sgst_rupees'] += $item->sgst_amount_rupees;
            $hsnSummary[$hsn]['igst_rupees'] += $item->igst_amount_rupees;
            $hsnSummary[$hsn]['total_tax_rupees'] += $item->total_tax_rupees;
        }

        return view('admin.bulk-invoices.invoice', [
            'invoice' => $bulkInvoice,
            'hsnSummary' => $hsnSummary,
        ]);
    }

    /**
     * Show form to edit an existing invoice.
     */
    public function edit(BulkInvoice $bulkInvoice): View
    {
        $bulkInvoice->load('items');
        $store = StoreSettings::current();
        $states = IndianStates::STATES;
        $products = Product::where('status', 'ACTIVE')
            ->with('defaultVariant:id,product_id,price_paise')
            ->orderBy('name')
            ->get(['id', 'name', 'hsn_code', 'gst_rate_bps'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price_paise' => $p->defaultVariant?->price_paise ?? 0,
                    'hsn_code' => $p->hsn_code ?? '6204',
                    'gst_rate' => $p->gst_rate_bps ? ($p->gst_rate_bps / 100) : 5,
                ];
            });

        return view('admin.bulk-invoices.edit', [
            'invoice' => $bulkInvoice,
            'store' => $store,
            'states' => $states,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, BulkInvoice $bulkInvoice): RedirectResponse
    {
        $validated = $this->validateInvoice($request, $bulkInvoice->id);

        try {
            DB::beginTransaction();

            // Remove existing items and rebuild
            $bulkInvoice->items()->delete();
            $invoice = $this->saveInvoiceData($bulkInvoice, $validated, $request);

            DB::commit();

            return redirect()->route('admin.bulk-invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} updated successfully.");
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Quick payment status update modal.
     */
    public function updatePayment(Request $request, BulkInvoice $bulkInvoice): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:UNPAID,PAID,PARTIALLY_PAID,CANCELLED',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $status = $validated['payment_status'];
        $paidRupees = (float) ($validated['paid_amount'] ?? 0);
        $paidPaise = (int) round($paidRupees * 100);

        if ($status === 'PAID' && $paidPaise === 0) {
            $paidPaise = $bulkInvoice->grand_total_paise;
        }

        $bulkInvoice->update([
            'payment_status' => $status,
            'paid_amount_paise' => $paidPaise,
            'payment_method' => $validated['payment_method'] ?: $bulkInvoice->payment_method,
            'payment_reference' => $validated['payment_reference'] ?: $bulkInvoice->payment_reference,
            'paid_at' => $status === 'PAID' ? now() : ($paidPaise > 0 ? now() : null),
        ]);

        return back()->with('success', 'Payment status updated successfully.');
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(BulkInvoice $bulkInvoice): RedirectResponse
    {
        $num = $bulkInvoice->invoice_number;
        $bulkInvoice->delete();

        return redirect()->route('admin.bulk-invoices.index')
            ->with('success', "Bulk Invoice {$num} deleted.");
    }

    /**
     * Validate incoming invoice form request.
     */
    private function validateInvoice(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'required|string|max:50|unique:bulk_invoices,invoice_number';
        if ($ignoreId) {
            $uniqueRule .= ",{$ignoreId}";
        }

        return $request->validate([
            'invoice_number' => $uniqueRule,
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_terms' => 'nullable|string|max:50',
            'payment_status' => 'required|in:UNPAID,PAID,PARTIALLY_PAID,CANCELLED',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'paid_amount' => 'nullable|numeric|min:0',

            // Customer
            'customer_name' => 'required|string|max:150',
            'business_name' => 'nullable|string|max:150',
            'customer_gstin' => 'nullable|string|max:20',
            'customer_pan' => 'nullable|string|max:20',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|email|max:100',

            // Billing
            'billing_line1' => 'required|string|max:255',
            'billing_line2' => 'nullable|string|max:255',
            'billing_city' => 'required|string|max:100',
            'billing_state_code' => 'required|string|size:2',
            'billing_postal_code' => 'required|string|max:12',

            // Shipping
            'shipping_same_as_billing' => 'nullable|boolean',
            'shipping_recipient_name' => 'nullable|string|max:150',
            'shipping_line1' => 'nullable|string|max:255',
            'shipping_line2' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state_code' => 'nullable|string|size:2',
            'shipping_postal_code' => 'nullable|string|max:12',

            // Place of Supply & Tax
            'place_of_supply_state_code' => 'required|string|size:2',
            'reverse_charge' => 'nullable|boolean',

            // Extra charges
            'shipping_charges' => 'nullable|numeric|min:0',
            'packaging_charges' => 'nullable|numeric|min:0',
            'additional_discount' => 'nullable|numeric|min:0',
            'round_off' => 'nullable|numeric',

            // Bank details
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc_code' => 'nullable|string|max:30',
            'bank_branch' => 'nullable|string|max:100',
            'bank_upi_id' => 'nullable|string|max:100',

            // Notes
            'customer_notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'disclaimer' => 'nullable|string',

            // Items array
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.hsn_code' => 'nullable|string|max:20',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0|max:100',
            'items.*.product_id' => 'nullable|integer',
        ]);
    }

    /**
     * Compute and save invoice header and line items.
     */
    private function saveInvoiceData(BulkInvoice $invoice, array $validated, Request $request): BulkInvoice
    {
        $admin = auth('admin')->user();
        $store = StoreSettings::current();

        $sellerStateCode = $store->state_code ?? '08';
        $posStateCode = $validated['place_of_supply_state_code'];
        $isIntraState = ($posStateCode === $sellerStateCode);
        $supplyType = $isIntraState ? 'INTRA_STATE' : 'INTER_STATE';

        $sameShipping = $request->boolean('shipping_same_as_billing', true);

        // Compute items
        $totalTaxablePaise = 0;
        $totalCgstPaise = 0;
        $totalSgstPaise = 0;
        $totalIgstPaise = 0;
        $totalTaxPaise = 0;

        $preparedItems = [];
        foreach ($validated['items'] as $index => $row) {
            $qty = (int) $row['quantity'];
            $unitPricePaise = (int) round(((float) $row['unit_price']) * 100);
            $discountPaise = (int) round(((float) ($row['discount'] ?? 0)) * 100);
            $taxablePaise = max(0, ($unitPricePaise * $qty) - $discountPaise);

            $gstRatePercent = (float) $row['gst_rate'];
            $gstRateBps = (int) round($gstRatePercent * 100);

            if ($isIntraState) {
                $cgstBps = (int) round($gstRateBps / 2);
                $sgstBps = $gstRateBps - $cgstBps;
                $igstBps = 0;

                $cgstPaise = (int) round(($taxablePaise * $cgstBps) / 10000);
                $sgstPaise = (int) round(($taxablePaise * $sgstBps) / 10000);
                $igstPaise = 0;
            } else {
                $cgstBps = 0;
                $sgstBps = 0;
                $igstBps = $gstRateBps;

                $cgstPaise = 0;
                $sgstPaise = 0;
                $igstPaise = (int) round(($taxablePaise * $igstBps) / 10000);
            }

            $lineTaxPaise = $cgstPaise + $sgstPaise + $igstPaise;
            $lineTotalPaise = $taxablePaise + $lineTaxPaise;

            $totalTaxablePaise += $taxablePaise;
            $totalCgstPaise += $cgstPaise;
            $totalSgstPaise += $sgstPaise;
            $totalIgstPaise += $igstPaise;
            $totalTaxPaise += $lineTaxPaise;

            $preparedItems[] = [
                'product_id' => $row['product_id'] ?? null,
                'item_name' => trim($row['item_name']),
                'description' => !empty($row['description']) ? trim($row['description']) : null,
                'hsn_code' => !empty($row['hsn_code']) ? trim($row['hsn_code']) : '6204',
                'quantity' => $qty,
                'unit' => !empty($row['unit']) ? trim($row['unit']) : 'PCS',
                'unit_price_paise' => $unitPricePaise,
                'discount_paise' => $discountPaise,
                'taxable_amount_paise' => $taxablePaise,
                'gst_rate_bps' => $gstRateBps,
                'cgst_rate_bps' => $cgstBps,
                'cgst_amount_paise' => $cgstPaise,
                'sgst_rate_bps' => $sgstBps,
                'sgst_amount_paise' => $sgstPaise,
                'igst_rate_bps' => $igstBps,
                'igst_amount_paise' => $igstPaise,
                'total_tax_paise' => $lineTaxPaise,
                'line_total_paise' => $lineTotalPaise,
                'position' => $index + 1,
            ];
        }

        $shippingPaise = (int) round(((float) ($validated['shipping_charges'] ?? 0)) * 100);
        $packagingPaise = (int) round(((float) ($validated['packaging_charges'] ?? 0)) * 100);
        $additionalDiscountPaise = (int) round(((float) ($validated['additional_discount'] ?? 0)) * 100);
        $roundOffPaise = (int) round(((float) ($validated['round_off'] ?? 0)) * 100);

        $grandTotalPaise = max(0, $totalTaxablePaise + $totalTaxPaise + $shippingPaise + $packagingPaise - $additionalDiscountPaise + $roundOffPaise);

        $paidRupees = (float) ($validated['paid_amount'] ?? 0);
        $paidPaise = (int) round($paidRupees * 100);
        if ($validated['payment_status'] === 'PAID' && $paidPaise === 0) {
            $paidPaise = $grandTotalPaise;
        }

        $billingStateName = IndianStates::getName($validated['billing_state_code']) ?? $validated['billing_state_code'];
        $posStateName = IndianStates::getName($posStateCode) ?? $posStateCode;

        if ($sameShipping) {
            $shippingRecipient = $validated['customer_name'];
            $shippingLine1 = $validated['billing_line1'];
            $shippingLine2 = $validated['billing_line2'] ?? null;
            $shippingCity = $validated['billing_city'];
            $shippingStateCode = $validated['billing_state_code'];
            $shippingStateName = $billingStateName;
            $shippingPostalCode = $validated['billing_postal_code'];
        } else {
            $shippingRecipient = (!empty($validated['shipping_recipient_name'])) ? $validated['shipping_recipient_name'] : $validated['customer_name'];
            $shippingLine1 = (!empty($validated['shipping_line1'])) ? $validated['shipping_line1'] : $validated['billing_line1'];
            $shippingLine2 = $validated['shipping_line2'] ?? null;
            $shippingCity = (!empty($validated['shipping_city'])) ? $validated['shipping_city'] : $validated['billing_city'];
            $shippingStateCode = (!empty($validated['shipping_state_code'])) ? $validated['shipping_state_code'] : $validated['billing_state_code'];
            $shippingStateName = IndianStates::getName($shippingStateCode) ?? $shippingStateCode;
            $shippingPostalCode = (!empty($validated['shipping_postal_code'])) ? $validated['shipping_postal_code'] : $validated['billing_postal_code'];
        }

        $invoice->fill([
            'invoice_number' => trim($validated['invoice_number']),
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'payment_terms' => (!empty($validated['payment_terms'])) ? $validated['payment_terms'] : 'Due on Receipt',
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'paid_amount_paise' => $paidPaise,
            'paid_at' => $validated['payment_status'] === 'PAID' ? now() : ($paidPaise > 0 ? now() : null),

            // Seller
            'seller_name' => 'Dhansree Trends',
            'seller_legal_name' => $store?->legal_name ?? 'Dhanshree Trends',
            'seller_gstin' => $store?->gstin ?? '08CCUPS2872J1ZO',
            'seller_pan' => substr($store?->gstin ?? '08CCUPS2872J1ZO', 2, 10),
            'seller_address' => ($store?->address_line1 ?? 'C-197 Murlipura Scheme') . ($store?->address_line2 ? ', ' . $store->address_line2 : ''),
            'seller_city' => $store?->city ?? 'Jaipur',
            'seller_state_code' => $sellerStateCode,
            'seller_state_name' => IndianStates::getName($sellerStateCode) ?? 'Rajasthan',
            'seller_postal_code' => $store?->postal_code ?? '302039',
            'seller_phone' => $store?->support_phone ?? '+919887776777',
            'seller_email' => $store?->support_email ?? 'info@dhansree.com',

            // Customer
            'customer_name' => trim($validated['customer_name']),
            'business_name' => !empty($validated['business_name']) ? trim($validated['business_name']) : null,
            'customer_gstin' => !empty($validated['customer_gstin']) ? strtoupper(trim($validated['customer_gstin'])) : null,
            'customer_pan' => !empty($validated['customer_pan']) ? strtoupper(trim($validated['customer_pan'])) : null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_email' => $validated['customer_email'] ?? null,

            // Billing
            'billing_line1' => trim($validated['billing_line1']),
            'billing_line2' => !empty($validated['billing_line2']) ? trim($validated['billing_line2']) : null,
            'billing_city' => trim($validated['billing_city']),
            'billing_state_code' => $validated['billing_state_code'],
            'billing_state_name' => $billingStateName,
            'billing_postal_code' => trim($validated['billing_postal_code']),
            'billing_country' => 'India',

            // Shipping
            'shipping_same_as_billing' => $sameShipping,
            'shipping_recipient_name' => $shippingRecipient,
            'shipping_line1' => $shippingLine1,
            'shipping_line2' => $shippingLine2,
            'shipping_city' => $shippingCity,
            'shipping_state_code' => $shippingStateCode,
            'shipping_state_name' => $shippingStateName,
            'shipping_postal_code' => $shippingPostalCode,
            'shipping_country' => 'India',

            // Tax & Place of supply
            'place_of_supply_state_code' => $posStateCode,
            'place_of_supply_state_name' => $posStateName,
            'supply_type' => $supplyType,
            'reverse_charge' => $request->boolean('reverse_charge', false),

            // Totals
            'subtotal_taxable_paise' => $totalTaxablePaise,
            'total_discount_paise' => $additionalDiscountPaise,
            'shipping_charges_paise' => $shippingPaise,
            'packaging_charges_paise' => $packagingPaise,
            'cgst_paise' => $totalCgstPaise,
            'sgst_paise' => $totalSgstPaise,
            'igst_paise' => $totalIgstPaise,
            'total_tax_paise' => $totalTaxPaise,
            'round_off_paise' => $roundOffPaise,
            'grand_total_paise' => $grandTotalPaise,

            // Bank details
            'bank_name' => $validated['bank_name'] ?? 'Bandhan Bank',
            'bank_account_name' => $validated['bank_account_name'] ?? ($store?->legal_name ?? 'Dhanshree Trends'),
            'bank_account_number' => $validated['bank_account_number'] ?? '20100080581870',
            'bank_ifsc_code' => $validated['bank_ifsc_code'] ?? 'BDBL0001886',
            'bank_branch' => $validated['bank_branch'] ?? 'Jaipur, Tonk Road',
            'bank_upi_id' => $validated['bank_upi_id'] ?? null,

            // Notes & terms
            'customer_notes' => $validated['customer_notes'] ?? null,
            'terms_and_conditions' => (!empty($validated['terms_and_conditions'])) ? $validated['terms_and_conditions'] : "1. Goods once sold will not be accepted back or exchanged.\n2. Interest @ 18% p.a. will be charged if payment is not made within the due date.\n3. Subject to Jaipur jurisdiction only.",
            'disclaimer' => (!empty($validated['disclaimer'])) ? $validated['disclaimer'] : 'This is a system generated invoice and does not require a physical signature.',
            'created_by_admin_user_id' => $admin?->id,
        ]);

        $invoice->save();

        foreach ($preparedItems as $itemData) {
            $invoice->items()->create($itemData);
        }

        return $invoice;
    }
}
