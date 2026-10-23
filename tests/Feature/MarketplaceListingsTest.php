<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\MarketplaceTemplate;
use App\Services\Marketplace\MarketplaceListingService;
use App\Support\Auth\AdminSessionManager;
use Tests\TestCase;

class MarketplaceListingsTest extends TestCase
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
            'email' => 'mkt_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Marketplace Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];

        app(MarketplaceListingService::class)->ensureStandardTemplates();
    }

    public function test_admin_can_view_marketplace_listings_screen(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.marketplace.listings'));

        $response->assertStatus(200);
        $response->assertSee('Marketplace Listings & Export');
        $response->assertSee('Meesho');
        $response->assertSee('Flipkart');
        $response->assertSee('Amazon');
    }

    public function test_admin_can_export_marketplace_csv(): void
    {
        $template = MarketplaceTemplate::where('platform', 'MEESHO')->first();
        $this->assertNotNull($template);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.marketplace.export', ['template' => $template->id]));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('Content-Type') ?? '', 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition') ?? '', 'meesho'));

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Product ID / SKU', $content);
        $this->assertStringContainsString('Selling Price', $content);
    }

    public function test_admin_can_copy_tsv(): void
    {
        $template = MarketplaceTemplate::where('platform', 'FLIPKART')->first();
        $this->assertNotNull($template);

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.marketplace.copy', ['template' => $template->id]));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString("\t", $content);
        $this->assertStringContainsString('Seller SKU ID', $content);
    }
}
