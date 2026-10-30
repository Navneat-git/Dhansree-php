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
use App\Models\ProductVideo;
use App\Services\Catalog\ProductVariantService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActualProductImageAndAiDisclaimerTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $rawSessionToken;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'actual_img_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Actual Image Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];

        $this->category = Category::firstOrCreate(
            ['slug' => 'womens-wear-test'],
            ['name' => "Women's Wear Test", 'is_visible' => true, 'position' => 1]
        );
    }

    public function test_admin_can_upload_image_marked_as_actual_product_image(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Actual Image Test Product ' . uniqid(),
            'slug' => 'actual-img-test-' . uniqid(),
            'status' => ProductStatus::DRAFT,
        ]);

        $fakeImage = UploadedFile::fake()->image('actual_photo.jpg', 800, 1000);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.products.upload-image', ['product' => $product]), [
                'tab' => 'media',
                'images' => [$fakeImage],
                'alt_text' => 'Authentic photo of Anarkali',
                'is_actual_product_image' => '1',
            ]);

        $response->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));
        $response->assertSessionHas('success');

        $image = ProductImage::where('product_id', $product->id)->first();
        $this->assertNotNull($image);
        $this->assertTrue($image->is_actual_product_image);
        $this->assertEquals('Authentic photo of Anarkali', $image->alt_text);
    }

    public function test_admin_can_toggle_actual_product_image_on_existing_image(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Toggle Actual Image ' . uniqid(),
            'slug' => 'toggle-actual-img-' . uniqid(),
            'status' => ProductStatus::DRAFT,
        ]);

        $image = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/test_sample.webp',
            'alt_text' => 'Sample Image',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 12450,
            'is_actual_product_image' => false,
        ]);

        $this->assertFalse($image->fresh()->is_actual_product_image);

        // Turn ON "Mark as Actual Product Image"
        $responseOn = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.images.update', $image), [
                'tab' => 'media',
                'alt_text' => 'Updated Alt Text',
                'is_actual_product_image' => '1',
            ]);

        $responseOn->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));
        $this->assertTrue($image->fresh()->is_actual_product_image);

        // Turn OFF "Mark as Actual Product Image"
        $responseOff = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.images.update', $image), [
                'tab' => 'media',
                'alt_text' => 'Updated Alt Text',
                'is_actual_product_image' => '0',
            ]);

        $responseOff->assertRedirect(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));
        $this->assertFalse($image->fresh()->is_actual_product_image);
    }

    public function test_admin_edit_page_renders_actual_product_image_tag_and_badges(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Admin UI Check ' . uniqid(),
            'slug' => 'admin-ui-check-' . uniqid(),
            'status' => ProductStatus::DRAFT,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/actual.webp',
            'alt_text' => 'Actual Garment',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 12450,
            'is_actual_product_image' => true,
        ]);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.products.edit', ['product' => $product, 'tab' => 'media']));

        $response->assertOk();
        $response->assertSee('Mark as Actual Product Image');
        $response->assertSee('ACTUAL PHOTO');
        $response->assertSee('Actual Product Image');
    }

    public function test_storefront_product_page_renders_actual_product_image_badge_only_when_tagged(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Storefront Actual Badge Dress ' . uniqid(),
            'slug' => 'storefront-actual-badge-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($product, [
            ['name' => 'Size', 'values' => ['Free Size']],
        ]);
        $product->refresh();
        $sizeOption = $product->options->firstWhere('name', 'Size');
        $sizeValue = $sizeOption->values->firstWhere('value', 'Free Size');

        $variantService->createVariant($product, [
            'sku' => 'ACTUAL-VAR-' . uniqid(),
            'price_rupees' => 1499.00,
            'mrp_rupees' => 2999.00,
            'option_value_ids' => [$sizeValue->id],
            'stock_quantity' => 10,
        ]);

        $taggedImage = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/real_dress.webp',
            'alt_text' => 'Real Dress Photo',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 15000,
            'is_actual_product_image' => true,
        ]);

        $untaggedImage = ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/catalog_art.webp',
            'alt_text' => 'Catalog Illustration',
            'position' => 1,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 14000,
            'is_actual_product_image' => false,
        ]);

        $response = $this->get(route('store.product', $product->slug));
        $response->assertOk();

        // Check that the badge markup with exact brand styling is rendered
        $response->assertSee('Actual Product Image');
        $response->assertSee('#580000');
        $response->assertSee('#B08858');
        $response->assertSee('#FBFAF8');

        // Check Alpine preparedImages array includes correct boolean flags
        $response->assertSee('\u0022id\u0022:' . $taggedImage->id, false);
        $response->assertSee('\u0022is_actual\u0022:true', false);
        $response->assertSee('\u0022id\u0022:' . $untaggedImage->id, false);
        $response->assertSee('\u0022is_actual\u0022:false', false);
    }

    public function test_storefront_product_page_shows_ai_disclaimer_on_ai_fashion_video(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'AI Fashion Shoot Dress ' . uniqid(),
            'slug' => 'ai-fashion-dress-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($product, [
            ['name' => 'Size', 'values' => ['Free Size']],
        ]);
        $product->refresh();
        $sizeOption = $product->options->firstWhere('name', 'Size');
        $sizeValue = $sizeOption->values->firstWhere('value', 'Free Size');

        $variantService->createVariant($product, [
            'sku' => 'AI-VAR-' . uniqid(),
            'price_rupees' => 1999.00,
            'mrp_rupees' => 3999.00,
            'option_value_ids' => [$sizeValue->id],
            'stock_quantity' => 10,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/primary.webp',
            'alt_text' => 'Primary Dress',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 12000,
            'is_actual_product_image' => false,
        ]);

        $aiVideo = ProductVideo::create([
            'product_id' => $product->id,
            'video_type' => 'ai_fashion',
            'title' => 'AI Model Fashion Reel',
            'video_path' => 'videos/ai_fashion_test.mp4',
            'duration_seconds' => 10,
            'status' => 'completed',
            'approval_status' => 'approved',
            'is_primary' => true,
        ]);

        $this->assertTrue($aiVideo->isAiGenerated());

        $response = $this->get(route('store.product', $product->slug));
        $response->assertOk();

        // Verify the exact disclaimer copy is present
        $exactDisclaimer = 'AI-generated video for illustration purposes only. Actual product details may vary; please refer to the actual product images for accurate details.';
        $response->assertSee($exactDisclaimer);

        // Verify info icon (ⓘ) is present
        $response->assertSee('ⓘ');
        $response->assertSee('AI Video Disclaimer');

        // Verify is_ai_generated is passed to Alpine
        $response->assertSee('\u0022is_ai_generated\u0022:true', false);
    }

    public function test_storefront_product_page_does_not_flag_standard_ffmpeg_slideshow_as_ai(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Slideshow Dress ' . uniqid(),
            'slug' => 'slideshow-dress-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($product, [
            ['name' => 'Size', 'values' => ['Free Size']],
        ]);
        $product->refresh();
        $sizeOption = $product->options->firstWhere('name', 'Size');
        $sizeValue = $sizeOption->values->firstWhere('value', 'Free Size');

        $variantService->createVariant($product, [
            'sku' => 'SLIDE-VAR-' . uniqid(),
            'price_rupees' => 1299.00,
            'mrp_rupees' => 2499.00,
            'option_value_ids' => [$sizeValue->id],
            'stock_quantity' => 10,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'storage_key' => 'products/' . $product->id . '/slide.webp',
            'alt_text' => 'Slide Dress',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 12000,
            'is_actual_product_image' => false,
        ]);

        $slideshowVideo = ProductVideo::create([
            'product_id' => $product->id,
            'video_type' => 'slideshow',
            'title' => 'Standard FFmpeg Slideshow',
            'video_path' => 'videos/slideshow_test.mp4',
            'duration_seconds' => 8,
            'status' => 'completed',
            'approval_status' => 'approved',
            'is_primary' => true,
        ]);

        $this->assertFalse($slideshowVideo->isAiGenerated());

        $response = $this->get(route('store.product', $product->slug));
        $response->assertOk();

        // Verify is_ai_generated is false for standard slideshows
        $response->assertSee('\u0022is_ai_generated\u0022:false', false);
    }
}
