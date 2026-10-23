<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantService;
use App\Support\Auth\AdminSessionManager;
use Tests\TestCase;

class ImageVariantAssignmentTest extends TestCase
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
            'email' => 'media_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Media Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];
    }

    public function test_admin_can_update_image_variant_and_color_assignments(): void
    {
        $product = Product::create([
            'name' => 'Media Pinning Product ' . uniqid(),
            'slug' => 'media-pinning-' . uniqid(),
            'status' => \App\Enums\ProductStatus::DRAFT,
        ]);

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($product, [
            ['name' => 'Color', 'values' => ['Blue', 'Yellow']],
            ['name' => 'Size', 'values' => ['M', 'L']],
        ]);

        $product->refresh();
        $blueOptionValue = $product->options->firstWhere('name', 'Color')->values->firstWhere('value', 'Blue');

        $variant1 = $variantService->createVariant($product, [
            'sku' => 'MED-VAR-' . uniqid(),
            'price_rupees' => 899.00,
            'mrp_rupees' => 1299.00,
            'option_value_ids' => [$blueOptionValue->id],
        ]);

        $image = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/test.webp',
            'alt_text' => 'Original Alt',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 120000,
        ]);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.images.update', $image), [
                'alt_text' => 'Updated Alt Text For Blue',
                'variant_ids' => [$variant1->id],
                'option_value_ids' => [$blueOptionValue->id],
            ]);

        $response->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));
        $response->assertSessionHas('success');

        $image->refresh();
        $this->assertSame('Updated Alt Text For Blue', $image->alt_text);

        // Verify variants and optionValues links
        $this->assertTrue($image->variants->contains('id', $variant1->id));
        $this->assertTrue($image->optionValues->contains('id', $blueOptionValue->id));
    }
}
