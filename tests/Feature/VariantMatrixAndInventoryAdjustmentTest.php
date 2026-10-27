<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminSession;
use App\Models\AdminUser;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantService;
use App\Support\Auth\AdminSessionManager;
use Carbon\Carbon;
use Tests\TestCase;

class VariantMatrixAndInventoryAdjustmentTest extends TestCase
{
    private AdminUser $admin;
    private string $rawSessionToken;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'variant_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Variant Tester',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];
    }

    private function adminPost(string $uri, array $data = [])
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post($uri, $data);
    }

    private function adminGet(string $uri)
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get($uri);
    }

    private function adminPut(string $uri, array $data = [])
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put($uri, $data);
    }

    private function adminDelete(string $uri, array $data = [])
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->delete($uri, $data);
    }

    public function test_inventory_adjust_endpoint_updates_stock_without_500_error(): void
    {
        $product = Product::create([
            'name' => 'Test Inventory Product ' . uniqid(),
            'slug' => 'test-inv-prod-' . uniqid(),
            'status' => ProductStatus::DRAFT,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-INV-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'weight_grams' => 200,
            'option_signature' => '',
            'position' => 0,
            'version' => 0,
        ]);

        $location = InventoryLocation::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Location', 'is_active' => true, 'is_default' => true]
        );

        // Test mode: set (setting exact stock quantity)
        $response = $this->adminPost("/admin/inventory/adjust/{$variant->id}", [
            'location_id' => $location->id,
            'quantity' => 15,
            'mode' => 'set',
            'reason' => 'Initial stock load',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(15, $variant->fresh()->stock_on_hand);

        // Test mode: adjust (adding stock quantity)
        $response2 = $this->adminPost("/admin/inventory/adjust/{$variant->id}", [
            'location_id' => $location->id,
            'quantity' => 5,
            'mode' => 'adjust',
            'reason' => 'Received more units',
        ]);

        $response2->assertSessionHas('success');
        $this->assertEquals(20, $variant->fresh()->stock_on_hand);
    }

    public function test_variant_matrix_generation_deduces_base_sku_and_adopts_existing_unassigned_variant(): void
    {
        $uid = uniqid();
        $product = Product::create([
            'name' => 'Kurta Short Red ' . $uid,
            'slug' => 'kurta-short-red-' . $uid,
            'status' => ProductStatus::DRAFT,
            'description' => 'Test Kurta',
        ]);

        $defaultSku = strtoupper("DSGES-S2-{$uid}-RED-S");

        // Default variant created with size S in SKU and 10 on-hand units
        $defaultVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $defaultSku,
            'price_paise' => 126000,
            'mrp_paise' => 249900,
            'weight_grams' => 250,
            'option_signature' => '',
            'position' => 0,
            'version' => 0,
        ]);

        $location = InventoryLocation::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Location', 'is_active' => true, 'is_default' => true]
        );

        \App\Models\InventoryItem::create([
            'variant_id' => $defaultVariant->id,
            'backorder_policy' => \App\Enums\BackorderPolicy::STOP_SALE,
        ]);

        InventoryLevel::create([
            'variant_id' => $defaultVariant->id,
            'location_id' => $location->id,
            'on_hand' => 10,
            'reserved' => 0,
        ]);

        // Admin adds option Size: XS, S, M, L
        $opt = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Size',
            'position' => 0,
        ]);
        ProductOptionValue::create(['option_id' => $opt->id, 'value' => 'XS', 'position' => 0]);
        $valS = ProductOptionValue::create(['option_id' => $opt->id, 'value' => 'S', 'position' => 1]);
        ProductOptionValue::create(['option_id' => $opt->id, 'value' => 'M', 'position' => 2]);
        ProductOptionValue::create(['option_id' => $opt->id, 'value' => 'L', 'position' => 3]);

        /** @var ProductVariantService $service */
        $service = app(ProductVariantService::class);
        $created = $service->generateVariantMatrix($product, 1260.00, 2499.00, null);

        // Verify total live variants is 4 (XS, S, M, L), no duplicate S created!
        $allVariants = ProductVariant::where('product_id', $product->id)->get();
        $this->assertCount(4, $allVariants);

        // The default variant should be adopted as the S variant with its 10 stock intact
        $variantS = $allVariants->firstWhere('sku', $defaultSku);
        $this->assertNotNull($variantS);
        $this->assertEquals($defaultVariant->id, $variantS->id);
        $this->assertEquals(10, $variantS->stock_on_hand);
        $this->assertTrue($variantS->optionValues->contains('id', $valS->id));

        // The missing variants should have properly formatted SKUs
        $upperUid = strtoupper($uid);
        $this->assertTrue($allVariants->contains('sku', "DSGES-S2-{$upperUid}-RED-XS"));
        $this->assertTrue($allVariants->contains('sku', "DSGES-S2-{$upperUid}-RED-M"));
        $this->assertTrue($allVariants->contains('sku', "DSGES-S2-{$upperUid}-RED-L"));
    }

    public function test_admin_can_delete_individual_variant(): void
    {
        $product = Product::create([
            'name' => 'Delete Variant Test ' . uniqid(),
            'slug' => 'del-var-' . uniqid(),
            'status' => ProductStatus::DRAFT,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-DEL-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'del-sig-1',
            'position' => 0,
            'version' => 0,
        ]);

        $response = $this->adminDelete("/admin/catalog/variants/{$variant->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_admin_can_delete_all_variants_of_product(): void
    {
        $product = Product::create([
            'name' => 'Delete All Variants Test ' . uniqid(),
            'slug' => 'del-all-var-' . uniqid(),
            'status' => ProductStatus::DRAFT,
            'description' => 'Test',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-ALL-1-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'sig-1',
            'position' => 0,
            'version' => 0,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-ALL-2-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'sig-2',
            'position' => 1,
            'version' => 0,
        ]);

        $response = $this->adminDelete("/admin/catalog/products/{$product->id}/variants/delete-all");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, ProductVariant::where('product_id', $product->id)->count());
    }

    public function test_admin_can_bulk_update_stock_across_all_variants(): void
    {
        $product = Product::create([
            'name' => 'Bulk Stock Test ' . uniqid(),
            'slug' => 'bulk-stock-' . uniqid(),
            'status' => ProductStatus::DRAFT,
            'description' => 'Test',
        ]);

        $v1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BULK-1-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'bsig-1',
            'position' => 0,
            'version' => 0,
        ]);

        $v2 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BULK-2-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'bsig-2',
            'position' => 1,
            'version' => 0,
        ]);

        $response = $this->adminPost("/admin/catalog/products/{$product->id}/variants/bulk-stock", [
            'bulk_stock_quantity' => 25,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(25, $v1->fresh()->stock_on_hand);
        $this->assertEquals(25, $v2->fresh()->stock_on_hand);
    }

    public function test_admin_can_update_variant_stock_directly_from_edit_form(): void
    {
        $product = Product::create([
            'name' => 'Direct Stock Test ' . uniqid(),
            'slug' => 'dir-stock-' . uniqid(),
            'status' => ProductStatus::DRAFT,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'DIR-1-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'dsig-1',
            'position' => 0,
            'version' => 0,
        ]);

        $response = $this->adminPut("/admin/catalog/variants/{$variant->id}", [
            'sku' => $variant->sku,
            'price_rupees' => 899.00,
            'mrp_rupees' => 1499.00,
            'stock_quantity' => 18,
            'weight_grams' => 300,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(18, $variant->fresh()->stock_on_hand);
    }

    public function test_product_edit_page_renders_delete_button_and_stock_tools(): void
    {
        $product = Product::create([
            'name' => 'UI Variant Tools Test ' . uniqid(),
            'slug' => 'ui-tools-' . uniqid(),
            'status' => ProductStatus::DRAFT,
            'description' => 'Test',
        ]);

        $opt = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Size',
            'position' => 0,
        ]);
        ProductOptionValue::create(['option_id' => $opt->id, 'value' => 'S', 'position' => 0]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'UI-TEST-' . uniqid(),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'option_signature' => 'uisig-1',
            'position' => 0,
            'version' => 0,
        ]);

        $response = $this->adminGet("/admin/catalog/products/{$product->id}/edit?tab=variants");
        $response->assertStatus(200);

        // Verify Delete button exists for variant
        $response->assertSee('form="delete-variant-' . $variant->id . '"', false);
        $response->assertSee('Delete All Variants');
        // Verify Set all stock bulk tool exists
        $response->assertSee('Set all stock:');
        $response->assertSee('name="bulk_stock_quantity"', false);
        // Verify +5 / +10 quick stock adjustment buttons exist
        $response->assertSee('+5');
        $response->assertSee('+10');
        // Verify Default Stock Qty in matrix generator
        $response->assertSee('Default Stock Qty');
        $response->assertSee('name="default_stock"', false);
    }

    public function test_admin_layout_renders_mobile_navigation_drawer(): void
    {
        $response = $this->adminGet('/admin/inventory');
        $response->assertStatus(200);

        // Verify mobile hamburger button and mobile slide-over drawer exist
        $response->assertSee('@click="mobileMenuOpen = true"', false);
        $response->assertSee('x-show="mobileMenuOpen"', false);
        $response->assertSee('@click="mobileMenuOpen = false"', false);
        $response->assertSee('Set Exact Stock');
        $response->assertSee('Add / Deduct Stock');
    }
}
