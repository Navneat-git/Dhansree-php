<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductImageService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminProductImageReorderTest extends TestCase
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
            'email' => 'image_reorder_' . uniqid() . '@dhansree.test',
            'name' => 'Image Reorder Admin',
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

    private function createImage(int $productId, string $storageKey, int $position, string $alt = 'Test Photo'): ProductImage
    {
        return ProductImage::create([
            'product_id' => $productId,
            'storage_key' => $storageKey,
            'alt_text' => $alt,
            'position' => $position,
            'width' => 1000,
            'height' => 1000,
            'byte_size' => 50000,
        ]);
    }

    public function test_move_position_swaps_adjacent_images_and_updates_positions(): void
    {
        $product = Product::create([
            'name' => 'Reorder Test Product ' . uniqid(),
            'slug' => 'reorder-product-' . uniqid(),
            'status' => ProductStatus::DRAFT,
        ]);

        $img0 = $this->createImage($product->id, 'photo_0_' . uniqid() . '.webp', 0, 'Photo 0');
        $img1 = $this->createImage($product->id, 'photo_1_' . uniqid() . '.webp', 1, 'Photo 1');
        $img2 = $this->createImage($product->id, 'photo_2_' . uniqid() . '.webp', 2, 'Photo 2');

        $service = app(ProductImageService::class);

        // Move img0 down (swaps with img1)
        $service->movePosition($img0, 'down', $this->admin->id);

        $img0->refresh();
        $img1->refresh();
        $img2->refresh();

        $this->assertEquals(1, $img0->position);
        $this->assertEquals(0, $img1->position);
        $this->assertEquals(2, $img2->position);

        // img1 is now position 0, so it is primary
        $this->assertTrue($img1->is_primary);
        $this->assertFalse($img0->is_primary);

        // Now move img2 up (swaps with img0 which is at position 1)
        $service->movePosition($img2, 'up', $this->admin->id);

        $img0->refresh();
        $img1->refresh();
        $img2->refresh();

        $this->assertEquals(0, $img1->position);
        $this->assertEquals(1, $img2->position);
        $this->assertEquals(2, $img0->position);
    }

    public function test_move_position_boundary_conditions(): void
    {
        $product = Product::create([
            'name' => 'Boundary Test Product ' . uniqid(),
            'slug' => 'boundary-product-' . uniqid(),
            'status' => ProductStatus::DRAFT,
        ]);

        $img0 = $this->createImage($product->id, 'photo_0_' . uniqid() . '.webp', 0, 'Photo 0');
        $img1 = $this->createImage($product->id, 'photo_1_' . uniqid() . '.webp', 1, 'Photo 1');

        $service = app(ProductImageService::class);

        // Moving first item 'up' should do nothing
        $service->movePosition($img0, 'up', $this->admin->id);
        $img0->refresh();
        $this->assertEquals(0, $img0->position);

        // Moving last item 'down' should do nothing
        $service->movePosition($img1, 'down', $this->admin->id);
        $img1->refresh();
        $this->assertEquals(1, $img1->position);
    }

    public function test_admin_http_route_moves_image_and_redirects_to_media_tab(): void
    {
        $product = Product::create([
            'name' => 'HTTP Move Product ' . uniqid(),
            'slug' => 'http-move-' . uniqid(),
            'status' => ProductStatus::DRAFT,
        ]);

        $img0 = $this->createImage($product->id, 'first_' . uniqid() . '.webp', 0, 'First');
        $img1 = $this->createImage($product->id, 'second_' . uniqid() . '.webp', 1, 'Second');

        $response = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawSessionToken)
            ->post("/admin/catalog/images/{$img0->id}/move/down");

        $response->assertRedirect(route('admin.products.edit', ['product' => $product->id, 'tab' => 'media']));
        $response->assertSessionHas('active_tab', 'media');
        $response->assertSessionHas('success', 'Image order updated successfully.');

        $img0->refresh();
        $img1->refresh();
        $this->assertEquals(1, $img0->position);
        $this->assertEquals(0, $img1->position);
    }

    public function test_admin_product_edit_page_renders_up_and_down_arrow_forms(): void
    {
        $category = Category::create([
            'name' => 'Apparel ' . uniqid(),
            'slug' => 'apparel-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'name' => 'View Edit Product ' . uniqid(),
            'slug' => 'view-edit-' . uniqid(),
            'category_id' => $category->id,
            'status' => ProductStatus::DRAFT,
        ]);

        $img0 = $this->createImage($product->id, 'view_0_' . uniqid() . '.webp', 0, 'View 0');
        $img1 = $this->createImage($product->id, 'view_1_' . uniqid() . '.webp', 1, 'View 1');

        $response = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawSessionToken)
            ->get("/admin/catalog/products/{$product->id}/edit?tab=media");

        $response->assertOk();
        // Down button should exist for img0
        $response->assertSee(route('admin.images.move', ['image' => $img0->id, 'direction' => 'down']));
        // Up button should exist for img1
        $response->assertSee(route('admin.images.move', ['image' => $img1->id, 'direction' => 'up']));
        // Arrow characters
        $response->assertSee('▲');
        $response->assertSee('▼');
    }

    public function test_storefront_displays_images_in_updated_reordered_sequence(): void
    {
        $category = Category::create([
            'name' => 'Dresses ' . uniqid(),
            'slug' => 'dresses-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'name' => 'Storefront Order Product ' . uniqid(),
            'slug' => 'storefront-order-' . uniqid(),
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'STO-' . uniqid(),
            'price_paise' => 129900,
            'mrp_paise' => 199900,
            'option_signature' => '',
            'position' => 0,
            'version' => 0,
        ]);

        $keyA = 'photo_alpha_' . uniqid() . '.webp';
        $keyB = 'photo_beta_' . uniqid() . '.webp';

        $imgA = $this->createImage($product->id, $keyA, 0, 'Alpha Photo');
        $imgB = $this->createImage($product->id, $keyB, 1, 'Beta Photo');

        // Move imgB up so it becomes position 0 (primary)
        app(ProductImageService::class)->movePosition($imgB, 'up', $this->admin->id);

        $imgA->refresh();
        $imgB->refresh();
        $this->assertEquals(1, $imgA->position);
        $this->assertEquals(0, $imgB->position);

        // Fetch storefront detail page
        $response = $this->get("/product/{$product->slug}");
        $response->assertOk();

        $content = $response->getContent();
        $posA = strpos($content, $keyA);
        $posB = strpos($content, $keyB);

        $this->assertNotFalse($posA, "Key A {$keyA} should be in HTML");
        $this->assertNotFalse($posB, "Key B {$keyB} should be in HTML");
        // photo_beta should appear BEFORE photo_alpha in the storefront HTML
        $this->assertLessThan($posA, $posB, 'Reordered image (Beta) must appear before Alpha on storefront');
    }
}
