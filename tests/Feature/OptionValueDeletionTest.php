<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantService;
use Tests\TestCase;

class OptionValueDeletionTest extends TestCase
{
    public function test_sync_options_permanently_removes_deleted_option_values(): void
    {
        $product = Product::create([
            'name' => 'Kurti Option Sync Test ' . uniqid(),
            'slug' => 'kurti-option-sync-' . uniqid(),
            'status' => \App\Enums\ProductStatus::DRAFT,
        ]);

        $variantService = app(ProductVariantService::class);

        // Initial sync with Blue, Red, Yellow
        $variantService->syncOptions($product, [
            ['name' => 'Color', 'values' => ['Blue', 'Red', 'Yellow']],
            ['name' => 'Size', 'values' => ['M', 'L']],
        ]);

        $product->refresh();
        $colorOption = $product->options->firstWhere('name', 'Color');
        $this->assertNotNull($colorOption);
        $this->assertCount(3, $colorOption->values);
        $this->assertTrue($colorOption->values->contains('value', 'Red'));

        // Admin removes 'Red'
        $variantService->syncOptions($product, [
            ['name' => 'Color', 'values' => ['Blue', 'Yellow']],
            ['name' => 'Size', 'values' => ['M', 'L']],
        ]);

        $product->refresh();
        $colorOption->refresh();
        $values = $colorOption->values()->pluck('value')->all();

        $this->assertContains('Blue', $values);
        $this->assertContains('Yellow', $values);
        $this->assertNotContains('Red', $values);
        $this->assertCount(2, $values);

        // Verify from database directly
        $this->assertFalse(
            ProductOptionValue::where('option_id', $colorOption->id)->where('value', 'Red')->exists()
        );
    }
}
