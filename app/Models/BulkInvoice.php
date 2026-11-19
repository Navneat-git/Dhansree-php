<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Commerce\IndianCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkInvoice extends Model
{
    protected $table = 'bulk_invoices';

    protected $guarded = ['id'];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'shipping_same_as_billing' => 'boolean',
        'reverse_charge' => 'boolean',
        'subtotal_taxable_paise' => 'integer',
        'total_discount_paise' => 'integer',
        'shipping_charges_paise' => 'integer',
        'shipping_gst_rate_bps' => 'integer',
        'packaging_charges_paise' => 'integer',
        'cgst_paise' => 'integer',
        'sgst_paise' => 'integer',
        'igst_paise' => 'integer',
        'total_tax_paise' => 'integer',
        'round_off_paise' => 'integer',
        'grand_total_paise' => 'integer',
        'paid_amount_paise' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BulkInvoiceItem::class, 'bulk_invoice_id')->orderBy('position');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    /* Rupee float accessors */
    public function getSubtotalTaxableRupeesAttribute(): float
    {
        return round($this->subtotal_taxable_paise / 100, 2);
    }

    public function getTotalDiscountRupeesAttribute(): float
    {
        return round($this->total_discount_paise / 100, 2);
    }

    public function getShippingChargesRupeesAttribute(): float
    {
        return round($this->shipping_charges_paise / 100, 2);
    }

    public function getPackagingChargesRupeesAttribute(): float
    {
        return round($this->packaging_charges_paise / 100, 2);
    }

    public function getCgstRupeesAttribute(): float
    {
        return round($this->cgst_paise / 100, 2);
    }

    public function getSgstRupeesAttribute(): float
    {
        return round($this->sgst_paise / 100, 2);
    }

    public function getIgstRupeesAttribute(): float
    {
        return round($this->igst_paise / 100, 2);
    }

    public function getTotalTaxRupeesAttribute(): float
    {
        return round($this->total_tax_paise / 100, 2);
    }

    public function getRoundOffRupeesAttribute(): float
    {
        return round($this->round_off_paise / 100, 2);
    }

    public function getGrandTotalRupeesAttribute(): float
    {
        return round($this->grand_total_paise / 100, 2);
    }

    public function getPaidAmountRupeesAttribute(): float
    {
        return round($this->paid_amount_paise / 100, 2);
    }

    public function getBalanceDueRupeesAttribute(): float
    {
        $due = max(0, $this->grand_total_paise - $this->paid_amount_paise);
        return round($due / 100, 2);
    }

    public function getAmountInWordsAttribute(): string
    {
        return IndianCurrency::formatWords($this->grand_total_paise);
    }

    /**
     * Generate sequential invoice number (e.g. INV-B2B-2026-0001).
     */
    public static function generateNextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-B2B-{$year}-";

        $last = static::where('invoice_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('invoice_number');

        if ($last) {
            $numPart = (int) substr($last, strlen($prefix));
            $next = str_pad((string) ($numPart + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }

        return $prefix . $next;
    }
}
