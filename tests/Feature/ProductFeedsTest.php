<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Feeds\ProductFeedService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductFeedsTest extends TestCase
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
            'email' => 'feeds_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Feeds Admin',
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

    public function test_admin_can_view_product_feeds_dashboard(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.feeds.index'));

        $response->assertStatus(200);
        $response->assertSee('Product Feeds');
        $response->assertSee('Meta Business Catalog Feed');
        $response->assertSee('Google Merchant Center Feed');
        $response->assertSee('meta_catalog.csv');
        $response->assertSee('google_merchant.csv');
    }

    public function test_admin_can_generate_all_feeds(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.feeds.generate'), ['type' => 'all']);

        $response->assertRedirect(route('admin.feeds.index'));
        $response->assertSessionHas('success');

        $this->assertFileExists(public_path('feeds/meta_catalog.csv'));
        $this->assertFileExists(public_path('feeds/google_merchant.csv'));

        // Check Meta header
        $metaHandle = fopen(public_path('feeds/meta_catalog.csv'), 'r');
        $metaHeader = fgetcsv($metaHandle);
        fclose($metaHandle);
        $this->assertSame(ProductFeedService::META_COLUMNS, $metaHeader);

        // Check Google header
        $googleHandle = fopen(public_path('feeds/google_merchant.csv'), 'r');
        $googleHeader = fgetcsv($googleHandle);
        fclose($googleHandle);
        $this->assertSame(ProductFeedService::GOOGLE_COLUMNS, $googleHeader);
    }

    public function test_admin_can_download_meta_and_google_feeds(): void
    {
        // Meta download
        $metaResp = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.feeds.download', ['type' => 'meta']));
        $metaResp->assertStatus(200);
        $metaResp->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Google download
        $googleResp = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.feeds.download', ['type' => 'google']));
        $googleResp->assertStatus(200);
        $googleResp->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_public_feed_endpoints_serve_csv_for_meta_and_google(): void
    {
        $metaResponse = $this->get('/feeds/meta_catalog.csv');
        $metaResponse->assertStatus(200);
        $metaResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('id,title,description,availability', $metaResponse->getContent());

        $googleResponse = $this->get('/feeds/google_merchant.csv');
        $googleResponse->assertStatus(200);
        $googleResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('id,title,description,link,image_link', $googleResponse->getContent());
    }

    public function test_artisan_feeds_generate_command_executes(): void
    {
        $this->artisan('feeds:generate', ['--type' => 'all'])
            ->expectsOutputToContain('Meta Catalog Feed')
            ->expectsOutputToContain('Google Merchant Feed')
            ->assertExitCode(0);
    }

    public function test_feed_generation_succeeds_even_when_public_feeds_is_readonly(): void
    {
        $feedService = app(ProductFeedService::class);
        $publicDir = public_path('feeds');

        // Make existing files and public directory temporarily read-only
        if (is_dir($publicDir)) {
            foreach (glob($publicDir . '/*.csv') as $f) {
                chmod($f, 0444);
            }
            chmod($publicDir, 0555);
        }

        try {
            // Should not throw Permission denied ErrorException!
            $results = $feedService->generateAllFeeds();
            $this->assertNotEmpty($results['meta']);
            $this->assertNotEmpty($results['google']);

            // Both endpoints should still serve valid CSV feeds
            $metaResp = $this->get('/feeds/meta_catalog.csv');
            $metaResp->assertStatus(200);
            $metaResp->assertHeader('content-type', 'text/csv; charset=UTF-8');

            $googleResp = $this->get('/feeds/google_merchant.csv');
            $googleResp->assertStatus(200);
            $googleResp->assertHeader('content-type', 'text/csv; charset=UTF-8');
        } finally {
            // Restore permissions
            if (is_dir($publicDir)) {
                chmod($publicDir, 0775);
                foreach (glob($publicDir . '/*.csv') as $f) {
                    chmod($f, 0664);
                }
            }
        }
    }
}
