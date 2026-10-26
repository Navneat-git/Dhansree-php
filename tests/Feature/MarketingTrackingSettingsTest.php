<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\StoreSettings;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MarketingTrackingSettingsTest extends TestCase
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
            'email' => 'tracking_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Tracking Admin',
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

    public function test_admin_can_update_gtm_and_meta_pixel_settings(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.settings.update'), [
                'section' => 'social_seo',
                'gtm_container_id' => 'GTM-TEST1234',
                'ads_meta_pixel_id' => '987654321098765',
                'analytics_google_measurement_id' => 'G-TESTGA4XYZ',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('active_tab', 'social_seo');

        $settings = StoreSettings::current();
        $this->assertSame('GTM-TEST1234', $settings->gtm_container_id);
        $this->assertSame('987654321098765', $settings->ads_meta_pixel_id);
        $this->assertSame('G-TESTGA4XYZ', $settings->analytics_google_measurement_id);
    }

    public function test_admin_can_update_ga4_measurement_id_alone_with_sanitization(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.settings.update'), [
                'section' => 'social_seo',
                'analytics_google_measurement_id' => '  g-99abcxyz88  ',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('active_tab', 'social_seo');

        $settings = StoreSettings::current();
        $this->assertSame('G-99ABCXYZ88', $settings->analytics_google_measurement_id);

        // Storefront renders gtag.js script with the new ID
        $frontResponse = $this->get('/');
        $frontResponse->assertStatus(200);
        $frontResponse->assertSee('googletagmanager.com/gtag/js?id=G-99ABCXYZ88', false);
        $frontResponse->assertSee("gtag('config', 'G-99ABCXYZ88');", false);
    }

    public function test_storefront_renders_gtm_and_meta_pixel_tags(): void
    {
        $settings = StoreSettings::current();
        $settings->gtm_container_id = 'GTM-DEMO999';
        $settings->ads_meta_pixel_id = '112233445566778';
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('googletagmanager.com/gtm.js', false);
        $response->assertSee("'dataLayer','GTM-DEMO999'", false);
        $response->assertSee('https://www.googletagmanager.com/ns.html?id=GTM-DEMO999', false);
        $response->assertSee("fbq('init', '112233445566778');", false);
        $response->assertSee('window.dhansreeTrack = function', false);
    }
}
