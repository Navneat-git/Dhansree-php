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
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Support\Auth\AdminSessionManager;
use App\Support\Catalog\ProductListingCard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVideoPinningTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $admin;
    protected string $rawSessionToken;
    protected Product $product;
    protected ProductOptionValue $yellowValue;
    protected ProductOptionValue $blueValue;
    protected ProductVariant $variantYellow;
    protected ProductVariant $variantBlue;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'video_pinning_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Video Pinning Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];

        $category = Category::firstOrCreate(
            ['slug' => 'kurta-set'],
            ['name' => 'Kurta Sets', 'is_visible' => true, 'position' => 1]
        );

        $this->product = Product::create([
            'name' => 'Ajrakh Print Cotton Kurta with Pant',
            'slug' => 'ajrakh-print-cotton-kurta-' . uniqid(),
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'ranking' => 1,
            'version' => 1,
        ]);

        $colorOption = ProductOption::create([
            'product_id' => $this->product->id,
            'name' => 'Color',
            'position' => 0,
        ]);

        $this->yellowValue = ProductOptionValue::create([
            'option_id' => $colorOption->id,
            'value' => 'Yellow',
            'position' => 0,
        ]);

        $this->blueValue = ProductOptionValue::create([
            'option_id' => $colorOption->id,
            'value' => 'Blue',
            'position' => 1,
        ]);

        $this->variantYellow = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'AJR-YEL-M-' . uniqid(),
            'price_paise' => 129900,
            'mrp_paise' => 249900,
            'option_signature' => (string) $this->yellowValue->id,
            'position' => 0,
            'version' => 0,
        ]);
        $this->variantYellow->optionValues()->attach($this->yellowValue->id, ['option_id' => $colorOption->id]);

        $this->variantBlue = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'AJR-BLU-M-' . uniqid(),
            'price_paise' => 129900,
            'mrp_paise' => 249900,
            'option_signature' => (string) $this->blueValue->id,
            'position' => 1,
            'version' => 0,
        ]);
        $this->variantBlue->optionValues()->attach($this->blueValue->id, ['option_id' => $colorOption->id]);
    }

    public function test_admin_can_upload_and_pin_video_to_color(): void
    {
        $file = UploadedFile::fake()->create('model_video.mp4', 5000, 'video/mp4');

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post("/admin/catalog/products/{$this->product->id}/upload-video", [
                'video' => $file,
                'title' => 'Ajrakh Yellow Motion Video',
                'is_primary' => '1',
                'option_value_ids' => [$this->yellowValue->id],
                'variant_ids' => [$this->variantYellow->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_videos', [
            'product_id' => $this->product->id,
            'title' => 'Ajrakh Yellow Motion Video',
            'is_primary' => true,
        ]);

        $video = ProductVideo::where('product_id', $this->product->id)->first();
        $this->assertNotNull($video);
        $this->assertTrue($video->is_primary);
        $this->assertTrue($video->isPinnedToOptionValue($this->yellowValue->id));
        $this->assertFalse($video->isPinnedToOptionValue($this->blueValue->id));
        $this->assertTrue($video->isPinnedToVariant($this->variantYellow->id));
    }

    public function test_admin_can_update_video_pinning(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Initial Title',
            'video_type' => 'imported_branded',
            'aspect_ratio' => '9:16',
            'status' => 'completed',
            'approval_status' => 'approved',
            'video_path' => 'video-studio/videos/test.mp4',
            'is_primary' => false,
            'pinned_option_value_ids' => [$this->yellowValue->id],
        ]);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put("/admin/catalog/videos/{$video->id}", [
                'title' => 'Updated Video Title',
                'is_primary' => '1',
                'option_value_ids' => [$this->blueValue->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $video->refresh();
        $this->assertEquals('Updated Video Title', $video->title);
        $this->assertTrue($video->is_primary);
        $this->assertTrue($video->isPinnedToOptionValue($this->blueValue->id));
        $this->assertFalse($video->isPinnedToOptionValue($this->yellowValue->id));
    }

    public function test_admin_can_set_primary_video_and_delete_video(): void
    {
        $video1 = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Video 1',
            'video_type' => 'imported_branded',
            'aspect_ratio' => '9:16',
            'status' => 'completed',
            'approval_status' => 'approved',
            'video_path' => 'video-studio/videos/test1.mp4',
            'is_primary' => true,
        ]);

        $video2 = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Video 2',
            'video_type' => 'imported_branded',
            'aspect_ratio' => '9:16',
            'status' => 'completed',
            'approval_status' => 'approved',
            'video_path' => 'video-studio/videos/test2.mp4',
            'is_primary' => false,
        ]);

        // Set video2 as primary
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post("/admin/catalog/videos/{$video2->id}/set-primary");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($video1->fresh()->is_primary);
        $this->assertTrue($video2->fresh()->is_primary);

        // Delete video2
        $delResponse = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->delete("/admin/catalog/videos/{$video2->id}");

        $delResponse->assertRedirect();
        $delResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('product_videos', ['id' => $video2->id]);
        $this->assertTrue($video1->fresh()->is_primary);
    }

    public function test_storefront_resolves_color_specific_pinned_video(): void
    {
        $yellowVideo = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Yellow Video',
            'video_type' => 'imported_branded',
            'aspect_ratio' => '9:16',
            'status' => 'completed',
            'approval_status' => 'approved',
            'video_path' => 'video-studio/videos/yellow.mp4',
            'is_primary' => false,
            'pinned_option_value_ids' => [$this->yellowValue->id],
        ]);

        $primaryVideo = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'General Primary Video',
            'video_type' => 'imported_branded',
            'aspect_ratio' => '9:16',
            'status' => 'completed',
            'approval_status' => 'approved',
            'video_path' => 'video-studio/videos/general.mp4',
            'is_primary' => true,
            'pinned_option_value_ids' => [],
        ]);

        $this->product->load('options.values', 'videos');

        // Pinned color Yellow gets the yellow video
        $resolvedYellow = $this->product->getApprovedVideoForColor('Yellow');
        $this->assertNotNull($resolvedYellow);
        $this->assertEquals($yellowVideo->id, $resolvedYellow->id);

        // Color Blue falls back to primary video
        $resolvedBlue = $this->product->getApprovedVideoForColor('Blue');
        $this->assertNotNull($resolvedBlue);
        $this->assertEquals($primaryVideo->id, $resolvedBlue->id);

        // ProductListingCard also receives color video
        $card = new ProductListingCard(
            product: $this->product,
            name: $this->product->name,
            slug: $this->product->slug,
            brand: 'DHANSREE',
            colorName: 'Yellow',
            colorCode: 'YEL',
            colorHex: '#eab308',
            primaryImageUrl: 'http://localhost/media/test.jpg',
            url: '/product/' . $this->product->slug,
            formattedPrice: '₹1,299',
            formattedMrp: '₹2,499',
            discountPercentage: 48,
            firstVariant: $this->variantYellow,
            variants: collect([$this->variantYellow]),
            hasMultipleVariants: true,
            inStock: true
        );

        $this->assertEquals($yellowVideo->id, $card->approved_video->id);
    }

    public function test_admin_product_edit_screen_renders_video_section_and_badges(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Ajrakh Yellow Motion Video',
            'video_type' => 'imported_branded',
            'aspect_ratio' => '9:16',
            'status' => 'completed',
            'approval_status' => 'approved',
            'video_path' => 'video-studio/videos/yellow.mp4',
            'is_primary' => true,
            'pinned_option_value_ids' => [$this->yellowValue->id],
        ]);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get("/admin/catalog/products/{$this->product->id}/edit?tab=media");

        $response->assertStatus(200);
        $response->assertSee('Upload Product Video');
        $response->assertSee('Product Videos (1)');
        $response->assertSee('PRIMARY VIDEO');
        $response->assertSee('Color: Yellow');
        $response->assertSee('Pin to Color / Edit Video');
    }
}
