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

    public function test_storefront_renders_facebook_domain_verification_meta_tag(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('<meta name="facebook-domain-verification" content="jaur6cwxsewazavl4qh5goy7ylgewz" />', false);
    }

    public function test_admin_can_update_facebook_domain_verification_with_code_or_full_meta_tag(): void
    {
        // 1. Update with bare code
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.settings.update'), [
                'section' => 'social_seo',
                'facebook_domain_verification' => 'abc123xyztoken',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = StoreSettings::current();
        $this->assertSame('abc123xyztoken', $settings->facebook_domain_verification);

        $frontResponse = $this->get('/');
        $frontResponse->assertSee('<meta name="facebook-domain-verification" content="abc123xyztoken" />', false);

        // 2. Update with full <meta ...> tag copied directly from Meta Business Manager
        $response2 = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.settings.update'), [
                'section' => 'social_seo',
                'facebook_domain_verification' => '<meta name="facebook-domain-verification" content="jaur6cwxsewazavl4qh5goy7ylgewz" />',
            ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $settings->refresh();
        $this->assertSame('jaur6cwxsewazavl4qh5goy7ylgewz', $settings->facebook_domain_verification);

        $frontResponse2 = $this->get('/');
        $frontResponse2->assertSee('<meta name="facebook-domain-verification" content="jaur6cwxsewazavl4qh5goy7ylgewz" />', false);
    }

    public function test_storefront_renders_meta_pixel_code_by_default(): void
    {
        $settings = StoreSettings::current();
        $settings->ads_meta_pixel_id = '1783488586189258';
        $settings->save();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('<!-- Meta Pixel Code -->', false);
        $response->assertSee("fbq('init', '1783488586189258');", false);
        $response->assertSee("fbq('track', 'PageView');", false);
        $response->assertSee('https://www.facebook.com/tr?id=1783488586189258&ev=PageView&noscript=1', false);
        $response->assertSee('<!-- End Meta Pixel Code -->', false);
    }

    public function test_storefront_renders_meta_pixel_code_fallback_when_setting_is_null(): void
    {
        $settings = StoreSettings::current();
        $settings->ads_meta_pixel_id = null;
        $settings->save();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee("fbq('init', '1783488586189258');", false);
        $response->assertSee('https://www.facebook.com/tr?id=1783488586189258&ev=PageView&noscript=1', false);
    }

    public function test_admin_can_update_ads_meta_pixel_id_with_code_or_full_snippet(): void
    {
        $snippet = <<<HTML
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1783488586189258');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1783488586189258&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
HTML;

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.settings.update'), [
                'section' => 'social_seo',
                'ads_meta_pixel_id' => $snippet,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = StoreSettings::current();
        $this->assertSame('1783488586189258', $settings->ads_meta_pixel_id);
    }

    public function test_admin_can_update_and_blank_out_social_urls_without_default_reversion(): void
    {
        // 1. Update with specific Instagram and Facebook, leaving YouTube, Pinterest, and X blank
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.settings.update'), [
                'section' => 'social_seo',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'facebook_url' => 'https://www.facebook.com/share/1Psrp8MVwW/',
                'youtube_url' => '',
                'pinterest_url' => '',
                'x_url' => '',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = StoreSettings::current();
        $this->assertSame('https://www.instagram.com/dhansreetrends', $settings->instagram_url);
        $this->assertSame('https://www.facebook.com/share/1Psrp8MVwW/', $settings->facebook_url);
        $this->assertNull($settings->youtube_url);
        $this->assertNull($settings->pinterest_url);
        $this->assertNull($settings->x_url);

        // 2. Verify admin view shows empty values and X field exists without forcing old defaults
        $viewRes = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.settings.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('name="x_url"', false);
        $viewRes->assertDontSee('https://youtube.com/@dhansree');
        $viewRes->assertDontSee('https://instagram.com/dhansree_jaipur');
        $viewRes->assertDontSee('https://facebook.com/dhansree');

        // 3. Verify storefront footer only renders Instagram and Facebook, not YouTube, Pinterest or X
        $storeRes = $this->get('/');
        $storeRes->assertStatus(200);
        $storeRes->assertSee('https://www.instagram.com/dhansreetrends');
        $storeRes->assertSee('https://www.facebook.com/share/1Psrp8MVwW/');
        $storeRes->assertDontSee('youtube.com');
        $storeRes->assertDontSee('pinterest.com');
        $storeRes->assertDontSee('x.com/dhansree');
    }
}
