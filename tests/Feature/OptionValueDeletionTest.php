<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OptionValueDeletionTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_sync_options_with_empty_array_removes_all_options_and_preserves_single_variant(): void
    {
        $product = Product::create([
            'name' => 'Gadget No Variation Test ' . uniqid(),
            'slug' => 'gadget-no-var-' . uniqid(),
            'status' => \App\Enums\ProductStatus::DRAFT,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-USB-NO-SERIAL-' . uniqid(),
            'price_paise' => 24900,
            'mrp_paise' => 89900,
            'option_signature' => '',
            'position' => 0,
            'version' => 0,
        ]);

        $variantService = app(ProductVariantService::class);

        // First configure an option
        $variantService->syncOptions($product, [
            ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
        ]);

        $this->assertEquals(1, $product->options()->count());

        // Now admin deletes the Size option row completely (passes empty array)
        $variantService->syncOptions($product, []);

        $product->refresh();
        $this->assertEquals(0, $product->options()->count());
        $this->assertEquals(0, ProductOption::where('product_id', $product->id)->count());
        $this->assertEquals(0, ProductOptionValue::whereHas('option', fn($q) => $q->where('product_id', $product->id))->count());

        // Ensure the single default variant remains active
        $activeVariants = $product->variants()->whereNull('archived_at')->get();
        $this->assertCount(1, $activeVariants);
        $this->assertEquals($variant->sku, $activeVariants->first()->sku);
    }

    public function test_edit_product_page_does_not_inject_default_size_when_product_has_no_options(): void
    {
        $superRole = \App\Models\AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $admin = \App\Models\AdminUser::create([
            'email' => 'admin_opt_test_' . uniqid() . '@dhansree.test',
            'name' => 'Test Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => \App\Enums\AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $admin->roles()->attach($superRole->id);

        $session = \App\Support\Auth\AdminSessionManager::createSession($admin);
        $adminToken = $session['token'];

        $product = Product::create([
            'name' => 'Smart Gadget Single Variant ' . uniqid(),
            'slug' => 'smart-gadget-' . uniqid(),
            'status' => \App\Enums\ProductStatus::DRAFT,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-GADGET-NO-SERIAL-' . uniqid(),
            'price_paise' => 49900,
            'mrp_paise' => 99900,
            'option_signature' => '',
            'position' => 0,
            'version' => 0,
        ]);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $adminToken)
            ->get(route('admin.products.edit', ['product' => $product, 'tab' => 'variants']));

        $response->assertStatus(200);

        // Should initialize with empty options array in x-data, NOT [['name' => 'Size', 'values' => 'S, M, L, XL']]
        $response->assertSee('options: []', false);
        $response->assertSee('No option types configured');
        $response->assertDontSee('options: [{"name":"Size","values":"S, M, L, XL"}]', false);

        // Also test posting empty options to sync-options route
        $postResponse = $this->withUnencryptedCookie('dhansree_admin_session', $adminToken)
            ->post(route('admin.products.sync-options', $product), []);

        $postResponse->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'variants']));
        $postResponse->assertSessionHas('success', 'Product options removed. Product configured without variations.');
    }
}
