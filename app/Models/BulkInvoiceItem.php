<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkInvoiceItem extends Model
{
    protected $table = 'bulk_invoice_items';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_paise' => 'integer',
        'discount_paise' => 'integer',
        'taxable_amount_paise' => 'integer',
        'gst_rate_bps' => 'integer',
        'cgst_rate_bps' => 'integer',
        'cgst_amount_paise' => 'integer',
        'sgst_rate_bps' => 'integer',
        'sgst_amount_paise' => 'integer',
        'igst_rate_bps' => 'integer',
        'igst_amount_paise' => 'integer',
        'total_tax_paise' => 'integer',
        'line_total_paise' => 'integer',
        'position' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BulkInvoice::class, 'bulk_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /* Rupee float accessors */
    public function getUnitPriceRupeesAttribute(): float
    {
        return round($this->unit_price_paise / 100, 2);
    }

    public function getDiscountRupeesAttribute(): float
    {
        return round($this->discount_paise / 100, 2);
    }

    public function getTaxableAmountRupeesAttribute(): float
    {
        return round($this->taxable_amount_paise / 100, 2);
    }

    public function getCgstAmountRupeesAttribute(): float
    {
        return round($this->cgst_amount_paise / 100, 2);
    }

    public function getSgstAmountRupeesAttribute(): float
    {
        return round($this->sgst_amount_paise / 100, 2);
    }

    public function getIgstAmountRupeesAttribute(): float
    {
        return round($this->igst_amount_paise / 100, 2);
    }

    public function getTotalTaxRupeesAttribute(): float
    {
        return round($this->total_tax_paise / 100, 2);
    }

    public function getLineTotalRupeesAttribute(): float
    {
        return round($this->line_total_paise / 100, 2);
    }

    public function getGstRatePercentAttribute(): float
    {
        return round($this->gst_rate_bps / 100, 2);
    }
}
