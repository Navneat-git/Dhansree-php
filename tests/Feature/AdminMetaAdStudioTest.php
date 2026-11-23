<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\MetaAdCreative;
use App\Models\Product;
use App\Models\SocialSetting;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMetaAdStudioTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $rawAdminToken;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'meta_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Meta Ad Tester',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawAdminToken = $sessionData['token'];

        $this->product = Product::active()->whereNull('archived_at')->first()
            ?: Product::first();
    }

    private function asAdmin()
    {
        return $this->withToken($this->rawAdminToken)
            ->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawAdminToken)
            ->withSession(['admin_session_token' => $this->rawAdminToken]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin/meta-ads');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_meta_ad_studio_index(): void
    {
        $response = $this->asAdmin()->get('/admin/meta-ads');

        $response->assertStatus(200);
        $response->assertSee('Meta Ad Studio');
        $response->assertSee('Shop Now');
        $response->assertSee('Dhansree Smart AI');
        $response->assertSee('Google Gemini');
        $response->assertSee('ChatGPT / OpenAI');
        $response->assertSee('Facebook');
        $response->assertSee('Instagram');
    }

    public function test_admin_can_fetch_product_data_json(): void
    {
        $this->assertNotNull($this->product, 'Product should exist in test database');

        $response = $this->asAdmin()
            ->getJson("/admin/meta-ads/product-data/{$this->product->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ],
        ]);
    }

    public function test_admin_can_generate_ad_copy_with_smart_ai(): void
    {
        $this->assertNotNull($this->product);

        $response = $this->asAdmin()->postJson('/admin/meta-ads/generate', [
            'product_id' => $this->product->id,
            'provider' => 'smart_ai',
            'angle' => 'all',
            'custom_instructions' => 'Include Navratri Special Discount',
            'utm_campaign' => 'festive_diwali_meta',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'product' => ['id', 'name', 'formatted_price'],
                'provider_used',
                'angles' => [
                    'festive' => ['primary_text', 'headline', 'description', 'cta', 'destination_url'],
                    'comfort' => ['primary_text', 'headline', 'description', 'cta', 'destination_url'],
                    'offer' => ['primary_text', 'headline', 'description', 'cta', 'destination_url'],
                    'viral' => ['primary_text', 'headline', 'description', 'cta', 'destination_url'],
                    'fit' => ['primary_text', 'headline', 'description', 'cta', 'destination_url'],
                ],
            ],
        ]);

        $responseData = $response->json('data.angles.festive');
        $this->assertSame('Shop Now', $responseData['cta']);
        $this->assertStringContainsString('utm_source=meta', $responseData['destination_url']);
        $this->assertStringContainsString('utm_medium=paid_social', $responseData['destination_url']);
        $this->assertStringContainsString('utm_campaign=festive-diwali-meta', $responseData['destination_url']);
        $this->assertStringContainsString('Navratri Special Discount', $responseData['primary_text']);
    }

    public function test_admin_can_save_generated_creative_to_library(): void
    {
        $this->assertNotNull($this->product);

        $response = $this->asAdmin()->postJson('/admin/meta-ads/save', [
            'product_id' => $this->product->id,
            'campaign_name' => 'Jaipur Festive Kurties Q4',
            'ad_name' => 'Royal Festive Angle 1',
            'angle' => 'festive',
            'primary_text' => '👑 Turn Heads At Every Festive Occasion! Handcrafted pure cotton kurtis from Jaipur.',
            'headline' => 'Jaipur Kurti Sets - Flat 40% Off',
            'description' => 'Cash on Delivery | Free Shipping',
            'cta' => 'Shop Now',
            'destination_url' => url("/product/{$this->product->slug}?utm_source=meta&utm_medium=paid_social"),
            'utm_campaign' => 'festive_q4',
            'ai_provider' => 'smart_ai',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('meta_ad_creatives', [
            'product_id' => $this->product->id,
            'angle' => 'festive',
            'headline' => 'Jaipur Kurti Sets - Flat 40% Off',
            'cta' => 'Shop Now',
        ]);
    }

    public function test_admin_can_delete_saved_creative(): void
    {
        $creative = MetaAdCreative::create([
            'product_id' => $this->product->id,
            'campaign_name' => 'Test Campaign',
            'ad_name' => 'Test Ad',
            'angle' => 'comfort',
            'primary_text' => 'Test primary text for comfort',
            'headline' => 'Pure Cotton Comfort',
            'description' => 'COD Available',
            'cta' => 'Shop Now',
            'destination_url' => url('/product/test'),
            'ai_provider' => 'smart_ai',
        ]);

        $response = $this->asAdmin()
            ->deleteJson("/admin/meta-ads/{$creative->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('meta_ad_creatives', [
            'id' => $creative->id,
        ]);
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->asAdmin()->post('/admin/meta-ads/settings', [
            'gemini_api_key' => 'AIzaSyTestMockKey12345',
            'openai_api_key' => 'sk-test-mock-key-67890',
            'default_ai_provider' => 'gemini',
            'default_utm_campaign' => 'diwali_mega_sale_2026',
        ]);

        $response->assertRedirect();
        $this->assertSame('AIzaSyTestMockKey12345', SocialSetting::get('gemini_api_key'));
        $this->assertSame('sk-test-mock-key-67890', SocialSetting::get('openai_api_key'));
        $this->assertSame('gemini', SocialSetting::get('default_ai_provider'));
        $this->assertSame('diwali_mega_sale_2026', SocialSetting::get('default_utm_campaign'));
    }
}
