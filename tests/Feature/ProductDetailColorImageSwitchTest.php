<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\InventoryLocation;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductImageOptionValue;
use App\Services\Catalog\ProductVariantService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductDetailColorImageSwitchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_product_detail_page_switches_initial_image_and_gallery_based_on_color_param(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'womens-kurta-sets-test'],
            ['name' => "Women's Kurta Sets Test", 'position' => 1]
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cotton Printed Kurta Suit Set ' . uniqid(),
            'slug' => 'cotton-printed-suit-set-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $location = InventoryLocation::where('is_default', true)->first()
            ?? InventoryLocation::firstOrCreate(
                ['code' => 'MAIN-WH-TEST'],
                ['name' => 'Main Warehouse Test', 'is_active' => true, 'is_default' => false]
            );

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($product, [
            ['name' => 'Color', 'values' => ['Blue', 'Yellow', 'Black']],
            ['name' => 'Size', 'values' => ['M', 'L']],
        ]);

        $product->refresh();
        $colorOption = $product->options->firstWhere('name', 'Color');
        $blueVal = $colorOption->values->firstWhere('value', 'Blue');
        $yellowVal = $colorOption->values->firstWhere('value', 'Yellow');
        $blackVal = $colorOption->values->firstWhere('value', 'Black');
        $sizeOption = $product->options->firstWhere('name', 'Size');
        $mVal = $sizeOption->values->firstWhere('value', 'M');

        $blueVar = $variantService->createVariant($product, [
            'sku' => 'SUIT-BLU-M-' . uniqid(),
            'price_rupees' => 999.00,
            'mrp_rupees' => 1999.00,
            'option_value_ids' => [$blueVal->id, $mVal->id],
        ]);
        InventoryLevel::updateOrCreate(
            ['variant_id' => $blueVar->id, 'location_id' => $location->id],
            ['on_hand' => 10, 'reserved' => 0]
        );

        $yellowVar = $variantService->createVariant($product, [
            'sku' => 'SUIT-YLW-M-' . uniqid(),
            'price_rupees' => 999.00,
            'mrp_rupees' => 1999.00,
            'option_value_ids' => [$yellowVal->id, $mVal->id],
        ]);
        InventoryLevel::updateOrCreate(
            ['variant_id' => $yellowVar->id, 'location_id' => $location->id],
            ['on_hand' => 10, 'reserved' => 0]
        );

        $blackVar = $variantService->createVariant($product, [
            'sku' => 'SUIT-BLK-M-' . uniqid(),
            'price_rupees' => 999.00,
            'mrp_rupees' => 1999.00,
            'option_value_ids' => [$blackVal->id, $mVal->id],
        ]);
        InventoryLevel::updateOrCreate(
            ['variant_id' => $blackVar->id, 'location_id' => $location->id],
            ['on_hand' => 10, 'reserved' => 0]
        );

        // Create images:
        // img1: Blue (position 0 in DB)
        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/blue-suit.webp',
            'alt_text' => 'Blue Suit Image',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 100000,
        ]);
        ProductImageOptionValue::create([
            'image_id' => $img1->id,
            'option_value_id' => $blueVal->id,
        ]);

        // img2: Yellow (position 1 in DB)
        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/yellow-suit.webp',
            'alt_text' => 'Yellow Suit Image',
            'position' => 1,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 100000,
        ]);
        ProductImageOptionValue::create([
            'image_id' => $img2->id,
            'option_value_id' => $yellowVal->id,
        ]);

        // img3: Black (position 2 in DB)
        $img3 = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/black-suit.webp',
            'alt_text' => 'Black Suit Image',
            'position' => 2,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 100000,
        ]);
        ProductImageOptionValue::create([
            'image_id' => $img3->id,
            'option_value_id' => $blackVal->id,
        ]);

        // img4: Common unpinned image (position 3 in DB)
        $img4 = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/fabric-detail.webp',
            'alt_text' => 'Fabric Detail Common',
            'position' => 3,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 100000,
        ]);

        // 1. Visit with ?color=Black (e.g. from clicking Black suit on listing page)
        $responseBlack = $this->get('/product/' . $product->slug . '?color=Black');
        $responseBlack->assertOk();
        $contentBlack = $responseBlack->getContent();

        // Must initialize Alpine with selectedColor = Black
        $this->assertStringContainsString("selectedColor: 'Black'", $contentBlack);
        // Meta og:image and activeImage should use the Black suit image
        $this->assertStringContainsString('black-suit.webp', $contentBlack);
        $this->assertStringContainsString('<meta property="og:image" content="' . $img3->url . '">', $contentBlack);
        $this->assertStringContainsString("activeImage: '" . $img3->url . "'", $contentBlack);

        // 2. Visit with ?color=Yellow
        $responseYellow = $this->get('/product/' . $product->slug . '?color=Yellow');
        $responseYellow->assertOk();
        $contentYellow = $responseYellow->getContent();

        $this->assertStringContainsString("selectedColor: 'Yellow'", $contentYellow);
        $this->assertStringContainsString('<meta property="og:image" content="' . $img2->url . '">', $contentYellow);
        $this->assertStringContainsString("activeImage: '" . $img2->url . "'", $contentYellow);

        // 3. Visit without color param -> defaults to Blue
        $responseDefault = $this->get('/product/' . $product->slug);
        $responseDefault->assertOk();
        $contentDefault = $responseDefault->getContent();

        $this->assertStringContainsString("selectedColor: 'Blue'", $contentDefault);
        $this->assertStringContainsString('<meta property="og:image" content="' . $img1->url . '">', $contentDefault);
        $this->assertStringContainsString("activeImage: '" . $img1->url . "'", $contentDefault);

        // 4. Verify that Alpine data includes visibleImages logic and allImages with pinned colors
        $this->assertStringContainsString('visibleImages', $contentDefault);
        $this->assertStringContainsString('allImages:', $contentDefault);
        $this->assertStringContainsString('selectColor(colorName)', $contentDefault);

        // 5. Case-insensitivity test with lowercase ?color=black
        $responseLower = $this->get('/product/' . $product->slug . '?color=black');
        $responseLower->assertOk();
        $this->assertStringContainsString("selectedColor: 'Black'", $responseLower->getContent());
        $this->assertStringContainsString('<meta property="og:image" content="' . $img3->url . '">', $responseLower->getContent());
    }

    public function test_image_pinned_via_variant_is_assigned_to_color_and_selected(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'womens-suit-sets-variant-test'],
            ['name' => "Women's Suit Sets Variant Test", 'position' => 2]
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Chanderi Silk Suit ' . uniqid(),
            'slug' => 'chanderi-silk-suit-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($product, [
            ['name' => 'Color', 'values' => ['Green', 'Maroon']],
            ['name' => 'Size', 'values' => ['Free Size']],
        ]);

        $product->refresh();
        $greenVal = $product->options->firstWhere('name', 'Color')->values->firstWhere('value', 'Green');
        $maroonVal = $product->options->firstWhere('name', 'Color')->values->firstWhere('value', 'Maroon');
        $freeSizeVal = $product->options->firstWhere('name', 'Size')->values->firstWhere('value', 'Free Size');

        $greenVar = $variantService->createVariant($product, [
            'sku' => 'CHAND-GRN-FS-' . uniqid(),
            'price_rupees' => 1499.00,
            'mrp_rupees' => 2499.00,
            'option_value_ids' => [$greenVal->id, $freeSizeVal->id],
        ]);

        $maroonVar = $variantService->createVariant($product, [
            'sku' => 'CHAND-MRN-FS-' . uniqid(),
            'price_rupees' => 1499.00,
            'mrp_rupees' => 2499.00,
            'option_value_ids' => [$maroonVal->id, $freeSizeVal->id],
        ]);

        // Image 1: Pinned to Green variant via product_image_variants
        $imgGreen = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/green-chanderi.webp',
            'alt_text' => 'Green Chanderi',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 100000,
        ]);
        \App\Models\ProductImageVariant::create([
            'image_id' => $imgGreen->id,
            'variant_id' => $greenVar->id,
        ]);

        // Image 2: Pinned to Maroon variant via product_image_variants
        $imgMaroon = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/maroon-chanderi.webp',
            'alt_text' => 'Maroon Chanderi',
            'position' => 1,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 100000,
        ]);
        \App\Models\ProductImageVariant::create([
            'image_id' => $imgMaroon->id,
            'variant_id' => $maroonVar->id,
        ]);

        // When visiting ?color=Maroon
        $response = $this->get('/product/' . $product->slug . '?color=Maroon');
        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString("selectedColor: 'Maroon'", $content);
        $this->assertStringContainsString('<meta property="og:image" content="' . $imgMaroon->url . '">', $content);
        $this->assertStringContainsString("activeImage: '" . $imgMaroon->url . "'", $content);
    }
}
