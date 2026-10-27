<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductImageOptionValue;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\WholesaleColorCode;
use App\Services\Catalog\ProductVariantService;
use App\Services\Storefront\CatalogBrowsingService;
use Tests\TestCase;

class ColorSplitListingAndColorCodeSkuTest extends TestCase
{
    private array $cleanupProductIds = [];
    private array $cleanupCategoryIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        InventoryLocation::firstOrCreate(
            ['is_default' => true],
            ['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]
        );

        $existingTestVars = ProductVariant::where('sku', 'like', 'DSICF-S3-27102603%')->pluck('id')->all();
        if (!empty($existingTestVars)) {
            \App\Models\InventoryLevel::whereIn('variant_id', $existingTestVars)->delete();
            \App\Models\InventoryItem::whereIn('variant_id', $existingTestVars)->delete();
            \Illuminate\Support\Facades\DB::table('product_variant_option_values')->whereIn('variant_id', $existingTestVars)->delete();
            ProductVariant::whereIn('id', $existingTestVars)->delete();
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->cleanupProductIds)) {
            $variantIds = ProductVariant::whereIn('product_id', $this->cleanupProductIds)->pluck('id')->all();
            if (!empty($variantIds)) {
                \App\Models\InventoryLevel::whereIn('variant_id', $variantIds)->delete();
                \App\Models\InventoryItem::whereIn('variant_id', $variantIds)->delete();
                \Illuminate\Support\Facades\DB::table('product_variant_option_values')->whereIn('variant_id', $variantIds)->delete();
                \Illuminate\Support\Facades\DB::table('product_image_variants')->whereIn('variant_id', $variantIds)->delete();
            }

            $imageIds = ProductImage::whereIn('product_id', $this->cleanupProductIds)->pluck('id')->all();
            if (!empty($imageIds)) {
                ProductImageOptionValue::whereIn('image_id', $imageIds)->delete();
            }

            ProductVariant::whereIn('product_id', $this->cleanupProductIds)->delete();
            ProductOption::whereIn('product_id', $this->cleanupProductIds)->delete();
            ProductImage::whereIn('product_id', $this->cleanupProductIds)->delete();
            Product::whereIn('id', $this->cleanupProductIds)->delete();
        }

        if (!empty($this->cleanupCategoryIds)) {
            Category::whereIn('id', $this->cleanupCategoryIds)->delete();
        }

        parent::tearDown();
    }

    public function test_standard_wholesale_color_codes_are_resolved(): void
    {
        $this->assertEquals('PNK', WholesaleColorCode::resolveCode('Pink'));
        $this->assertEquals('BLU', WholesaleColorCode::resolveCode('Blue'));
        $this->assertEquals('BRW', WholesaleColorCode::resolveCode('Brown'));
        $this->assertEquals('YLW', WholesaleColorCode::resolveCode('Yellow'));
        $this->assertEquals('RED', WholesaleColorCode::resolveCode('Red'));
        $this->assertEquals('BLK', WholesaleColorCode::resolveCode('Black'));
        $this->assertEquals('WHT', WholesaleColorCode::resolveCode('White'));
        $this->assertEquals('OFWHT', WholesaleColorCode::resolveCode('Off White'));
        $this->assertEquals('PRGRN', WholesaleColorCode::resolveCode('Parrot Green'));
    }

    public function test_variant_matrix_generates_standard_color_code_and_size_ordered_skus(): void
    {
        $category = Category::where('slug', 'women-fashion')->first() ?? Category::create([
            'name' => 'Women Fashion',
            'slug' => 'women-fashion-' . uniqid(),
            'is_visible' => true,
        ]);
        if (!Category::where('slug', 'women-fashion')->exists()) {
            $this->cleanupCategoryIds[] = $category->id;
        }

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Festive Chanderi Silk Kurta Set ' . uniqid(),
            'slug' => 'festive-chanderi-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'description' => 'Test Chanderi set',
        ]);
        $this->cleanupProductIds[] = $product->id;

        $colorOption = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Color',
            'position' => 0,
        ]);
        $valPink = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Pink', 'position' => 0]);
        $valBlue = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);
        $valBrown = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Brown', 'position' => 2]);

        $sizeOption = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Size',
            'position' => 1,
        ]);
        $valM = ProductOptionValue::create(['option_id' => $sizeOption->id, 'value' => 'M', 'position' => 0]);
        $valL = ProductOptionValue::create(['option_id' => $sizeOption->id, 'value' => 'L', 'position' => 1]);

        // Default initial variant on product
        $initialVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'DSICF-S3-27102603-PNK-L',
            'option_signature' => '',
            'price_paise' => 149900,
            'mrp_paise' => 299900,
            'position' => 0,
            'version' => 0,
        ]);

        $service = app(ProductVariantService::class);
        $generated = $service->generateVariantMatrix(
            product: $product,
            defaultPriceRupees: 1499.00,
            defaultMrpRupees: 2999.00
        );

        // 3 colors x 2 sizes = 6 combinations
        $this->assertCount(6, $generated);

        $skus = collect($generated)->pluck('sku')->all();
        $this->assertContains('DSICF-S3-27102603-PNK-M', $skus);
        $this->assertContains('DSICF-S3-27102603-PNK-L', $skus);
        $this->assertContains('DSICF-S3-27102603-BLU-M', $skus);
        $this->assertContains('DSICF-S3-27102603-BLU-L', $skus);
        $this->assertContains('DSICF-S3-27102603-BRW-M', $skus);
        $this->assertContains('DSICF-S3-27102603-BRW-L', $skus);

        // Initial variant was adopted with matching signature
        $initialVariant->refresh();
        $this->assertEquals(ProductVariantService::signature([$valPink->id, $valL->id]), $initialVariant->option_signature);
    }

    public function test_multi_color_product_expands_to_separate_listing_cards_with_color_images(): void
    {
        $category = Category::where('slug', 'kurta-set')->first() ?? Category::create([
            'name' => 'Kurta Set',
            'slug' => 'kurta-set-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cotton Printed Anarkali Suit ' . uniqid(),
            'slug' => 'cotton-printed-anarkali-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'description' => 'Test suit',
        ]);
        $this->cleanupProductIds[] = $product->id;

        $colorOption = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Color',
            'position' => 0,
        ]);
        $valBlue = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 0]);
        $valYellow = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Yellow', 'position' => 1]);

        $imgBlue = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/blue.webp',
            'alt_text' => 'Blue Anarkali Suit',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 50000,
        ]);
        ProductImageOptionValue::create(['image_id' => $imgBlue->id, 'option_value_id' => $valBlue->id]);

        $imgYellow = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/yellow.webp',
            'alt_text' => 'Yellow Anarkali Suit',
            'position' => 1,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 50000,
        ]);
        ProductImageOptionValue::create(['image_id' => $imgYellow->id, 'option_value_id' => $valYellow->id]);

        $varBlue = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SUIT-BLU-' . uniqid(),
            'price_paise' => 129900,
            'mrp_paise' => 249900,
            'option_signature' => (string) $valBlue->id,
            'position' => 0,
            'version' => 0,
        ]);
        $varBlue->optionValues()->attach($valBlue->id, ['option_id' => $colorOption->id]);

        $varYellow = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SUIT-YLW-' . uniqid(),
            'price_paise' => 139900,
            'mrp_paise' => 249900,
            'option_signature' => (string) $valYellow->id,
            'position' => 1,
            'version' => 0,
        ]);
        $varYellow->optionValues()->attach($valYellow->id, ['option_id' => $colorOption->id]);

        $product->load([
            'options.values',
            'variants.optionValues',
            'variants.inventoryLevels',
            'images.productImageOptionValues',
            'images.productImageVariants',
        ]);

        $browsingService = app(CatalogBrowsingService::class);
        $cards = $browsingService->expandProductsForListing([$product]);

        $this->assertCount(2, $cards);

        $blueCard = $cards->firstWhere('color_name', 'Blue');
        $this->assertNotNull($blueCard);
        $this->assertStringContainsString('blue.webp', $blueCard->primary_image_url);
        $this->assertStringContainsString('color=Blue', $blueCard->url);
        $this->assertEquals('BLU', $blueCard->color_code);

        $yellowCard = $cards->firstWhere('color_name', 'Yellow');
        $this->assertNotNull($yellowCard);
        $this->assertStringContainsString('yellow.webp', $yellowCard->primary_image_url);
        $this->assertStringContainsString('color=Yellow', $yellowCard->url);
        $this->assertEquals('YLW', $yellowCard->color_code);
    }

    public function test_category_page_displays_color_split_product_cards(): void
    {
        $category = Category::where('slug', 'kurta-set')->first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Kurta Set',
                'slug' => 'kurta-set',
                'is_visible' => true,
            ]);
            $this->cleanupCategoryIds[] = $category->id;
        }

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cotton Straight Kurta ' . uniqid(),
            'slug' => 'cotton-straight-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'description' => 'Test cotton straight kurta',
        ]);
        $this->cleanupProductIds[] = $product->id;

        $colorOption = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Color',
            'position' => 0,
        ]);
        $valPink = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Pink', 'position' => 0]);
        $valBlue = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

        $varPink = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KT-PNK-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 199900,
            'option_signature' => (string) $valPink->id,
            'position' => 0,
            'version' => 0,
        ]);
        $varPink->optionValues()->attach($valPink->id, ['option_id' => $colorOption->id]);

        $varBlue = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KT-BLU-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 199900,
            'option_signature' => (string) $valBlue->id,
            'position' => 1,
            'version' => 0,
        ]);
        $varBlue->optionValues()->attach($valBlue->id, ['option_id' => $colorOption->id]);

        $response = $this->get('/kurta-set');
        $response->assertStatus(200);
        $response->assertSee('color=Pink');
        $response->assertSee('color=Blue');
    }

    public function test_product_detail_page_auto_selects_color_from_query_parameter(): void
    {
        $category = Category::first();

        $product = Product::create([
            'category_id' => $category?->id,
            'name' => 'Printed Anarkali Kurta ' . uniqid(),
            'slug' => 'printed-anarkali-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'description' => 'Test anarkali kurta',
        ]);
        $this->cleanupProductIds[] = $product->id;

        $colorOption = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Color',
            'position' => 0,
        ]);
        $valPink = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Pink', 'position' => 0]);
        $valBlue = ProductOptionValue::create(['option_id' => $colorOption->id, 'value' => 'Blue', 'position' => 1]);

        $sizeOption = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Size',
            'position' => 1,
        ]);
        $valL = ProductOptionValue::create(['option_id' => $sizeOption->id, 'value' => 'L', 'position' => 0]);

        $varPink = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'ANR-PNK-' . uniqid(),
            'price_paise' => 129900,
            'mrp_paise' => 249900,
            'option_signature' => ProductVariantService::signature([$valPink->id, $valL->id]),
            'position' => 0,
            'version' => 0,
        ]);
        $varPink->optionValues()->attach($valPink->id, ['option_id' => $colorOption->id]);
        $varPink->optionValues()->attach($valL->id, ['option_id' => $sizeOption->id]);

        $varBlue = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'ANR-BLU-' . uniqid(),
            'price_paise' => 129900,
            'mrp_paise' => 249900,
            'option_signature' => ProductVariantService::signature([$valBlue->id, $valL->id]),
            'position' => 1,
            'version' => 0,
        ]);
        $varBlue->optionValues()->attach($valBlue->id, ['option_id' => $colorOption->id]);
        $varBlue->optionValues()->attach($valL->id, ['option_id' => $sizeOption->id]);

        // Request with ?color=Blue
        $response = $this->get('/product/' . $product->slug . '?color=Blue');
        $response->assertStatus(200);
        $response->assertSee("selectedColor: 'Blue'", false);
    }
}
