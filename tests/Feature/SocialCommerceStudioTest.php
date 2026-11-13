<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialSetting;
use App\Models\SocialSupplier;
use App\Services\SocialCommerce\CaptionGeneratorService;
use App\Services\SocialCommerce\SocialProductService;
use App\Services\SocialCommerce\SupplierMediaService;
use App\Services\SocialCommerce\WhatsAppStatusService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SocialCommerceStudioTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();

        // Ensure storage directory is initialized
        Storage::fake('public');

        $superRole = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'social_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Social Commerce Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($superRole->id);

        $session = AdminSessionManager::createSession($this->admin);
        $this->adminToken = $session['token'];
    }

    protected function authGet(string $url)
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->adminToken)
            ->get($url);
    }

    protected function authPost(string $url, array $data = [])
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->adminToken)
            ->post($url, $data);
    }

    protected function authPostJson(string $url, array $data = [])
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->adminToken)
            ->postJson($url, $data);
    }

    /**
     * Test 1: Verify all 9 submenus and core pages render 200 OK without errors.
     */
    public function test_all_social_commerce_studio_submenus_render_200_ok(): void
    {
        $urls = [
            '/admin/social-commerce/dashboard',
            '/admin/social-commerce/inbox',
            '/admin/social-commerce/products',
            '/admin/social-commerce/products/create',
            '/admin/social-commerce/reels',
            '/admin/social-commerce/reels/create',
            '/admin/social-commerce/whatsapp-status',
            '/admin/social-commerce/queue',
            '/admin/social-commerce/history',
            '/admin/social-commerce/connections',
            '/admin/social-commerce/settings',
        ];

        foreach ($urls as $url) {
            $response = $this->authGet($url);
            $response->assertStatus(200, "Failed to load {$url}");
        }
    }

    /**
     * Test 2: Upload multiple images with duplicate detection (SHA-256 exact match).
     */
    public function test_media_upload_and_duplicate_detection(): void
    {
        $supplier = SocialSupplier::create([
            'name' => 'Surat Textile Wholesalers',
            'whatsapp_group_name' => 'Surat Kurtis Group A',
        ]);

        // Create 2 visually distinct image files
        $img1 = imagecreatetruecolor(400, 400);
        imagefilledrectangle($img1, 0, 0, 400, 400, imagecolorallocate($img1, 255, 0, 0));
        imageline($img1, 0, 0, 400, 400, imagecolorallocate($img1, 255, 255, 255));
        $path1 = tempnam(sys_get_temp_dir(), 'test_img1_') . '.jpg';
        imagejpeg($img1, $path1);
        imagedestroy($img1);

        $img2 = imagecreatetruecolor(400, 400);
        imagefilledrectangle($img2, 0, 0, 400, 400, imagecolorallocate($img2, 0, 0, 255));
        for ($x = 0; $x < 400; $x += 20) {
            imageline($img2, $x, 0, $x, 400, imagecolorallocate($img2, 0, 255, 0));
        }
        $path2 = tempnam(sys_get_temp_dir(), 'test_img2_') . '.jpg';
        imagejpeg($img2, $path2);
        imagedestroy($img2);

        $file1 = new UploadedFile($path1, 'suit_design_1.jpg', 'image/jpeg', null, true);
        $file2 = new UploadedFile($path2, 'suit_design_2.jpg', 'image/jpeg', null, true);

        // Upload first batch with two distinct images
        $response = $this->authPost('/admin/social-commerce/inbox/upload', [
            'files' => [$file1, $file2],
            'supplier_id' => $supplier->id,
            'supplier_group_name' => 'Surat Kurtis Group A',
            'category_name' => "Women's Suits",
        ]);

        $response->assertRedirect('/admin/social-commerce/inbox');
        $this->assertEquals(2, SocialMediaAsset::where('supplier_id', $supplier->id)->count());

        // Upload duplicate file1 again
        $duplicateFile = new UploadedFile($path1, 'suit_design_1_copy.jpg', 'image/jpeg', null, true);
        $duplicateResponse = $this->authPost('/admin/social-commerce/inbox/upload', [
            'files' => [$duplicateFile],
            'supplier_id' => $supplier->id,
        ]);

        $duplicateResponse->assertRedirect('/admin/social-commerce/inbox');
        // Total count should still be 2 because duplicate was skipped
        $this->assertEquals(2, SocialMediaAsset::where('supplier_id', $supplier->id)->count());

        @unlink($path1);
        @unlink($path2);
    }

    /**
     * Test 3: ZIP archive upload and unpacking.
     */
    public function test_zip_archive_import_unpacks_images(): void
    {
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create 2 visually distinct image patterns
        $imgData1 = imagecreatetruecolor(200, 200);
        imagefilledrectangle($imgData1, 0, 0, 200, 200, imagecolorallocate($imgData1, 200, 50, 50));
        imagestring($imgData1, 5, 20, 20, 'DESIGN 1', imagecolorallocate($imgData1, 255, 255, 255));
        ob_start();
        imagejpeg($imgData1);
        $jpgBytes1 = ob_get_clean();
        imagedestroy($imgData1);

        $imgData2 = imagecreatetruecolor(300, 300);
        imagefilledrectangle($imgData2, 0, 0, 300, 300, imagecolorallocate($imgData2, 50, 50, 200));
        for ($i = 0; $i < 300; $i += 15) {
            imageline($imgData2, 0, $i, 300, $i, imagecolorallocate($imgData2, 255, 255, 0));
        }
        ob_start();
        imagejpeg($imgData2);
        $jpgBytes2 = ob_get_clean();
        imagedestroy($imgData2);

        $zip->addFromString('catalog/anarkali_01.jpg', $jpgBytes1);
        $zip->addFromString('catalog/anarkali_02.jpg', $jpgBytes2);
        $zip->addFromString('catalog/.DS_Store', 'junk'); // should be ignored
        $zip->close();

        $uploadedZip = new UploadedFile($tempZipPath, 'supplier_catalog.zip', 'application/zip', null, true);

        $response = $this->authPost('/admin/social-commerce/inbox/upload-zip', [
            'zip_file' => $uploadedZip,
            'supplier_name' => 'Jaipur Anarkali Artisans',
            'category_name' => 'Anarkali Suits',
        ]);

        $response->assertRedirect('/admin/social-commerce/inbox');

        $supplier = SocialSupplier::where('name', 'Jaipur Anarkali Artisans')->first();
        $this->assertNotNull($supplier);

        $assets = SocialMediaAsset::where('supplier_id', $supplier->id)->get();
        $this->assertCount(2, $assets);

        @unlink($tempZipPath);
    }

    /**
     * Test 4: Convert selected media inbox assets to Product Library item.
     */
    public function test_convert_media_assets_to_product_library_item(): void
    {
        $supplier = SocialSupplier::create(['name' => 'Delhi Wholesale Hub']);

        $asset1 = SocialMediaAsset::create([
            'supplier_id' => $supplier->id,
            'file_path' => 'social-commerce/media/img1.jpg',
            'file_name' => 'img1.jpg',
            'file_hash' => hash('sha256', 'dummy1'),
            'status' => 'UNPROCESSED',
        ]);

        $asset2 = SocialMediaAsset::create([
            'supplier_id' => $supplier->id,
            'file_path' => 'social-commerce/media/img2.jpg',
            'file_name' => 'img2.jpg',
            'file_hash' => hash('sha256', 'dummy2'),
            'status' => 'UNPROCESSED',
        ]);

        $initialWebProductCount = Product::count();

        $response = $this->authPost('/admin/social-commerce/inbox/move-to-product', [
            'asset_ids' => [$asset1->id, $asset2->id],
            'product_name' => 'Royal Velvet Embroidered Kaftan',
            'category_name' => 'Kaftans',
            'supplier_id' => $supplier->id,
            'fabric' => 'Pure Velvet',
            'purchase_price' => '650.00',
            'suggested_selling_price' => '1499.00',
            'special_offer_price' => '999.00',
            'available_colors' => 'Navy, Maroon',
            'available_sizes' => 'M, L, XL',
        ]);

        $product = SocialProduct::where('name', 'Royal Velvet Embroidered Kaftan')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('DS-SP-', $product->product_code);
        $this->assertEquals(99900, $product->special_offer_price_paise);
        $this->assertEquals('Kaftans', $product->category_name);
        $this->assertEquals('NEW', $product->availability_status);
        $this->assertFalse($product->is_promoted_to_web);

        // Crucial requirement: Verify supplier products are NOT published to public website store
        $this->assertEquals($initialWebProductCount, Product::count(), 'Supplier product must not be added to web store products!');

        // Check assets marked IN_PRODUCT
        $asset1->refresh();
        $this->assertEquals('IN_PRODUCT', $asset1->status);
    }

    /**
     * Test 5: Caption generation without hallucinating attributes.
     */
    public function test_caption_generator_strictly_formats_attributes(): void
    {
        $captionService = app(CaptionGeneratorService::class);

        $product = SocialProduct::create([
            'product_code' => 'DS-SP-9999',
            'name' => 'Chanderi Silk Printed Kurti',
            'category_name' => 'Kurtis',
            'fabric' => 'Chanderi Silk',
            'available_colors' => ['Mustard', 'Teal'],
            'available_sizes' => ['M', 'L', 'XL'],
            'suggested_selling_price_paise' => 129900,
            'special_offer_price_paise' => 79900,
            'availability_status' => 'AVAILABLE',
        ]);

        $result = $captionService->generateCaption($product, 'new_arrival', 'INSTAGRAM');

        $captionText = $result['caption_text'];
        $this->assertStringContainsString('Chanderi Silk Printed Kurti', $captionText);
        $this->assertStringContainsString('Kurtis', $captionText);
        $this->assertStringContainsString('Chanderi Silk', $captionText);
        $this->assertStringContainsString('₹799', $captionText);
        $this->assertStringContainsString('Mustard, Teal', $captionText);
        $this->assertStringContainsString('M, L, XL', $captionText);
        $this->assertStringContainsString('#Dhansree', $result['hashtags']);
        $this->assertStringContainsString('#Kurtis', $result['hashtags']);
    }

    /**
     * Test 6: WhatsApp Status 9:16 Canvas Generation & ZIP Export.
     */
    public function test_whatsapp_status_generation_and_zip_export(): void
    {
        $statusService = app(WhatsAppStatusService::class);

        // Create a real sample image on disk
        $sampleImg = imagecreatetruecolor(600, 800);
        $bgColor = imagecolorallocate($sampleImg, 220, 100, 100);
        imagefilledrectangle($sampleImg, 0, 0, 600, 800, $bgColor);
        $samplePath = storage_path('app/public/test_status_input.jpg');
        File::ensureDirectoryExists(dirname($samplePath));
        imagejpeg($sampleImg, $samplePath);
        imagedestroy($sampleImg);

        $asset = SocialMediaAsset::create([
            'file_path' => 'test_status_input.jpg',
            'file_name' => 'test_status_input.jpg',
            'file_hash' => hash('sha256', 'dummy_status_hash'),
            'media_type' => 'IMAGE',
            'status' => 'PROCESSED',
        ]);

        $product = SocialProduct::create([
            'product_code' => 'DS-SP-8888',
            'name' => 'Bandhani Festive Kurti Set',
            'category_name' => 'Kurtis',
            'fabric' => 'Cotton Bandhani',
            'special_offer_price_paise' => 89900,
            'availability_status' => 'AVAILABLE',
            'primary_media_asset_id' => $asset->id,
        ]);

        // Render card
        $cardPath = $statusService->renderStatusCard($asset, $product, [
            'brand_name' => 'Dhansree',
            'tagline' => 'STYLE. TECH. EVERYDAY.',
            'whatsapp_number' => '+919887776777',
            'cta_text' => 'WhatsApp to Order',
        ]);

        $this->assertFileExists(storage_path('app/public/' . $cardPath));

        // Check image dimensions are exactly 1080 x 1920
        [$w, $h] = getimagesize(storage_path('app/public/' . $cardPath));
        $this->assertEquals(1080, $w);
        $this->assertEquals(1920, $h);

        // Test rendering card with show_price = false
        $noPriceCardPath = $statusService->renderStatusCard($asset, $product, [
            'show_price' => false,
            'whatsapp_number' => '+919887776777',
            'cta_text' => 'DM OR WHATSAPP US TO ORDER',
        ]);
        $this->assertFileExists(storage_path('app/public/' . $noPriceCardPath));
        [$noPriceW, $noPriceH] = getimagesize(storage_path('app/public/' . $noPriceCardPath));
        $this->assertEquals(1080, $noPriceW);
        $this->assertEquals(1920, $noPriceH);

        // Test AJAX generate endpoint with show_price = false
        $ajaxResponse = $this->authPostJson('/admin/social-commerce/whatsapp-status/generate', [
            'asset_id' => $asset->id,
            'product_id' => $product->id,
            'show_price' => false,
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['success' => true]);

        // Test Single Download endpoint
        $downloadRes = $this->authGet('/admin/social-commerce/whatsapp-status/download?path=' . urlencode($cardPath));
        $downloadRes->assertStatus(200);
        $downloadRes->assertHeader('Content-Type', 'image/jpeg');

        // Test ZIP export
        $zipPath = $statusService->createZipPackage([$cardPath], 'test_pack.zip');
        $this->assertFileExists($zipPath);

        @unlink($samplePath);
        @unlink(storage_path('app/public/' . $cardPath));
        @unlink(storage_path('app/public/' . $noPriceCardPath));
        @unlink($zipPath);
    }

    /**
     * Test 7: Explicit promotion to public web storefront.
     */
    public function test_explicit_promote_to_web_creates_active_web_product(): void
    {
        $productService = app(SocialProductService::class);

        $socialProduct = SocialProduct::create([
            'product_code' => 'DS-SP-7777',
            'name' => 'Handblock Chanderi Suit',
            'category_name' => "Women's Suits",
            'fabric' => 'Chanderi',
            'suggested_selling_price_paise' => 159900,
            'special_offer_price_paise' => 119900,
            'purchase_price_paise' => 60000,
            'availability_status' => 'AVAILABLE',
            'is_promoted_to_web' => false,
        ]);

        $this->assertFalse($socialProduct->is_promoted_to_web);
        $this->assertNull($socialProduct->web_product_id);

        // Explicit promotion
        $webProduct = $productService->promoteToWeb($socialProduct);

        $this->assertInstanceOf(Product::class, $webProduct);
        $socialProduct->refresh();
        $this->assertTrue($socialProduct->is_promoted_to_web);
        $this->assertEquals($webProduct->id, $socialProduct->web_product_id);
    }

    /**
     * Test 8: Reel creation and video rendering via SocialReelRendererService.
     */
    public function test_reel_creation_and_video_rendering_job(): void
    {
        // Create sample product image on public disk
        $img = imagecreatetruecolor(600, 800);
        imagefilledrectangle($img, 0, 0, 600, 800, imagecolorallocate($img, 88, 0, 0));
        $imgPath = storage_path('app/public/sample_reel_suit.jpg');
        File::ensureDirectoryExists(dirname($imgPath));
        imagejpeg($img, $imgPath);
        imagedestroy($img);

        $asset = SocialMediaAsset::create([
            'file_path' => 'sample_reel_suit.jpg',
            'file_name' => 'sample_reel_suit.jpg',
            'file_hash' => hash('sha256', 'sample_reel_suit_hash'),
            'media_type' => 'IMAGE',
            'status' => 'PROCESSED',
        ]);

        $product = SocialProduct::create([
            'product_code' => 'DS-SP-9999',
            'name' => 'Festive Anarkali Suit',
            'category_name' => 'Anarkali Suits',
            'fabric' => 'Georgette',
            'special_offer_price_paise' => 129900,
            'availability_status' => 'AVAILABLE',
        ]);

        // Create reel
        $reel = \App\Models\SocialReel::create([
            'reel_code' => 'REEL-TEST-001',
            'title' => 'Festive Anarkali Showcase',
            'template_code' => 'FESTIVE_COLLECTION',
            'aspect_ratio' => '9:16',
            'width' => 1080,
            'height' => 1920,
            'duration_seconds' => 10,
            'status' => 'DRAFT',
            'caption' => '✨ New Festive Anarkali ✨ Order on WhatsApp +919887776777',
            'audio_track' => 'festive_royal',
        ]);

        \App\Models\SocialReelItem::create([
            'social_reel_id' => $reel->id,
            'social_product_id' => $product->id,
            'media_asset_id' => $asset->id,
            'position' => 0,
            'headline' => 'Royal Festive Anarkali',
            'price_text' => '₹1,299',
            'display_seconds' => 5.0,
        ]);

        // Test render endpoint triggers redirect and status change
        $renderRes = $this->authPost("/admin/social-commerce/reels/{$reel->id}/render");
        $renderRes->assertRedirect();

        // Run rendering service directly
        $renderer = app(\App\Services\SocialCommerce\SocialReelRendererService::class);
        $result = $renderer->renderReel($reel);

        $reel->refresh();
        $this->assertEquals('COMPLETED', $reel->status);
        $this->assertEquals(100, $reel->progress);
        $this->assertNotNull($reel->video_path);
        $this->assertNotNull($reel->thumbnail_path);

        Storage::disk('public')->assertExists($reel->video_path);
        Storage::disk('public')->assertExists($reel->thumbnail_path);

        @unlink($imgPath);
    }

    /**
     * Test 9: Publishing Queue approval gating and Meta publishing execution.
     */
    public function test_publishing_queue_approval_gate_and_meta_publishing(): void
    {
        $queueItem = \App\Models\SocialPublishingQueue::create([
            'title' => 'Royal Chanderi Reel Release',
            'content_type' => 'REEL',
            'caption_instagram' => 'Chanderi suits back in stock! DM to order.',
            'caption_facebook' => 'Festive Chanderi suits available now.',
            'platforms' => ['INSTAGRAM', 'FACEBOOK'],
            'status' => 'AWAITING_APPROVAL',
        ]);

        $publishingService = app(\App\Services\SocialCommerce\MetaPublishingService::class);

        // Trying to publish an unapproved item must throw an exception
        try {
            $publishingService->publishQueueItem($queueItem);
            $this->fail('Expected exception was not thrown for unapproved queue item.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Explicit admin approval is required', $e->getMessage());
        }

        // Approve item via web endpoint
        $approveRes = $this->authPost("/admin/social-commerce/queue/{$queueItem->id}/approve");
        $approveRes->assertRedirect();
        $queueItem->refresh();
        $this->assertEquals('APPROVED', $queueItem->status);

        // Publish via MetaPublishingService (in mock/test environment)
        $outcome = $publishingService->publishQueueItem($queueItem);

        $queueItem->refresh();
        $this->assertEquals('PUBLISHED', $queueItem->status);
        $this->assertNotNull($queueItem->published_at);

        // Verify individual logs exist for both platforms
        $logs = $queueItem->logs;
        $this->assertCount(2, $logs);
        $this->assertTrue($logs->contains('platform', 'INSTAGRAM'));
        $this->assertTrue($logs->contains('platform', 'FACEBOOK'));

        foreach ($logs as $log) {
            $this->assertEquals('SUCCESS', $log->status);
            $this->assertNotNull($log->post_id);
            $this->assertNotNull($log->post_url);
        }
    }

    /**
     * Test 10: Partial failure retry isolation (Instagram succeeded, Facebook retried).
     */
    public function test_partial_failure_retry_isolation(): void
    {
        $queueItem = \App\Models\SocialPublishingQueue::create([
            'title' => 'Daily Kurti Drop',
            'content_type' => 'REEL',
            'caption_instagram' => 'Daily wear kurtis.',
            'caption_facebook' => 'Daily wear kurtis.',
            'platforms' => ['INSTAGRAM', 'FACEBOOK'],
            'status' => 'APPROVED',
        ]);

        // Pre-create an existing SUCCESS log for Instagram
        \App\Models\SocialPublishingLog::create([
            'queue_item_id' => $queueItem->id,
            'platform' => 'INSTAGRAM',
            'status' => 'SUCCESS',
            'post_id' => 'ig_existing_12345',
            'post_url' => 'https://www.instagram.com/reel/ig_existing_12345/',
            'published_at' => now(),
        ]);

        $publishingService = app(\App\Services\SocialCommerce\MetaPublishingService::class);
        $outcome = $publishingService->publishQueueItem($queueItem);

        // Instagram should have been skipped, and Facebook published
        $this->assertEquals('SUCCESS', $outcome['logs']['INSTAGRAM']['status']);
        $this->assertTrue($outcome['logs']['INSTAGRAM']['skipped'] ?? false);
        $this->assertEquals('ig_existing_12345', $outcome['logs']['INSTAGRAM']['post_id']);

        $this->assertEquals('SUCCESS', $outcome['logs']['FACEBOOK']['status']);

        // Overall status should now be PUBLISHED
        $queueItem->refresh();
        $this->assertEquals('PUBLISHED', $queueItem->status);
    }
}
