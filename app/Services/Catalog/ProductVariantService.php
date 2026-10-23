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
            if (isset($data['stock_quantity'])) {
                $defaultLocation = InventoryLocation::where('is_default', true)->first();
                if ($defaultLocation) {
                    $level = InventoryLevel::where('variant_id', $current->id)
                        ->where('location_id', $defaultLocation->id)
                        ->first();
                    $newQty = max(0, (int) $data['stock_quantity']);
                    if ($level) {
                        $level->update(['on_hand' => $newQty]);
                    } else {
                        InventoryLevel::create([
                            'variant_id' => $current->id,
                            'location_id' => $defaultLocation->id,
                            'on_hand' => $newQty,
                            'reserved' => 0,
                        ]);
                    }
                }
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
        ?Request $request = null
    ): array {
        return DB::transaction(function () use ($product, $defaultPriceRupees, $defaultMrpRupees, $baseSku, $adminUserId, $request) {
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

            $base = $baseSku ?: strtoupper(Str::slug($product->name, ''));
            $createdVariants = [];

            foreach ($cartesian as $combo) {
                $valueIds = array_map(fn($v) => (int) $v->id, $combo);
                $valueNames = array_map(fn($v) => Str::slug($v->value, ''), $combo);
                $sig = self::signature($valueIds);

                // Skip if live variant already has this combination
                $exists = ProductVariant::where('product_id', $product->id)
                    ->where('option_signature', $sig)
                    ->whereNull('archived_at')
                    ->exists();
                if ($exists) {
                    continue;
                }

                $skuCandidate = strtoupper($base . '-' . implode('-', $valueNames));
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
                    'stock_quantity' => 0,
                ], $adminUserId, $request);
            }

            return $createdVariants;
        });
    }
}
