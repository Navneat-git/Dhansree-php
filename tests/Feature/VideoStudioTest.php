<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Services\VideoStudio\FfmpegService;
use App\Services\VideoStudio\VideoStudioService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoStudioTest extends TestCase
{
    use DatabaseTransactions;

    protected string $rawSessionToken;
    protected AdminUser $admin;
    protected Product $product;
    protected ProductImage $image1;
    protected ProductImage $image2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'admin_vs_' . uniqid() . '@dhansree.test',
            'name' => 'Video Studio Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];

        // Create test product with test images
        $this->product = Product::create([
            'name' => 'Royal Crimson Embroidered Kurti Set',
            'slug' => 'royal-crimson-embroidered-kurti-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'ranking' => 1,
            'version' => 1,
        ]);

        // Generate 2 sample test PNG image files in public/media/test/
        $mediaDir = public_path('media/test-studio');
        File::ensureDirectoryExists($mediaDir);

        $testImgPath1 = $mediaDir . '/slide_a.png';
        $testImgPath2 = $mediaDir . '/slide_b.png';

        if (!file_exists($testImgPath1)) {
            $im1 = imagecreatetruecolor(800, 1000);
            $c1 = imagecolorallocate($im1, 88, 0, 0); // Maroon
            imagefilledrectangle($im1, 0, 0, 800, 1000, $c1);
            imagepng($im1, $testImgPath1);
            imagedestroy($im1);
        }

        if (!file_exists($testImgPath2)) {
            $im2 = imagecreatetruecolor(800, 1000);
            $c2 = imagecolorallocate($im2, 176, 136, 88); // Gold
            imagefilledrectangle($im2, 0, 0, 800, 1000, $c2);
            imagepng($im2, $testImgPath2);
            imagedestroy($im2);
        }

        $this->image1 = ProductImage::create([
            'product_id' => $this->product->id,
            'storage_key' => 'test-studio/slide_a.png',
            'alt_text' => 'Test Slide A',
            'position' => 0,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 1234,
        ]);

        $this->image2 = ProductImage::create([
            'product_id' => $this->product->id,
            'storage_key' => 'test-studio/slide_b.png',
            'alt_text' => 'Test Slide B',
            'position' => 1,
            'width' => 800,
            'height' => 1000,
            'byte_size' => 1234,
        ]);
    }

    protected function adminRequest()
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->withSession(['admin_session_token' => $this->rawSessionToken])
            ->withHeader('Authorization', 'Bearer ' . $this->rawSessionToken);
    }

    public function test_unauthenticated_user_cannot_access_video_studio(): void
    {
        $response = $this->get(route('admin.video-studio.index'));
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_video_studio_dashboard(): void
    {
        $response = $this->adminRequest()
            ->get(route('admin.video-studio.index'));

        $response->assertStatus(200);
        $response->assertSee('Dhansree Video Studio');
        $response->assertSee('Create New Video');
    }

    public function test_admin_can_view_create_screen(): void
    {
        $response = $this->adminRequest()
            ->get(route('admin.video-studio.create', ['product_id' => $this->product->id]));

        $response->assertStatus(200);
        $response->assertSee('Create Promotional Video');
        $response->assertSee("Women's Fashion Showcase");
        $response->assertSee('Festive Collection');
        $response->assertSee('New Arrivals');
        $response->assertSee('9:16');
    }

    public function test_product_search_endpoint_returns_json(): void
    {
        $response = $this->adminRequest()
            ->getJson(route('admin.video-studio.products.search', ['q' => 'Royal Crimson']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'products' => [
                '*' => ['id', 'name', 'sku', 'price', 'image_count', 'images'],
            ],
        ]);
    }

    public function test_product_images_endpoint_returns_genuine_images(): void
    {
        $response = $this->adminRequest()
            ->getJson(route('admin.video-studio.products.images', $this->product));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product' => ['id', 'name'],
            'images' => [
                '*' => ['id', 'url', 'position', 'is_primary'],
            ],
        ]);
        $this->assertCount(2, $response->json('images'));
    }

    public function test_store_validation_requires_product_and_images(): void
    {
        $response = $this->adminRequest()
            ->post(route('admin.video-studio.store'), [
                'video_type' => 'slideshow',
                'template' => 'fashion_showcase',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 10,
            ]);

        $response->assertSessionHasErrors(['product_id', 'image_ids']);
    }

    public function test_admin_can_queue_video_generation(): void
    {
        Queue::fake();

        $data = [
            'product_id' => $this->product->id,
            'title' => 'Festive Showcase Reel',
            'video_type' => 'slideshow',
            'template' => 'festive_collection',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 10,
            'image_ids' => [$this->image1->id, $this->image2->id],
            'enable_music' => '1',
            'audio_track' => 'festive_royal',
            'text_overlay' => '1',
            'show_price' => '1',
            'call_to_action' => '1',
        ];

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.store'), $data);

        $response->assertRedirect(route('admin.video-studio.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_videos', [
            'product_id' => $this->product->id,
            'title' => 'Festive Showcase Reel',
            'template' => 'festive_collection',
            'aspect_ratio' => '9:16',
            'status' => 'queued',
        ]);
    }

    public function test_store_via_ajax_returns_json_with_redirect_and_status_url(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'title' => 'Ajax Reel',
            'video_type' => 'slideshow',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 10,
            'image_ids' => [$this->image1->id],
            'enable_music' => '1',
            'audio_track' => 'festive_royal',
            'text_overlay' => '1',
            'show_price' => '1',
            'call_to_action' => '1',
        ];

        $response = $this->adminRequest()
            ->postJson(route('admin.video-studio.store'), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('admin.video-studio.index'),
        ]);
        $this->assertNotNull($response->json('status_url'));
        $this->assertNotNull($response->json('video.id'));
    }

    public function test_ai_motion_option_is_guarded_when_disabled(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'video_type' => 'ai_motion',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 10,
            'image_ids' => [$this->image1->id],
        ];

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.store'), $data);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('product_videos', [
            'video_type' => 'ai_motion',
            'product_id' => $this->product->id,
        ]);
    }

    public function test_status_endpoint_returns_json(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Status Check Video',
            'video_type' => 'slideshow',
            'template' => 'new_arrivals',
            'aspect_ratio' => '1:1',
            'status' => 'processing',
            'progress' => 45,
            'duration_seconds' => 10,
        ]);

        $response = $this->adminRequest()
            ->getJson(route('admin.video-studio.status', $video));

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $video->id,
            'status' => 'processing',
            'progress' => 45,
            'is_processing' => true,
        ]);
    }

    public function test_destroy_deletes_video_without_touching_product_images(): void
    {
        Storage::fake('public');

        // Create dummy video file on storage
        Storage::disk('public')->put('video-studio/videos/test.mp4', 'dummy mp4 data');
        Storage::disk('public')->put('video-studio/thumbnails/test.jpg', 'dummy jpg data');

        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Delete Test Video',
            'video_type' => 'slideshow',
            'template' => 'clean_showcase',
            'aspect_ratio' => '4:5',
            'status' => 'completed',
            'video_path' => 'video-studio/videos/test.mp4',
            'thumbnail_path' => 'video-studio/thumbnails/test.jpg',
            'selected_image_ids' => [$this->image1->id, $this->image2->id],
        ]);

        $response = $this->adminRequest()
            ->delete(route('admin.video-studio.destroy', $video));

        $response->assertRedirect(route('admin.video-studio.index'));
        $response->assertSessionHas('success');

        // Video record deleted
        $this->assertDatabaseMissing('product_videos', ['id' => $video->id]);

        // Storage files deleted
        Storage::disk('public')->assertMissing('video-studio/videos/test.mp4');
        Storage::disk('public')->assertMissing('video-studio/thumbnails/test.jpg');

        // CRITICAL REQUIREMENT: Original product images still exist in DB and disk!
        $this->assertDatabaseHas('product_images', ['id' => $this->image1->id]);
        $this->assertDatabaseHas('product_images', ['id' => $this->image2->id]);
        $this->assertTrue(file_exists(public_path('media/' . $this->image1->storage_key)));
    }

    public function test_ffmpeg_service_can_render_real_slideshow(): void
    {
        /** @var FfmpegService $ffmpegService */
        $ffmpegService = app(FfmpegService::class);

        if (!$ffmpegService->isAvailable()) {
            $this->markTestSkipped('FFmpeg binary not available for real render smoke test.');
        }

        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Smoke Test Video',
            'video_type' => 'slideshow',
            'template' => 'new_arrivals',
            'aspect_ratio' => '9:16',
            'width' => 1080,
            'height' => 1920,
            'duration_seconds' => 5, // Fast 5-second test
            'selected_image_ids' => [$this->image1->id, $this->image2->id],
            'render_settings' => [
                'enable_music' => false,
                'text_overlay' => true,
                'show_price' => true,
                'call_to_action' => true,
            ],
            'status' => 'queued',
        ]);

        /** @var VideoStudioService $studioService */
        $studioService = app(VideoStudioService::class);
        $success = $studioService->render($video);

        $this->assertTrue($success, 'VideoStudioService::render should succeed');
        $video->refresh();

        $this->assertEquals('completed', $video->status);
        $this->assertEquals(100, $video->progress);
        $this->assertNotNull($video->video_path);
        $this->assertNotNull($video->thumbnail_path);
        $this->assertGreaterThan(5000, $video->file_size_bytes);

        // Verify file exists on storage
        $disk = config('video_studio.disk', 'public');
        $this->assertTrue(Storage::disk($disk)->exists($video->video_path));
        $this->assertTrue(Storage::disk($disk)->exists($video->thumbnail_path));

        // Clean up rendered files
        Storage::disk($disk)->delete($video->video_path);
        Storage::disk($disk)->delete($video->thumbnail_path);
    }

    public function test_admin_can_trigger_migration_endpoint(): void
    {
        $response = $this->adminRequest()
            ->post('/admin/video-studio/migrate-db');

        $response->assertRedirect('/admin/video-studio');
        $response->assertSessionHas('success');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('media/test-studio'));
        parent::tearDown();
    }
}
