<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Services\Shipping\DelhiveryService;
use App\Services\Storefront\CatalogBrowsingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    /**
     * Display the digital product catalogue for women's ethnic fashion.
     */
    public function index(Request $request): View
    {
        $store = StoreSettings::current();
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) ($store->support_phone ?? '919887776777'));
        if (!str_starts_with($cleanPhone, '91') && strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        // Fetch all active women's fashion products
        $allProducts = Product::query()
            ->where('status', 'ACTIVE')
            ->whereHas('category', function ($q) {
                $q->whereNotIn('slug', CatalogBrowsingService::SMART_LIVING_SLUGS);
            })
            ->with([
                'category',
                'options.values',
                'variants.optionValues.option',
                'variants.inventoryLevels',
                'images',
                'videos',
            ])
            ->orderByDesc('id')
            ->get();

        // Build catalogue items by expanding multi-color products so every color is showcased
        $catalogueItems = collect();

        foreach ($allProducts as $product) {
            $liveVariants = $product->variants->whereNull('archived_at');
            $colorOption = $product->options->first(fn($o) => in_array(strtolower(trim((string) $o->name)), ['color', 'colour', 'shade', 'rang'], true));
            $sizeOption = $product->options->first(fn($o) => in_array(strtolower(trim((string) $o->name)), ['size', 'sizes'], true));

            // List all colors for parent product swatches
            $allProductColors = [];
            if ($colorOption) {
                foreach ($colorOption->values as $cVal) {
                    $cName = trim((string) $cVal->value);
                    if ($cName !== '') {
                        $allProductColors[] = [
                            'name' => $cName,
                            'hex' => \App\Models\WholesaleColorCode::resolveHex($cName) ?: $this->resolveColorHex($cName),
                        ];
                    }
                }
            }

            $colorCardsGenerated = 0;

            if ($colorOption && $colorOption->values->isNotEmpty()) {
                foreach ($colorOption->values as $cVal) {
                    $colorName = trim((string) $cVal->value);
                    if ($colorName === '') {
                        continue;
                    }

                    // Variants matching this color
                    $varsForColor = $liveVariants->filter(fn($v) => $v->optionValues->contains('id', $cVal->id));

                    // Fallback to SKU matching for legacy variants
                    if ($varsForColor->isEmpty()) {
                        $colorCode = \App\Models\WholesaleColorCode::resolveCode($colorName);
                        $varsForColor = $liveVariants->filter(function ($v) use ($colorCode, $colorName) {
                            $sku = strtoupper((string) $v->sku);
                            return str_contains($sku, '-' . $colorCode) || str_contains($sku, strtoupper($colorName));
                        });
                    }

                    if ($varsForColor->isEmpty()) {
                        continue;
                    }

                    // Find gallery images specifically assigned to this color
                    $varIds = $varsForColor->pluck('id')->all();
                    $colorImageModels = $product->images->filter(function ($img) use ($cVal, $varIds) {
                        return $img->productImageOptionValues->contains('option_value_id', $cVal->id)
                            || $img->productImageVariants->whereIn('variant_id', $varIds)->isNotEmpty();
                    })->sortBy('position');

                    $images = $colorImageModels->pluck('url')->filter()->values()->all();
                    if (empty($images)) {
                        $images = $product->images->sortBy('position')->pluck('url')->filter()->values()->all();
                        if (empty($images) && $product->primary_image_url) {
                            $images = [$product->primary_image_url];
                        }
                    }

                    // Sizes available for this color
                    $sizes = [];
                    if ($sizeOption) {
                        foreach ($sizeOption->values as $sVal) {
                            $sName = trim((string) $sVal->value);
                            $var = $varsForColor->first(fn($v) => $v->optionValues->contains('id', $sVal->id));
                            if (!$var) {
                                $var = $varsForColor->first(fn($v) => str_ends_with(strtoupper((string) $v->sku), '-' . strtoupper($sName)));
                            }
                            $stock = $var ? ($var->inventoryLevels->sum('on_hand') - $var->inventoryLevels->sum('reserved')) : 0;
                            $sizes[] = [
                                'name' => $sName,
                                'variant_id' => $var?->id,
                                'in_stock' => $stock > 0,
                                'price_rupees' => $var ? (float) ($var->price_paise / 100) : (float) (($varsForColor->min('price_paise') ?: 0) / 100),
                            ];
                        }
                    } else {
                        foreach ($varsForColor as $var) {
                            $stock = $var->inventoryLevels->sum('on_hand') - $var->inventoryLevels->sum('reserved');
                            $sizes[] = [
                                'name' => $var->option_signature ?: 'Standard',
                                'variant_id' => $var->id,
                                'in_stock' => $stock > 0,
                                'price_rupees' => (float) ($var->price_paise / 100),
                            ];
                        }
                    }

                    // Pricing for this color
                    $minPricePaise = $varsForColor->where('price_paise', '>', 0)->min('price_paise') ?: $liveVariants->min('price_paise') ?: $product->price_paise ?: 0;
                    $maxMrpPaise = $varsForColor->where('mrp_paise', '>', 0)->max('mrp_paise') ?: $liveVariants->max('mrp_paise') ?: $product->mrp_paise ?: $minPricePaise;
                    $minPriceRupees = (float) ($minPricePaise / 100);
                    $maxMrpRupees = (float) ($maxMrpPaise / 100);

                    $discountPct = ($maxMrpRupees > $minPriceRupees)
                        ? (int) round((($maxMrpRupees - $minPriceRupees) / $maxMrpRupees) * 100)
                        : 0;

                    // Default variant for 1-click Buy Now (first in-stock variant)
                    $defaultVar = $varsForColor->first(function ($v) {
                        $stock = $v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved');
                        return $stock > 0;
                    }) ?? $varsForColor->first();

                    $productUrl = url('/product/' . $product->slug . '?color=' . urlencode($colorName));
                    $colorHex = \App\Models\WholesaleColorCode::resolveHex($colorName) ?: $this->resolveColorHex($colorName);

                    // WhatsApp Direct Order Message with color
                    $waText = "Namaste Dhansree! 🌸\n\n"
                        . "I would like to order this item from your catalogue:\n\n"
                        . "🛍️ *" . $product->name . " (" . $colorName . ")*\n"
                        . "🎨 *Color:* " . $colorName . "\n"
                        . "💰 *Price:* ₹" . number_format($minPriceRupees, 2)
                        . ($maxMrpRupees > $minPriceRupees ? " (MRP: ₹" . number_format($maxMrpRupees, 2) . " • {$discountPct}% OFF)" : "") . "\n"
                        . "🚚 *Delivery:* Free Home Delivery | Cash on Delivery (COD) Available\n"
                        . "🔗 *Product Link:* " . $productUrl . "\n\n"
                        . "Please confirm size availability and how to place the order.";

                    $waUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode($waText);

                    $colorVideo = $product->getApprovedVideoForColor($colorName);

                    $catalogueItems->push([
                        'id' => $product->id . '-' . \Illuminate\Support\Str::slug($colorName),
                        'product_id' => $product->id,
                        'sku' => $defaultVar?->sku ?: $product->sku,
                        'name' => $product->name,
                        'color_name' => $colorName,
                        'color_hex' => $colorHex,
                        'slug' => $product->slug,
                        'url' => $productUrl,
                        'brand' => $product->brand ?: 'DHANSREE TRENDS',
                        'category_name' => $product->category?->name ?: 'Women Ethnic Wear',
                        'category_slug' => $product->category?->slug ?: 'women-fashion',
                        'price_rupees' => $minPriceRupees,
                        'mrp_rupees' => $maxMrpRupees,
                        'formatted_price' => '₹' . number_format($minPriceRupees, 2),
                        'formatted_mrp' => $maxMrpRupees > $minPriceRupees ? '₹' . number_format($maxMrpRupees, 2) : null,
                        'discount_percentage' => $discountPct,
                        'images' => $images,
                        'primary_image_url' => $images[0] ?? $product->primary_image_url ?? asset('images/placeholder.webp'),
                        'video_url' => $colorVideo?->video_url ?? $product->approved_video?->video_url,
                        'webm_url' => $colorVideo?->webm_url ?? $product->approved_video?->webm_url,
                        'sizes' => $sizes,
                        'colors' => $allProductColors,
                        'default_variant_id' => $defaultVar?->id,
                        'in_stock' => $varsForColor->some(function ($v) {
                            return ($v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved')) > 0;
                        }),
                        'delivery' => [
                            'free_shipping' => true,
                            'cod_available' => true,
                            'estimated_days' => '3–5 Business Days',
                            'courier' => 'Delhivery Express',
                            'dispatch' => 'Dispatched within 24–48 Hours',
                            'returns' => '7-Day Easy Exchange & Return Guarantee',
                        ],
                        'whatsapp_url' => $waUrl,
                    ]);

                    $colorCardsGenerated++;
                }
            }

            // Fallback for single-color products or products without color options
            if ($colorCardsGenerated === 0) {
                $images = $product->images->sortBy('position')->pluck('url')->filter()->values()->all();
                if (empty($images) && $product->primary_image_url) {
                    $images = [$product->primary_image_url];
                }

                $sizes = [];
                if ($sizeOption) {
                    foreach ($sizeOption->values as $sVal) {
                        $sName = trim((string) $sVal->value);
                        $var = $liveVariants->first(fn($v) => $v->optionValues->contains('id', $sVal->id));
                        if (!$var) {
                            $var = $liveVariants->first(fn($v) => str_ends_with(strtoupper((string) $v->sku), '-' . strtoupper($sName)));
                        }
                        $stock = $var ? ($var->inventoryLevels->sum('on_hand') - $var->inventoryLevels->sum('reserved')) : 0;
                        $sizes[] = [
                            'name' => $sName,
                            'variant_id' => $var?->id,
                            'in_stock' => $stock > 0,
                            'price_rupees' => $var ? (float) ($var->price_paise / 100) : (float) (($liveVariants->min('price_paise') ?: 0) / 100),
                        ];
                    }
                } else {
                    foreach ($liveVariants as $var) {
                        $stock = $var->inventoryLevels->sum('on_hand') - $var->inventoryLevels->sum('reserved');
                        $sizes[] = [
                            'name' => $var->option_signature ?: 'Standard',
                            'variant_id' => $var->id,
                            'in_stock' => $stock > 0,
                            'price_rupees' => (float) ($var->price_paise / 100),
                        ];
                    }
                }

                $minPricePaise = $liveVariants->min('price_paise') ?: $product->price_paise ?: 0;
                $maxMrpPaise = $liveVariants->max('mrp_paise') ?: $product->mrp_paise ?: $minPricePaise;
                $minPriceRupees = (float) ($minPricePaise / 100);
                $maxMrpRupees = (float) ($maxMrpPaise / 100);

                $discountPct = ($maxMrpRupees > $minPriceRupees)
                    ? (int) round((($maxMrpRupees - $minPriceRupees) / $maxMrpRupees) * 100)
                    : 0;

                $defaultVar = $liveVariants->first(function ($v) {
                    $stock = $v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved');
                    return $stock > 0;
                }) ?? $liveVariants->first();

                $productUrl = url('/product/' . $product->slug);

                $waText = "Namaste Dhansree! 🌸\n\n"
                    . "I would like to order this item from your catalogue:\n\n"
                    . "🛍️ *" . $product->name . "*\n"
                    . "💰 *Price:* ₹" . number_format($minPriceRupees, 2)
                    . ($maxMrpRupees > $minPriceRupees ? " (MRP: ₹" . number_format($maxMrpRupees, 2) . " • {$discountPct}% OFF)" : "") . "\n"
                    . "🚚 *Delivery:* Free Home Delivery | Cash on Delivery (COD) Available\n"
                    . "🔗 *Product Link:* " . $productUrl . "\n\n"
                    . "Please confirm size availability and how to place the order.";

                $waUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode($waText);

                $approvedVideo = $product->approved_video;

                $catalogueItems->push([
                    'id' => (string) $product->id,
                    'product_id' => $product->id,
                    'sku' => $defaultVar?->sku ?: $product->sku,
                    'name' => $product->name,
                    'color_name' => null,
                    'color_hex' => null,
                    'slug' => $product->slug,
                    'url' => $productUrl,
                    'brand' => $product->brand ?: 'DHANSREE TRENDS',
                    'category_name' => $product->category?->name ?: 'Women Ethnic Wear',
                    'category_slug' => $product->category?->slug ?: 'women-fashion',
                    'price_rupees' => $minPriceRupees,
                    'mrp_rupees' => $maxMrpRupees,
                    'formatted_price' => '₹' . number_format($minPriceRupees, 2),
                    'formatted_mrp' => $maxMrpRupees > $minPriceRupees ? '₹' . number_format($maxMrpRupees, 2) : null,
                    'discount_percentage' => $discountPct,
                    'images' => $images,
                    'primary_image_url' => $images[0] ?? $product->primary_image_url ?? asset('images/placeholder.webp'),
                    'video_url' => $approvedVideo?->video_url,
                    'webm_url' => $approvedVideo?->webm_url,
                    'sizes' => $sizes,
                    'colors' => $allProductColors,
                    'default_variant_id' => $defaultVar?->id,
                    'in_stock' => $liveVariants->some(function ($v) {
                        return ($v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved')) > 0;
                    }),
                    'delivery' => [
                        'free_shipping' => true,
                        'cod_available' => true,
                        'estimated_days' => '3–5 Business Days',
                        'courier' => 'Delhivery Express',
                        'dispatch' => 'Dispatched within 24–48 Hours',
                        'returns' => '7-Day Easy Exchange & Return Guarantee',
                    ],
                    'whatsapp_url' => $waUrl,
                ]);
            }
        }

        // Build category filter pills based on expanded catalogue items
        $categoryCounts = [];
        foreach ($catalogueItems as $item) {
            $catSlug = $item['category_slug'];
            $categoryCounts[$catSlug] = ($categoryCounts[$catSlug] ?? 0) + 1;
        }

        $categories = Category::query()
            ->whereIn('slug', array_keys($categoryCounts))
            ->orderBy('position', 'asc')
            ->get(['id', 'name', 'slug'])
            ->map(function ($cat) use ($categoryCounts) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'count' => $categoryCounts[$cat->slug] ?? 0,
                ];
            });

        // Store catalogue share URL & text for sharing
        $catalogueUrl = url('/catalogue');
        $shareCatalogueMsg = "Namaste! 🌸 Explore our latest Women's Fashion & Ethnic Wear Digital Catalogue:\n\n"
            . "✨ Pure Cotton Anarkali Sets, Designer Suits & Co-ord Sets\n"
            . "🏷️ Best Prices with upto 40% OFF\n"
            . "🚚 FREE Home Delivery across India\n"
            . "💵 Cash on Delivery (COD) Available\n"
            . "📦 3-5 Days Delivery via Delhivery Express\n\n"
            . "👉 View Complete Catalogue with Prices: " . $catalogueUrl;

        $shareCatalogueWaUrl = "https://wa.me/?text=" . rawurlencode($shareCatalogueMsg);

        return view('store.catalogue', [
            'store' => $store,
            'cleanPhone' => $cleanPhone,
            'categories' => $categories,
            'products' => $catalogueItems,
            'totalCount' => $catalogueItems->count(),
            'catalogueUrl' => $catalogueUrl,
            'shareCatalogueWaUrl' => $shareCatalogueMsg,
            'shareCatalogueUrl' => $shareCatalogueWaUrl,
            'seoTitle' => "Women's Ethnic Fashion Catalogue with Price & Delivery | Dhansree Trends",
            'seoDescription' => "Browse Dhansree's exclusive women's ethnic fashion catalogue. Handcrafted Jaipur Anarkali sets, kurta pant sets, and festive suits with prices, sizes M to 3XL, Free Delivery & Cash on Delivery (COD) all over India.",
        ]);
    }

    /**
     * Quick delivery check for delivery pincode.
     */
    public function checkPincode(Request $request, DelhiveryService $delhiveryService): JsonResponse
    {
        $validated = $request->validate([
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
        ]);

        $pincode = trim($validated['pincode']);

        try {
            $result = $delhiveryService->checkPincode($pincode);
            $serviceable = $result['serviceable'] ?? true;
            $cod = $result['cod'] ?? true;
            $city = $result['city'] ?? null;

            if ($serviceable) {
                $location = $city ? "to {$city} ({$pincode})" : "to {$pincode}";
                return response()->json([
                    'success' => true,
                    'serviceable' => true,
                    'city' => $city,
                    'state_code' => $result['state_code'] ?? null,
                    'cod' => $cod,
                    'prepaid' => true,
                    'estimated_days' => '3–5 Days',
                    'courier' => 'Delhivery Express',
                    'message' => "🎉 Fast Delivery Available {$location} in 3 to 5 business days! Free Shipping & " . ($cod ? 'Cash on Delivery (COD) Available ✅' : 'Prepaid Available ✅'),
                ]);
            }

            return response()->json([
                'success' => true,
                'serviceable' => false,
                'message' => "Pincode {$pincode} is currently outside our direct courier coverage. Please contact our WhatsApp support at +91 98877 76777 for special delivery.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'serviceable' => true,
                'message' => "🎉 Delivery available to {$pincode} in 3–5 business days via Delhivery Express! Free Shipping & Cash on Delivery (COD) Available ✅",
            ]);
        }
    }

    /**
     * Map color name to hexadecimal swatch color.
     */
    protected function resolveColorHex(string $color): string
    {
        $map = [
            'pink' => '#f472b6',
            'white' => '#ffffff',
            'off-white' => '#faf7f2',
            'off white' => '#faf7f2',
            'blue' => '#3b82f6',
            'slate blue' => '#64748b',
            'yellow' => '#eab308',
            'lime green' => '#84cc16',
            'lime yellow' => '#facc15',
            'red' => '#dc2626',
            'green' => '#16a34a',
            'black' => '#0f172a',
            'maroon' => '#800000',
            'beige' => '#f5f5dc',
            'navy' => '#1e3a8a',
            'orange' => '#ea580c',
            'rust orange' => '#c2410c',
            'purple' => '#9333ea',
            'brown' => '#854d0e',
            'dark brown' => '#451a03',
            'grey' => '#9ca3af',
            'gray' => '#9ca3af',
            'teal' => '#0d9488',
            'olive' => '#65a30d',
            'gold' => '#d97706',
            'mustard yellow' => '#ca8a04',
            'mustard' => '#ca8a04',
        ];

        return $map[strtolower(trim($color))] ?? '#8b4513';
    }
}
