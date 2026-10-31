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
use App\Models\ProductVideo;
use App\Services\VideoStudio\FfmpegService;
use App\Services\VideoStudio\VideoStudioService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WatchInMotionReelDownloadTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $admin;
    protected string $rawSessionToken;
    protected Product $product;
    protected ProductVideo $video;
    protected string $dummyVideoPath;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'reel_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Reel Download Admin',
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
            ['slug' => 'sarees'],
            ['name' => 'Sarees', 'is_visible' => true, 'position' => 1]
        );

        $this->product = Product::create([
            'name' => 'Royal Heritage Silk Saree in Emerald',
            'slug' => 'royal-heritage-silk-saree-' . uniqid(),
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'ranking' => 1,
            'version' => 1,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'storage_key' => 'products/emerald-saree.jpg',
            'alt_text' => 'Emerald Saree Primary',
            'position' => 0,
            'width' => 1080,
            'height' => 1440,
            'byte_size' => 102400,
        ]);

        $colorOption = \App\Models\ProductOption::create([
            'product_id' => $this->product->id,
            'name' => 'Color',
            'position' => 0,
        ]);

        $emeraldValue = \App\Models\ProductOptionValue::create([
            'option_id' => $colorOption->id,
            'value' => 'Emerald Green',
            'position' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'SAREE-EMR-' . uniqid(),
            'price_paise' => 189900,
            'mrp_paise' => 399900,
            'option_signature' => (string) $emeraldValue->id,
            'position' => 0,
            'version' => 0,
        ]);
        $variant->optionValues()->attach($emeraldValue->id, ['option_id' => $colorOption->id]);

        // Generate tiny 1080x1920 test mp4 via FFmpeg and store in fake public storage
        $localDummy = storage_path('app/temp/test_source_' . uniqid() . '.mp4');
        File::ensureDirectoryExists(dirname($localDummy));
        exec("ffmpeg -y -f lavfi -i color=c=black:s=1080x1920:d=1 -c:v libx264 {$localDummy} 2>&1");

        $this->dummyVideoPath = 'video-studio/videos/test_reel_source_' . uniqid() . '.mp4';
        Storage::disk('public')->put($this->dummyVideoPath, File::get($localDummy));
        @unlink($localDummy);

        $this->video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Emerald Silk Saree Showcase',
            'video_type' => 'imported_branded',
            'template' => 'watch_in_motion',
            'aspect_ratio' => '9:16',
            'width' => 1080,
            'height' => 1920,
            'duration_seconds' => 5,
            'status' => 'completed',
            'approval_status' => 'approved',
            'progress' => 100,
            'video_path' => $this->dummyVideoPath,
            'render_settings' => [
                'is_ai_generated' => true,
            ],
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_download_watch_in_motion_reel_from_product_catalog(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.videos.download-reel', $this->video));

        $response->assertOk();
        $response->assertHeader('content-type', 'video/mp4');

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('watch-in-motion-reel.mp4', $disposition);

        // Verify that reel_video_path was cached in render_settings
        $refreshed = $this->video->fresh();
        $this->assertNotEmpty($refreshed->render_settings['reel_video_path']);
        $this->assertTrue(Storage::disk('public')->exists($refreshed->render_settings['reel_video_path']));
    }

    public function test_admin_can_download_watch_in_motion_reel_from_video_studio(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.video-studio.download', ['video' => $this->video, 'format' => 'reel']));

        $response->assertOk();
        $response->assertHeader('content-type', 'video/mp4');

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('watch-in-motion-reel.mp4', $disposition);
    }

    public function test_unauthenticated_user_cannot_download_reel(): void
    {
        $response = $this->get(route('admin.videos.download-reel', $this->video));
        $response->assertRedirect();
    }

    public function test_ffmpeg_service_creates_watch_in_motion_overlay_graphic(): void
    {
        $ffmpeg = app(FfmpegService::class);
        $tempPng = storage_path('app/temp/test_wim_overlay_' . uniqid() . '.png');
        File::ensureDirectoryExists(dirname($tempPng));

        try {
            $ffmpeg->createWatchInMotionOverlayGraphic($this->video, $tempPng, 1080, 1920);

            $this->assertFileExists($tempPng);
            $this->assertGreaterThan(500, filesize($tempPng));

            $imgSize = getimagesize($tempPng);
            $this->assertEquals(1080, $imgSize[0]);
            $this->assertEquals(1920, $imgSize[1]);
            $this->assertEquals(IMAGETYPE_PNG, $imgSize[2]);
        } finally {
            if (file_exists($tempPng)) {
                @unlink($tempPng);
            }
        }
    }

    public function test_video_studio_service_reuses_cached_reel(): void
    {
        $service = app(VideoStudioService::class);

        // First render generates and caches the file
        $firstPath = $service->getOrGenerateWatchInMotionReel($this->video);
        $this->assertFileExists($firstPath);

        $refreshed = $this->video->fresh();
        $cachedRelative = $refreshed->render_settings['reel_video_path'];
        $this->assertNotEmpty($cachedRelative);

        // Second call should return the exact same cached path without error
        $secondPath = $service->getOrGenerateWatchInMotionReel($refreshed);
        $this->assertEquals($firstPath, $secondPath);
    }
}
