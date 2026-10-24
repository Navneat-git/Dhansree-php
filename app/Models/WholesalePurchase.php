<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesalePurchase extends Model
{
    use SoftDeletes;

    protected $table = 'wholesale_purchases';

    protected $fillable = [
        'purchase_type',
        'sku',
        'product_name',
        'image_path',
        'image_url',
        'status',
        'purchase_date',
        'created_by',

        // Women Fashion
        'brand_code',
        'design_number',
        'category_name',
        'vcode',
        'category_code',
        'pcode',
        'color',
        'color_code',
        'size',
        'thread_count',
        'cost',
        'gst_percent',
        'gst_amount',
        'total_cost',
        'markup',
        'selling_price',

        // Smart Living & Gadgets
        'description',
        'category_id',
        'cost_price',
        'wholesale_price',
        'retail_markup',
        'retail_price',
        'stock_qty',
        'moq',
        'brand',
        'model',
        'badge',
        'supplier',

        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'cost' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'markup' => 'decimal:3',
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'retail_markup' => 'decimal:3',
        'retail_price' => 'decimal:2',
        'stock_qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const TYPE_WOMEN_FASHION = 'women_fashion';
    public const TYPE_SMART_LIVING = 'smart_living';

    public const CATEGORY_CODES = [
        'KURTI' => 'KT',
        '2 SUIT SET' => 'S2',
        '3 SUIT SET' => 'S3',
        'CO-ORD SET' => 'CS',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get accessible image URL.
     */
    public function getEffectiveImageUrlAttribute(): ?string
    {
        if (!empty($this->image_url)) {
            return $this->image_url;
        }

        if (!empty($this->image_path)) {
            return url('/media/' . ltrim($this->image_path, '/'));
        }

        return null;
    }

    /**
     * Automatically deduce category code from category name.
     */
    public static function resolveCategoryCode(?string $categoryName): string
    {
        if (!$categoryName) {
            return '';
        }

        $upper = strtoupper(trim($categoryName));
        if (isset(self::CATEGORY_CODES[$upper])) {
            return self::CATEGORY_CODES[$upper];
        }

        // Fuzzy match common terms
        if (str_contains($upper, 'KURTI')) return 'KT';
        if (str_contains($upper, '3 SUIT') || str_contains($upper, 'THREE PIECE')) return 'S3';
        if (str_contains($upper, '2 SUIT') || str_contains($upper, 'TWO PIECE')) return 'S2';
        if (str_contains($upper, 'CO-ORD') || str_contains($upper, 'COORD')) return 'CS';

        // Fallback: First 2 letters
        return substr(preg_replace('/[^A-Za-z0-9]/', '', $upper), 0, 2);
    }

    /**
     * Automatically format date to ddmmyy.
     */
    public static function formatDateToDdmmyy(mixed $date): string
    {
        if (!$date) {
            return '';
        }

        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $c->format('dmy'); // ddmmyy format: e.g. 190426
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Generate pcode = vcode + ddmmyy + design_number
     */
    public static function generatePcode(?string $vcode, mixed $purchaseDate, ?string $designNumber): string
    {
        $v = strtoupper(trim((string) $vcode));
        $d = self::formatDateToDdmmyy($purchaseDate);
        $des = trim((string) $designNumber);

        return $v . $d . $des;
    }

    /**
     * Generate SKU for Women Fashion:
     * First 2 letters of Brand Code (e.g. 'DS') + pcode + '-' + category_code + '-' + color_code + '-' + size
     * e.g. DSICF19042610-S3-BLU-XL
     */
    public static function generateWomenSku(
        ?string $brandCode,
        ?string $pcode,
        ?string $categoryCode,
        ?string $colorCode,
        ?string $size
    ): string {
        $brand = strtoupper(substr(trim((string) ($brandCode ?: 'DS')), 0, 2));
        $p = strtoupper(trim((string) $pcode));
        $cat = strtoupper(trim((string) $categoryCode));
        $col = strtoupper(trim((string) $colorCode));
        $sz = strtoupper(trim((string) $size));

        $sku = $brand . $p;
        $parts = [];
        if ($cat !== '') $parts[] = $cat;
        if ($col !== '') $parts[] = $col;
        if ($sz !== '') $parts[] = $sz;

        if (!empty($parts)) {
            $sku .= '-' . implode('-', $parts);
        }

        return $sku;
    }

    /**
     * Compute financial figures for Women Fashion.
     */
    public static function computeWomenCosts(float $cost, float $gstPercent = 5.0, float $markup = 1.0): array
    {
        $gstAmount = round($cost * ($gstPercent / 100), 2);
        $totalCost = round($cost + $gstAmount, 2);
        $sellingPrice = round($totalCost * $markup, 2);

        return [
            'gst_amount' => $gstAmount,
            'total_cost' => $totalCost,
            'selling_price' => $sellingPrice,
        ];
    }

    /**
     * Compute financial figures for Smart Living.
     */
    public static function computeSmartLivingPrices(float $costPrice, float $markup = 1.0, float $retailMarkup = 1.0): array
    {
        $wholesalePrice = round($costPrice * $markup, 2);
        $retailPrice = round($costPrice * $retailMarkup, 2);

        return [
            'wholesale_price' => $wholesalePrice,
            'retail_price' => $retailPrice,
        ];
    }

    /**
     * Get next 3-digit serial number for Smart Living & Gadgets SKUs.
     * Scans both WholesalePurchase (smart_living) and ProductVariant (DS-*-###) to find highest serial.
     * e.g., if max is 61 (from 'DS-MSG-GUN-061'), returns '062'.
     */
    public static function getNextSmartLivingSerialNumber(): string
    {
        $maxSerial = 0;

        // 1. Scan wholesale_purchases where purchase_type = smart_living
        $wpSkus = self::withTrashed()
            ->where('purchase_type', self::TYPE_SMART_LIVING)
            ->whereNotNull('sku')
            ->pluck('sku');

        foreach ($wpSkus as $sku) {
            if (preg_match('/-(\d{1,5})$/', trim((string) $sku), $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxSerial) {
                    $maxSerial = $num;
                }
            }
        }

        // 2. Also scan existing catalog product variants (e.g. DS-TMR-009, DS-IRON-MINI-016, DS-DT-CBL-018)
        try {
            $variantSkus = ProductVariant::where('sku', 'like', 'DS-%')
                ->pluck('sku');

            foreach ($variantSkus as $sku) {
                // Must match DS-...-### (at least 3 digits at end to avoid matching fashion sizes like -S, -M, -L or random letters)
                if (preg_match('/^DS-[A-Za-z0-9-]+-(\d{3,5})$/', trim((string) $sku), $matches)) {
                    $num = (int) $matches[1];
                    if ($num > $maxSerial) {
                        $maxSerial = $num;
                    }
                }
            }
        } catch (\Throwable) {
            // Fallback gracefully
        }

        $next = $maxSerial + 1;

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Format/complete a Smart Living SKU with the next serial number if not already present.
     * e.g. 'DS-JWL-BOX' -> 'DS-JWL-BOX-062'
     * e.g. 'DS-MSG-GUN-061' -> 'DS-MSG-GUN-061' (keeps existing 3-digit serial)
     * e.g. 'DS-JWL-BOX-62' -> 'DS-JWL-BOX-062' (pads 1-2 digits to 3 digits)
     */
    public static function completeSmartLivingSku(?string $rawSku, ?string $explicitSerial = null): string
    {
        $sku = strtoupper(trim((string) $rawSku));
        if ($sku === '') {
            return '';
        }

        // If it already ends with 3+ digits preceded by a hyphen, e.g. DS-MSG-GUN-061
        if (preg_match('/-(\d{3,})$/', $sku)) {
            return $sku;
        }

        // If it ends with 1 or 2 digits preceded by a hyphen, e.g. DS-JWL-BOX-62 -> pad to 062
        if (preg_match('/^(.*?)-(\d{1,2})$/', $sku, $matches)) {
            $base = $matches[1];
            $padded = str_pad($matches[2], 3, '0', STR_PAD_LEFT);
            return $base . '-' . $padded;
        }

        // Otherwise it has no serial suffix e.g. 'DS-JWL-BOX' or 'DS-JWL-BOX-'
        $base = rtrim($sku, '-_ ');
        $serial = $explicitSerial ?: self::getNextSmartLivingSerialNumber();

        return $base . '-' . $serial;
    }
}
