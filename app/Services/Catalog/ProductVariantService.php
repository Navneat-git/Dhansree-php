<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\AuditActorType;
use App\Enums\BackorderPolicy;
use App\Enums\ProductStatus;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\WholesaleColorCode;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProductVariantService
{
    /**
     * Build option signature (sorted option value ids: e.g. "14-25").
     *
     * @param int[] $valueIds
     */
    public static function signature(array $valueIds): string
    {
        sort($valueIds, SORT_NUMERIC);
        return implode('-', $valueIds);
    }

    /**
     * Resolve standard 3-4 letter uppercase color code for wholesale/fashion SKUs (e.g. Pink -> PNK, Blue -> BLU, Brown -> BRW).
     */
    public static function resolveColorCode(?string $colorName): string
    {
        return WholesaleColorCode::resolveCode($colorName);
    }

    /**
     * Create or update options structure for a product.
     *
     * @param array<int, array{name: string, values: array<int, string>}> $optionsInput
     */
    public function syncOptions(Product $product, array $optionsInput, ?int $adminUserId = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($product, $optionsInput, $adminUserId, $request) {
            $optPos = 0;
            $existingOptions = ProductOption::where('product_id', $product->id)->with('values')->get();
            $existingOptionMap = $existingOptions->keyBy('name');

            $keptOptionIds = [];
            $keptValueIds = [];

            foreach ($optionsInput as $optData) {
                $name = trim($optData['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $option = $existingOptionMap->get($name) ?? ProductOption::create([
                    'product_id' => $product->id,
                    'name' => Str::limit($name, 40),
                    'position' => $optPos++,
                ]);

                $keptOptionIds[] = $option->id;
                $existingValuesMap = $option->values ? $option->values->keyBy('value') : collect();

                $valPos = 0;
                foreach ($optData['values'] as $valStr) {
                    $valStr = trim((string) $valStr);
                    if ($valStr === '') {
                        continue;
                    }

                    $val = $existingValuesMap->get($valStr) ?? ProductOptionValue::create([
                        'option_id' => $option->id,
                        'value' => Str::limit($valStr, 50),
                        'position' => $valPos++,
                    ]);

                    $keptValueIds[] = $val->id;
                }
            }

            // Identify and remove unused options
            $removedOptionIds = ProductOption::where('product_id', $product->id)
                ->whereNotIn('id', $keptOptionIds)
                ->pluck('id')
                ->all();

            if (!empty($removedOptionIds)) {
                $affectedVariantIdsFromOptions = DB::table('product_variant_option_values')
                    ->whereIn('option_id', $removedOptionIds)
                    ->pluck('variant_id')
                    ->unique()
                    ->all();

                if (!empty($affectedVariantIdsFromOptions)) {
                    ProductVariant::whereIn('id', $affectedVariantIdsFromOptions)->update([
                        'archived_at' => Carbon::now('UTC'),
                        'option_signature' => null,
                    ]);
                }

                ProductOption::whereIn('id', $removedOptionIds)->delete();
            }

            // Identify and remove unused option values for kept options
            $removedValueIds = ProductOptionValue::whereIn('option_id', $keptOptionIds)
                ->whereNotIn('id', $keptValueIds)
                ->pluck('id')
                ->all();

            if (!empty($removedValueIds)) {
                $affectedVariantIds = DB::table('product_variant_option_values')
                    ->whereIn('option_value_id', $removedValueIds)
                    ->pluck('variant_id')
                    ->unique()
                    ->all();

                if (!empty($affectedVariantIds)) {
                    ProductVariant::whereIn('id', $affectedVariantIds)->update([
                        'archived_at' => Carbon::now('UTC'),
                        'option_signature' => null,
                    ]);
                }

                ProductOptionValue::whereIn('id', $removedValueIds)->delete();
            }

            // If all options were removed and no active variants remain, reactivate the primary variant as the default single variant
            if (empty($keptOptionIds)) {
                $activeCount = ProductVariant::where('product_id', $product->id)
                    ->whereNull('archived_at')
                    ->count();

                if ($activeCount === 0) {
                    $primaryVariant = ProductVariant::where('product_id', $product->id)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->first();

                    if ($primaryVariant) {
                        $primaryVariant->update([
                            'archived_at' => null,
                            'option_signature' => '',
                        ]);
                    }
                }
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.product.options_changed',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product',
                    entityId: $product->id,
                    metadata: ['options_count' => count($keptOptionIds)],
                    request: $request
                );
            }
        });
    }

    /**
     * Create a single variant.
     */
    public function createVariant(Product $product, array $data, ?int $adminUserId = null, ?Request $request = null): ProductVariant
    {
        return DB::transaction(function () use ($product, $data, $adminUserId, $request) {
            $sku = strtoupper(trim($data['sku'] ?? ''));
            if (empty($sku)) {
                $sku = strtoupper(Str::slug($product->name, '')) . '-' . strtoupper(Str::random(4));
            }

            if (ProductVariant::where('sku', $sku)->exists()) {
                throw new InvalidArgumentException("SKU '{$sku}' is already taken by another variant.");
            }

            $valueIds = array_map('intval', $data['option_value_ids'] ?? []);
            $sig = self::signature($valueIds);

            // Check signature uniqueness for this product
            if (!empty($valueIds)) {
                $conflict = ProductVariant::where('product_id', $product->id)
                    ->where('option_signature', $sig)
                    ->whereNull('archived_at')
                    ->exists();
                if ($conflict) {
                    throw new InvalidArgumentException('A live variant with this combination of options already exists.');
                }
            }

            $pricePaise = (int) round(($data['price_rupees'] ?? 0) * 100);
            $mrpPaise = !empty($data['mrp_rupees'])
                ? (int) round($data['mrp_rupees'] * 100)
                : $pricePaise;
            $costPaise = !empty($data['cost_rupees'])
                ? (int) round($data['cost_rupees'] * 100)
                : null;

            if ($mrpPaise < $pricePaise) {
                throw new InvalidArgumentException('MRP cannot be less than selling price.');
            }

            $maxPos = ProductVariant::where('product_id', $product->id)->max('position');
            $position = $maxPos !== null ? $maxPos + 1 : 0;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'price_paise' => $pricePaise,
                'mrp_paise' => $mrpPaise,
                'cost_paise' => $costPaise,
                'weight_grams' => !empty($data['weight_grams']) ? (int) $data['weight_grams'] : 250,
                'option_signature' => $sig,
                'position' => $position,
                'version' => 0,
            ]);

            // Link option values
            if (!empty($valueIds)) {
                $pivotData = [];
                $optionValues = ProductOptionValue::whereIn('id', $valueIds)->get();
                foreach ($optionValues as $ov) {
                    $pivotData[] = [
                        'variant_id' => $variant->id,
                        'option_id' => $ov->option_id,
                        'option_value_id' => $ov->id,
                    ];
                }
                DB::table('product_variant_option_values')->insert($pivotData);
            }

            // Initialize inventory
            $defaultLocation = InventoryLocation::where('is_default', true)->first()
                ?? InventoryLocation::firstOrCreate(
                    ['code' => 'MAIN'],
                    ['name' => 'Main location', 'is_active' => true, 'is_default' => true]
                );

            InventoryItem::firstOrCreate(
                ['variant_id' => $variant->id],
                ['backorder_policy' => BackorderPolicy::STOP_SALE]
            );

            $initialStock = max(0, (int) ($data['stock_quantity'] ?? 0));
            InventoryLevel::firstOrCreate(
                ['variant_id' => $variant->id, 'location_id' => $defaultLocation->id],
                ['on_hand' => $initialStock, 'reserved' => 0]
            );

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.variant.created',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_variant',
                    entityId: $variant->id,
                    metadata: ['product_id' => $product->id, 'sku' => $sku],
                    request: $request
                );
            }

            return $variant;
        });
    }

    /**
     * Update variant pricing, weight, SKU, and stock quantity.
     */
    public function updateVariant(ProductVariant $variant, array $data, ?int $adminUserId = null, ?Request $request = null): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data, $adminUserId, $request) {
            $current = ProductVariant::lockForUpdate()->findOrFail($variant->id);

            $newSku = strtoupper(trim($data['sku'] ?? $current->sku));
            if ($newSku !== $current->sku) {
                if (ProductVariant::where('sku', $newSku)->where('id', '!=', $current->id)->exists()) {
                    throw new InvalidArgumentException("SKU '{$newSku}' is already taken by another variant.");
                }
            }

            $pricePaise = isset($data['price_rupees'])
                ? (int) round($data['price_rupees'] * 100)
                : $current->price_paise;

            $mrpPaise = isset($data['mrp_rupees'])
                ? (int) round($data['mrp_rupees'] * 100)
                : $current->mrp_paise;

            $costPaise = array_key_exists('cost_rupees', $data)
                ? ($data['cost_rupees'] !== null ? (int) round($data['cost_rupees'] * 100) : null)
                : $current->cost_paise;

            if ($mrpPaise < $pricePaise) {
                throw new InvalidArgumentException('MRP cannot be less than selling price.');
            }

            $current->update([
                'sku' => $newSku,
                'price_paise' => $pricePaise,
                'mrp_paise' => $mrpPaise,
                'cost_paise' => $costPaise,
                'weight_grams' => isset($data['weight_grams']) ? (int) $data['weight_grams'] : $current->weight_grams,
                'version' => $current->version + 1,
            ]);

            // Stock update if passed
            if (array_key_exists('stock_quantity', $data) && $data['stock_quantity'] !== null) {
                $defaultLocation = InventoryLocation::where('is_default', true)->first()
                    ?? InventoryLocation::firstOrCreate(
                        ['code' => 'MAIN'],
                        ['name' => 'Main location', 'is_active' => true, 'is_default' => true]
                    );

                InventoryItem::firstOrCreate(
                    ['variant_id' => $current->id],
                    ['backorder_policy' => BackorderPolicy::STOP_SALE]
                );

                $level = InventoryLevel::firstOrCreate(
                    ['variant_id' => $current->id, 'location_id' => $defaultLocation->id],
                    ['on_hand' => 0, 'reserved' => 0]
                );

                $newQty = max(0, (int) $data['stock_quantity']);
                $level->update(['on_hand' => $newQty]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.variant.updated',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_variant',
                    entityId: $current->id,
                    metadata: ['sku' => $newSku, 'price_paise' => $pricePaise],
                    request: $request
                );
            }

            return $current;
        });
    }

    /**
     * Archive variant. Refuses if product is ACTIVE and this is the last live variant.
     */
    public function archiveVariant(ProductVariant $variant, ?int $adminUserId = null, ?Request $request = null): ProductVariant
    {
        return DB::transaction(function () use ($variant, $adminUserId, $request) {
            $current = ProductVariant::lockForUpdate()->findOrFail($variant->id);
            $product = Product::findOrFail($current->product_id);

            if ($product->status === ProductStatus::ACTIVE) {
                $liveCount = ProductVariant::where('product_id', $product->id)
                    ->whereNull('archived_at')
                    ->count();
                if ($liveCount <= 1) {
                    throw new InvalidArgumentException('Cannot archive the only active variant of a published product. Unpublish the product first.');
                }
            }

            $current->update([
                'archived_at' => Carbon::now('UTC'),
                'option_signature' => null,
                'version' => $current->version + 1,
            ]);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.variant.archived',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_variant',
                    entityId: $current->id,
                    request: $request
                );
            }

            return $current;
        });
    }

    /**
     * Restore variant.
     */
    public function restoreVariant(ProductVariant $variant, ?int $adminUserId = null, ?Request $request = null): ProductVariant
    {
        return DB::transaction(function () use ($variant, $adminUserId, $request) {
            $current = ProductVariant::lockForUpdate()->findOrFail($variant->id);

            $valueIds = DB::table('product_variant_option_values')
                ->where('variant_id', $current->id)
                ->pluck('option_value_id')
                ->all();
            $sig = self::signature(array_map('intval', $valueIds));

            $current->update([
                'archived_at' => null,
                'option_signature' => $sig,
                'version' => $current->version + 1,
            ]);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.variant.restored',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_variant',
                    entityId: $current->id,
                    request: $request
                );
            }

            return $current;
        });
    }

    /**
     * Permanently delete a variant and its associated inventory/images mappings.
     * Prevents deletion if linked to existing customer orders or shipments.
     */
    public function deleteVariant(ProductVariant $variant, ?int $adminUserId = null, ?Request $request = null): bool
    {
        return DB::transaction(function () use ($variant, $adminUserId, $request) {
            $current = ProductVariant::lockForUpdate()->findOrFail($variant->id);

            // Safety Check 1: Order items
            $hasOrders = DB::table('order_items')->where('variant_id', $current->id)->exists();
            if ($hasOrders) {
                throw new InvalidArgumentException("Variant '{$current->sku}' cannot be deleted because it is linked to existing customer orders. You can archive it instead.");
            }

            // Safety Check 2: Shipment items
            $hasShipments = DB::table('shipment_items')->where('variant_id', $current->id)->exists();
            if ($hasShipments) {
                throw new InvalidArgumentException("Variant '{$current->sku}' cannot be deleted because it is linked to customer shipments.");
            }

            // 1. Delete inventory movements & reservations
            DB::table('inventory_movements')->where('variant_id', $current->id)->delete();
            DB::table('inventory_reservations')->where('variant_id', $current->id)->delete();

            // 2. Delete inventory levels & item
            DB::table('inventory_levels')->where('variant_id', $current->id)->delete();
            DB::table('inventory_items')->where('variant_id', $current->id)->delete();

            // 3. Detach from images and option values
            DB::table('product_image_variants')->where('variant_id', $current->id)->delete();
            DB::table('product_images')->where('variant_id', $current->id)->update(['variant_id' => null]);
            DB::table('product_variant_option_values')->where('variant_id', $current->id)->delete();
            DB::table('variant_marketplace_values')->where('variant_id', $current->id)->delete();

            $deletedSku = $current->sku;
            $deletedId = $current->id;

            $current->delete();

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.variant.deleted',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_variant',
                    entityId: $deletedId,
                    metadata: ['sku' => $deletedSku],
                    request: $request
                );
            }

            return true;
        });
    }

    /**
     * Delete all variants of a product (skips variants with existing orders).
     */
    public function deleteAllVariants(Product $product, ?int $adminUserId = null, ?Request $request = null): int
    {
        $variants = ProductVariant::where('product_id', $product->id)->get();
        $deletedCount = 0;
        $skipped = [];

        foreach ($variants as $variant) {
            try {
                $this->deleteVariant($variant, $adminUserId, $request);
                $deletedCount++;
            } catch (InvalidArgumentException $e) {
                $skipped[] = $variant->sku;
            }
        }

        if (!empty($skipped)) {
            throw new InvalidArgumentException("Deleted {$deletedCount} variants. Could not delete variants with existing orders: " . implode(', ', $skipped));
        }

        return $deletedCount;
    }

    /**
     * Bulk update stock quantity across all variants of a product.
     */
    public function bulkUpdateStock(Product $product, int $quantity, ?int $adminUserId = null, ?Request $request = null): int
    {
        return DB::transaction(function () use ($product, $quantity, $adminUserId, $request) {
            $defaultLocation = InventoryLocation::where('is_default', true)->first()
                ?? InventoryLocation::firstOrCreate(
                    ['code' => 'MAIN'],
                    ['name' => 'Main location', 'is_active' => true, 'is_default' => true]
                );

            $variants = ProductVariant::where('product_id', $product->id)->get();
            $qty = max(0, $quantity);

            foreach ($variants as $variant) {
                InventoryItem::firstOrCreate(
                    ['variant_id' => $variant->id],
                    ['backorder_policy' => BackorderPolicy::STOP_SALE]
                );

                $level = InventoryLevel::firstOrCreate(
                    ['variant_id' => $variant->id, 'location_id' => $defaultLocation->id],
                    ['on_hand' => 0, 'reserved' => 0]
                );

                $level->update(['on_hand' => $qty]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.variant.bulk_stock_updated',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product',
                    entityId: $product->id,
                    metadata: ['stock_quantity' => $qty, 'variants_count' => $variants->count()],
                    request: $request
                );
            }

            return $variants->count();
        });
    }

    /**
     * Generate Cartesian matrix of variants from existing product options.
     * Skips combinations that already exist.
     *
     * @return ProductVariant[]
     */
    public function generateVariantMatrix(
        Product $product,
        float $defaultPriceRupees,
        float $defaultMrpRupees,
        ?string $baseSku = null,
        ?int $adminUserId = null,
        ?Request $request = null,
        int $defaultStock = 0
    ): array {
        return DB::transaction(function () use ($product, $defaultPriceRupees, $defaultMrpRupees, $baseSku, $adminUserId, $request, $defaultStock) {
            $options = ProductOption::where('product_id', $product->id)
                ->with(['values' => fn($q) => $q->orderBy('position')])
                ->orderBy('position')
                ->get();

            if ($options->isEmpty()) {
                throw new InvalidArgumentException('Cannot generate matrix: product has no options configured.');
            }

            // Generate Cartesian product of values
            $sets = [];
            foreach ($options as $opt) {
                $values = $opt->values->all();
                if (empty($values)) {
                    throw new InvalidArgumentException("Option '{$opt->name}' has no values.");
                }
                $sets[] = $values;
            }

            $cartesian = [[]];
            foreach ($sets as $set) {
                $append = [];
                foreach ($cartesian as $combo) {
                    foreach ($set as $item) {
                        $append[] = array_merge($combo, [$item]);
                    }
                }
                $cartesian = $append;
            }

            $hasColorOption = $options->contains(fn($o) => in_array(strtolower(trim($o->name)), ['color', 'colour', 'shade', 'rang'], true));
            $optionMap = $options->keyBy('id');

            // Smart Base SKU deduction if not explicitly provided
            if (empty($baseSku)) {
                $existingSku = ProductVariant::where('product_id', $product->id)
                    ->whereNotNull('sku')
                    ->where('sku', '!=', '')
                    ->value('sku');

                if ($existingSku) {
                    // Strip trailing standard size or number slug (e.g. -S, -M, -XL, -XXL, -FREE, etc.)
                    $cleaned = preg_replace('/-(XXS|XS|S|M|L|XL|XXL|2XL|3XL|4XL|5XL|FREE|OS|\d+)$/i', '', $existingSku);

                    // If product has a Color option, also check if $cleaned ends with a color code/name and strip it from the base
                    if ($hasColorOption) {
                        $colorCodesPattern = '(BLK|WHT|RED|BLU|GRN|YLW|PNK|MRN|PPL|PCH|ORG|BEG|OFWHT|GRE|BRW|PRGRN|MGT|[A-Z]{3,4})';
                        $cleanedWithoutColor = preg_replace('/-' . $colorCodesPattern . '$/i', '', $cleaned);
                        if ($cleanedWithoutColor !== '' && $cleanedWithoutColor !== $cleaned) {
                            $cleaned = $cleanedWithoutColor;
                        }
                    }

                    $base = strtoupper(trim((string) $cleaned));
                } else {
                    $base = strtoupper(Str::slug($product->name, ''));
                }
            } else {
                $base = strtoupper(trim($baseSku));
            }

            // Find any unassigned live variant(s) on the product (empty option_signature or no option values)
            $unassignedVariants = ProductVariant::where('product_id', $product->id)
                ->where(function ($q) {
                    $q->whereNull('option_signature')->orWhere('option_signature', '');
                })
                ->whereNull('archived_at')
                ->get();

            $createdVariants = [];

            foreach ($cartesian as $combo) {
                $valueIds = array_map(fn($v) => (int) $v->id, $combo);
                $sig = self::signature($valueIds);

                // Skip if a live variant already has this exact option signature
                $exists = ProductVariant::where('product_id', $product->id)
                    ->where('option_signature', $sig)
                    ->whereNull('archived_at')
                    ->exists();
                if ($exists) {
                    continue;
                }

                // Categorize option values to enforce standard SKU structure:
                // [Base]-[OtherOptions]-[ColorCode]-[Size]
                // (Size is always the last slug; Color code is immediately before Size)
                $colorVal = null;
                $sizeVal = null;
                $otherVals = [];

                foreach ($combo as $optVal) {
                    $parentOpt = $optionMap->get($optVal->option_id);
                    $optName = strtolower(trim($parentOpt?->name ?? ''));
                    if (in_array($optName, ['color', 'colour', 'shade', 'rang'], true)) {
                        $colorVal = $optVal;
                    } elseif (in_array($optName, ['size', 'sizes', 'size (uk)', 'size (us)', 'fit'], true)) {
                        $sizeVal = $optVal;
                    } else {
                        $otherVals[] = $optVal;
                    }
                }

                $tokens = [];
                foreach ($otherVals as $ov) {
                    $tokens[] = strtoupper(Str::slug($ov->value, ''));
                }
                if ($colorVal !== null) {
                    $tokens[] = self::resolveColorCode($colorVal->value);
                }
                if ($sizeVal !== null) {
                    $tokens[] = strtoupper(Str::slug($sizeVal->value, ''));
                }

                // Construct clean SKU candidate, avoiding duplicate slugs already present in $base
                $filteredTokens = [];
                $checkBase = $base;
                foreach ($tokens as $t) {
                    if (str_ends_with($checkBase, '-' . $t) || str_contains($checkBase, '-' . $t . '-')) {
                        continue;
                    }
                    $filteredTokens[] = $t;
                    $checkBase .= '-' . $t;
                }
                $skuCandidate = empty($filteredTokens) ? $base : ($base . '-' . implode('-', $filteredTokens));

                // Check if an unassigned variant matches this combo
                $matchedUnassigned = null;
                if ($unassignedVariants->isNotEmpty()) {
                    // 1. Exact SKU match
                    $matchedUnassigned = $unassignedVariants->first(fn($uv) => strtoupper($uv->sku) === $skuCandidate);

                    // 2. If both color and size exist, check if SKU contains color code AND ends with size
                    if (!$matchedUnassigned && $colorVal && $sizeVal) {
                        $cCode = self::resolveColorCode($colorVal->value);
                        $sSlug = strtoupper(Str::slug($sizeVal->value, ''));
                        $matchedUnassigned = $unassignedVariants->first(function ($uv) use ($cCode, $sSlug) {
                            $sku = strtoupper($uv->sku);
                            return (str_contains($sku, '-' . $cCode) || str_contains($sku, $cCode))
                                && str_ends_with($sku, '-' . $sSlug);
                        });
                    }

                    // 3. Check if unassigned SKU ends with the size slug or any token from this combo
                    if (!$matchedUnassigned && !empty($filteredTokens)) {
                        $matchedUnassigned = $unassignedVariants->first(function ($uv) use ($filteredTokens) {
                            foreach ($filteredTokens as $t) {
                                if (str_ends_with(strtoupper($uv->sku), '-' . $t)) {
                                    return true;
                                }
                            }
                            return false;
                        });
                    }

                    // 4. If only 1 unassigned variant exists and total combinations equals 1, adopt it
                    if (!$matchedUnassigned && $unassignedVariants->count() === 1 && count($cartesian) === 1) {
                        $matchedUnassigned = $unassignedVariants->first();
                    }
                }

                if ($matchedUnassigned) {
                    // Adopt this existing variant!
                    // Link option values
                    $pivotData = [];
                    $optionValues = ProductOptionValue::whereIn('id', $valueIds)->get();
                    foreach ($optionValues as $ov) {
                        $pivotData[] = [
                            'variant_id' => $matchedUnassigned->id,
                            'option_id' => $ov->option_id,
                            'option_value_id' => $ov->id,
                        ];
                    }
                    DB::table('product_variant_option_values')->where('variant_id', $matchedUnassigned->id)->delete();
                    DB::table('product_variant_option_values')->insert($pivotData);

                    // If its SKU was empty or mismatched, update it to skuCandidate if available
                    $newSku = $matchedUnassigned->sku;
                    if (empty($newSku) || $newSku !== $skuCandidate) {
                        if (!ProductVariant::where('sku', $skuCandidate)->where('id', '!=', $matchedUnassigned->id)->exists()) {
                            $newSku = $skuCandidate;
                        }
                    }

                    $matchedUnassigned->update([
                        'option_signature' => $sig,
                        'sku' => $newSku,
                    ]);

                    // Remove from unassigned pool
                    $unassignedVariants = $unassignedVariants->reject(fn($v) => $v->id === $matchedUnassigned->id);

                    if ($defaultStock > 0) {
                        $defaultLocation = InventoryLocation::where('is_default', true)->first()
                            ?? InventoryLocation::firstOrCreate(
                                ['code' => 'MAIN'],
                                ['name' => 'Main location', 'is_active' => true, 'is_default' => true]
                            );
                        InventoryItem::firstOrCreate(
                            ['variant_id' => $matchedUnassigned->id],
                            ['backorder_policy' => BackorderPolicy::STOP_SALE]
                        );
                        $level = InventoryLevel::firstOrCreate(
                            ['variant_id' => $matchedUnassigned->id, 'location_id' => $defaultLocation->id],
                            ['on_hand' => 0, 'reserved' => 0]
                        );
                        if ($level->on_hand === 0) {
                            $level->update(['on_hand' => $defaultStock]);
                        }
                    }

                    $createdVariants[] = $matchedUnassigned->fresh();
                    continue;
                }

                // Ensure unique SKU across catalog
                $finalSku = $skuCandidate;
                $attempt = 1;
                while (ProductVariant::where('sku', $finalSku)->exists()) {
                    $attempt++;
                    $finalSku = $skuCandidate . '-' . $attempt;
                }

                $createdVariants[] = $this->createVariant($product, [
                    'sku' => $finalSku,
                    'price_rupees' => $defaultPriceRupees,
                    'mrp_rupees' => $defaultMrpRupees,
                    'option_value_ids' => $valueIds,
                    'stock_quantity' => $defaultStock,
                ], $adminUserId, $request);
            }

            return $createdVariants;
        });
    }
}
