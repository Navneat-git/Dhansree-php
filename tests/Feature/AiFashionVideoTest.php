<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Jobs\AiFashionVideoJob;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Services\VideoStudio\FashionVideoPromptService;
use App\Services\VideoStudio\GoogleVideoGenerationService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiFashionVideoTest extends TestCase
{
    use DatabaseTransactions;

    protected string $rawSessionToken;
    protected AdminUser $admin;
    protected Product $product;
    protected ProductImage $image1;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable mock mode for testing so no real API calls or paid credits are used
        Config::set('video_studio.google_video.mock', true);
        Config::set('video_studio.google_video.enabled', true);

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'admin_ai_' . uniqid() . '@dhansree.test',
            'name' => 'AI Studio Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];

        // Create an ethnic wear Anarkali product
        $this->product = Product::create([
            'name' => 'Noor Royal Embroidered Anarkali Suit Set',
            'slug' => 'noor-royal-anarkali-suit-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'ranking' => 1,
            'version' => 1,
        ]);

        $mediaDir = public_path('media/test-studio');
        File::ensureDirectoryExists($mediaDir);
        $testImgPath = $mediaDir . '/slide_anarkali.png';

        if (!file_exists($testImgPath)) {
            $im = imagecreatetruecolor(800, 1000);
            $c = imagecolorallocate($im, 88, 0, 0);
            imagefilledrectangle($im, 0, 0, 800, 1000, $c);
            imagepng($im, $testImgPath);
            imagedestroy($im);
        }

        ProductImage::where('storage_key', 'test-studio/slide_anarkali.png')->delete();

        $this->image1 = ProductImage::create([
            'product_id' => $this->product->id,
            'storage_key' => 'test-studio/slide_anarkali.png',
            'alt_text' => 'Anarkali Front View',
            'position' => 0,
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

    public function test_prompt_service_builds_garment_fidelity_prompt(): void
    {
        $promptService = app(FashionVideoPromptService::class);
        $prompt = $promptService->buildPrompt($this->product, 'runway_walk', 'Dramatic wind effect');

        $this->assertStringContainsString('Anarkali', $prompt);
        $this->assertStringContainsString('GARMENT FIDELITY', $prompt);
        $this->assertStringContainsString('EXACT garment', $prompt);
        $this->assertStringContainsString('Dramatic wind effect', $prompt);

        $negativePrompt = $promptService->getNegativePrompt();
        $this->assertStringContainsString('blurry', $negativePrompt);
        $this->assertStringContainsString('distorted face', $negativePrompt);
    }

    public function test_prompt_preview_endpoint_returns_json(): void
    {
        $response = $this->adminRequest()
            ->postJson(route('admin.video-studio.preview-prompt'), [
                'product_id' => $this->product->id,
                'style' => 'editorial_studio',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'style' => 'editorial_studio',
            ]);

        $this->assertNotEmpty($response->json('prompt'));
        $this->assertStringContainsString('GARMENT FIDELITY', $response->json('prompt'));
    }

    public function test_admin_can_queue_ai_fashion_video(): void
    {
        Queue::fake([AiFashionVideoJob::class]);

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.store'), [
                'product_id' => $this->product->id,
                'video_type' => 'ai_fashion',
                'prompt_template' => 'runway_walk',
                'template' => 'fashion_showcase',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 5,
                'image_ids' => [$this->image1->id],
                'primary_image_id' => $this->image1->id,
                'prompt' => 'Custom model runway walk with suit',
            ]);

        $response->assertRedirect(route('admin.video-studio.index'));

        $this->assertDatabaseHas('product_videos', [
            'product_id' => $this->product->id,
            'video_type' => 'ai_fashion',
            'prompt_template' => 'runway_walk',
            'approval_status' => 'pending_review',
        ]);

        Queue::assertPushed(AiFashionVideoJob::class);
    }

    public function test_admin_can_queue_ai_fashion_branded_video(): void
    {
        Queue::fake([AiFashionVideoJob::class]);

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.store'), [
                'product_id' => $this->product->id,
                'video_type' => 'ai_fashion_branded',
                'prompt_template' => 'slow_motion_turn',
                'template' => 'festive_collection',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 10,
                'image_ids' => [$this->image1->id],
                'primary_image_id' => $this->image1->id,
                'enable_music' => 1,
                'audio_track' => 'festive_royal',
                'call_to_action' => 1,
            ]);

        $response->assertRedirect(route('admin.video-studio.index'));

        $this->assertDatabaseHas('product_videos', [
            'product_id' => $this->product->id,
            'video_type' => 'ai_fashion_branded',
            'approval_status' => 'pending_review',
        ]);

        Queue::assertPushed(AiFashionVideoJob::class);
    }

    public function test_ai_video_guards_when_limits_reached(): void
    {
        Config::set('video_studio.google_video.mock', false);
        Config::set('video_studio.google_video.enabled', true);
        Config::set('video_studio.google_video.api_key', 'test_key');
        Config::set('video_studio.google_video.daily_limit', 0); // 0 quota

        // In testing, mock limit check
        $googleService = $this->mock(GoogleVideoGenerationService::class);
        $googleService->shouldReceive('checkLimits')->andReturn([
            'allowed' => false,
            'reason' => 'Daily AI video quota exceeded.',
        ]);

        $response = $this->adminRequest()
            ->postJson(route('admin.video-studio.store'), [
                'product_id' => $this->product->id,
                'video_type' => 'ai_fashion',
                'template' => 'fashion_showcase',
                'aspect_ratio' => '9:16',
                'duration_seconds' => 5,
                'image_ids' => [$this->image1->id],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Daily AI video quota exceeded.',
            ]);
    }

    public function test_admin_can_view_quality_control_review_screen(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Anarkali Showcase',
            'video_type' => 'ai_fashion_branded',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 5,
            'status' => 'completed',
            'progress' => 100,
            'approval_status' => 'pending_review',
            'primary_image_id' => $this->image1->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->adminRequest()
            ->get(route('admin.video-studio.review', $video));

        $response->assertOk()
            ->assertViewIs('admin.video-studio.review')
            ->assertSee('Quality Control Review')
            ->assertSee('Source of Truth')
            ->assertSee('Approve Video for Public Use');
    }

    public function test_admin_can_approve_ai_video(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Ready Anarkali Video',
            'video_type' => 'ai_fashion',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 5,
            'status' => 'completed',
            'approval_status' => 'pending_review',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.approve', $video), [
                'checklist' => [
                    'garment_match' => 1,
                    'color_fidelity' => 1,
                    'dupatta_motion' => 1,
                    'model_quality' => 1,
                ],
            ]);

        $response->assertRedirect(route('admin.video-studio.index'));

        $video->refresh();
        $this->assertEquals('approved', $video->approval_status);
        $this->assertEquals($this->admin->id, $video->reviewed_by);
        $this->assertNotNull($video->reviewed_at);
        $this->assertTrue($video->isApproved());
    }

    public function test_admin_can_reject_ai_video_with_reason(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Defective Anarkali Video',
            'video_type' => 'ai_fashion',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 5,
            'status' => 'completed',
            'approval_status' => 'pending_review',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.reject', $video), [
                'reason' => 'Dupatta drape was distorted in frame 4.',
            ]);

        $response->assertRedirect(route('admin.video-studio.index'));

        $video->refresh();
        $this->assertEquals('rejected', $video->approval_status);
        $this->assertEquals('Dupatta drape was distorted in frame 4.', $video->rejection_reason);
        $this->assertTrue($video->isRejected());
    }

    public function test_admin_can_regenerate_ai_video(): void
    {
        Queue::fake([AiFashionVideoJob::class]);

        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Regenerate Candidate',
            'video_type' => 'ai_fashion',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 5,
            'status' => 'completed',
            'approval_status' => 'rejected',
            'prompt' => 'Initial prompt',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->adminRequest()
            ->post(route('admin.video-studio.regenerate', $video), [
                'prompt' => 'Updated prompt with slower walk',
            ]);

        $response->assertRedirect(route('admin.video-studio.index'));

        $video->refresh();
        $this->assertEquals('queued', $video->status);
        $this->assertEquals('pending_review', $video->approval_status);
        $this->assertEquals('Updated prompt with slower walk', $video->prompt);

        Queue::assertPushed(AiFashionVideoJob::class);
    }

    public function test_ai_fashion_video_job_executes_successfully_in_mock_mode(): void
    {
        $video = ProductVideo::create([
            'product_id' => $this->product->id,
            'title' => 'Job Execution Test',
            'video_type' => 'ai_fashion_branded',
            'template' => 'fashion_showcase',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 5,
            'status' => 'queued',
            'progress' => 0,
            'approval_status' => 'pending_review',
            'primary_image_id' => $this->image1->id,
            'selected_image_ids' => [$this->image1->id],
            'prompt' => 'Cinematic walk of model in Noor Anarkali',
            'render_settings' => [
                'enable_music' => false,
                'text_overlay' => true,
                'show_price' => true,
                'call_to_action' => true,
            ],
            'created_by' => $this->admin->id,
        ]);

        $job = new AiFashionVideoJob($video->id);
        $job->handle(
            app(\App\Services\VideoStudio\GoogleVideoGenerationService::class),
            app(\App\Services\VideoStudio\FashionVideoPromptService::class),
            app(\App\Services\VideoStudio\FfmpegService::class)
        );

        $video->refresh();
        $this->assertEquals('completed', $video->status);
        $this->assertEquals(100, $video->progress);
        $this->assertEquals('pending_review', $video->approval_status);
        $this->assertNotEmpty($video->video_path);
        $this->assertNotEmpty($video->thumbnail_path);
        $this->assertNotEmpty($video->ai_master_video_path);
        $this->assertGreaterThan(0, $video->file_size_bytes);
    }
}

