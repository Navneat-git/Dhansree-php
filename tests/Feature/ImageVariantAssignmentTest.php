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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantAssignmentTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_admin_can_upload_multiple_product_images_at_once(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Multi Image Product ' . uniqid(),
            'slug' => 'multi-img-' . uniqid(),
            'status' => \App\Enums\ProductStatus::DRAFT,
        ]);

        $file1 = UploadedFile::fake()->image('angle1.jpg', 800, 800);
        $file2 = UploadedFile::fake()->image('angle2.jpg', 800, 800);
        $file3 = UploadedFile::fake()->image('angle3.jpg', 800, 800);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.products.upload-image', $product), [
                'images' => [$file1, $file2, $file3],
                'alt_text' => 'Multi Angle Kurti',
            ]);

        $response->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));
        $response->assertSessionHas('success', '3 product images uploaded successfully.');

        $images = ProductImage::where('product_id', $product->id)->orderBy('position')->get();
        $this->assertCount(3, $images);
        $this->assertSame(0, $images[0]->position);
        $this->assertSame(1, $images[1]->position);
        $this->assertSame(2, $images[2]->position);
        $this->assertTrue($images[0]->is_primary);
        $this->assertFalse($images[1]->is_primary);
    }

    public function test_admin_can_upload_single_image_with_fallback(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Single Image Product ' . uniqid(),
            'slug' => 'single-img-' . uniqid(),
            'status' => \App\Enums\ProductStatus::DRAFT,
        ]);

        $file = UploadedFile::fake()->image('single.jpg', 800, 800);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.products.upload-image', $product), [
                'image' => $file,
                'alt_text' => 'Single Shot',
            ]);

        $response->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));
        $response->assertSessionHas('success', 'Product image uploaded successfully.');

        $image = ProductImage::where('product_id', $product->id)->first();
        $this->assertNotNull($image);
        $this->assertSame('Single Shot', $image->alt_text);
    }
}
