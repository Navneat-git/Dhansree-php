<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\TrackedVisitor;
use App\Models\VisitorPageView;
use App\Models\VisitorSession;
use App\Support\Auth\AdminSessionManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerJourneyTrackingTest extends TestCase
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
            'email' => 'journey_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Journey Admin',
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

    public function test_customer_journey_tracking_captures_platform_utm_and_page_steps(): void
    {
        $campaign = 'diwali_sale_' . uniqid();

        // 1. Visit landing page from Instagram with UTM tags
        $response1 = $this->withHeaders([
            'referer' => 'https://l.instagram.com/',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
        ])->get('/?utm_source=instagram&utm_medium=reel&utm_campaign=' . $campaign);

        $response1->assertStatus(200);

        // Verify session created
        $session = VisitorSession::where('utm_campaign', $campaign)->first();
        $this->assertNotNull($session);
        $this->assertSame('Instagram', $session->platform);
        $this->assertSame('mobile', $session->device_type);
        $this->assertSame('iOS', $session->os);
        $this->assertSame(1, $session->page_views_count);
        $this->assertNotNull($session->customer_code);

        // Verify page view step 1
        $pv1 = VisitorPageView::where('session_id', $session->session_id)->where('step_number', 1)->first();
        $this->assertNotNull($pv1);
        $this->assertSame('/', $pv1->path);

        // 2. Customer explores Category / Store page in the same session
        $response2 = $this->withSession(['customer_journey_session_id' => $session->session_id])
            ->withCookie('dhansree_visitor_id', $session->visitor_uuid)
            ->get('/smart-living');

        $response2->assertStatus(200);

        $session->refresh();
        $this->assertSame(2, $session->page_views_count);

        // Verify page view step 2
        $pv2 = VisitorPageView::where('session_id', $session->session_id)->where('step_number', 2)->first();
        $this->assertNotNull($pv2);
        $this->assertSame('/smart-living', $pv2->path);
        $this->assertSame('Smart Living Hub', $pv2->page_title);
    }

    public function test_admin_can_view_customer_journeys_dashboard(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.analytics.journeys'));

        $response->assertStatus(200);
        $response->assertSee('Customer Journeys & Traffic Explorer', false);
        $response->assertSee('Acquisition Channels');
    }

    public function test_unique_customer_assignment_and_repeat_visitor_recognition_by_ip_and_email(): void
    {
        $ip1 = '103.21.244.' . rand(1, 250);

        // 1. First visit from IP1 -> Brand New Customer
        $res1 = $this->withServerVariables(['REMOTE_ADDR' => $ip1])
            ->get('/');
        $res1->assertStatus(200);

        $session1 = VisitorSession::where('ip_address', $ip1)->first();
        $this->assertNotNull($session1);
        $this->assertFalse((bool) $session1->is_repeat_visitor);
        $this->assertEquals(1, $session1->visit_number);
        $customerCode1 = $session1->customer_code;
        $this->assertStringStartsWith('CUST-', $customerCode1);

        // 2. Second visit from same IP1 (simulating return visit after session expiry)
        VisitorSession::query()->update(['last_activity_at' => Carbon::now()->subHours(2)]);
        $this->flushSession();

        $res2 = $this->withServerVariables(['REMOTE_ADDR' => $ip1])
            ->get('/smart-living');
        $res2->assertStatus(200);

        $session2 = VisitorSession::where('ip_address', $ip1)->latest('started_at')->first();
        $this->assertNotNull($session2);
        $this->assertTrue((bool) $session2->is_repeat_visitor);
        $this->assertEquals($customerCode1, $session2->customer_code);
        $this->assertEquals(2, $session2->visit_number);

        // 3. Third visit from DIFFERENT IP, but with matching email in session
        $ip2 = '49.36.120.' . rand(1, 250);
        $customerEmail = 'user_' . uniqid() . '@example.com';

        // Attach email to first customer
        TrackedVisitor::where('customer_code', $customerCode1)->update(['email' => $customerEmail]);

        VisitorSession::query()->update(['last_activity_at' => Carbon::now()->subHours(2)]);
        $this->flushSession();

        $res3 = $this->withServerVariables(['REMOTE_ADDR' => $ip2])
            ->withSession(['customer_email' => $customerEmail])
            ->get('/');
        $res3->assertStatus(200);

        $session3 = VisitorSession::where('ip_address', $ip2)->first();
        $this->assertNotNull($session3);
        $this->assertTrue((bool) $session3->is_repeat_visitor);
        $this->assertEquals($customerCode1, $session3->customer_code);

        // 4. Fourth visit from brand new IP and NO matching email -> New Customer
        $ip3 = '203.0.113.' . rand(1, 250);
        VisitorSession::query()->update(['last_activity_at' => Carbon::now()->subHours(2)]);
        $this->flushSession();

        $res4 = $this->withServerVariables(['REMOTE_ADDR' => $ip3])
            ->get('/');
        $res4->assertStatus(200);

        $session4 = VisitorSession::where('ip_address', $ip3)->first();
        $this->assertNotNull($session4);
        $this->assertFalse((bool) $session4->is_repeat_visitor);
        $this->assertNotEquals($customerCode1, $session4->customer_code);
    }

    public function test_google_cpc_and_meta_ads_detection(): void
    {
        // 1. Google CPC with gclid
        $this->get('/?gclid=CjwKCAjwTESTGOOGLE_12345');
        $googleCpcSession = VisitorSession::where('landing_page', 'like', '%gclid%')->first();
        $this->assertNotNull($googleCpcSession);
        $this->assertEquals('Google CPC', $googleCpcSession->platform);

        // Expire previous session
        VisitorSession::query()->update(['last_activity_at' => Carbon::now()->subHours(2)]);
        $this->flushSession();

        // 2. Meta Ads with utm_source=meta and utm_medium=cpc
        $this->get('/?utm_source=meta&utm_medium=cpc&utm_campaign=winter_promo');
        $metaSession = VisitorSession::where('utm_campaign', 'winter_promo')->first();
        $this->assertNotNull($metaSession);
        $this->assertEquals('Meta Ads', $metaSession->platform);

        // Expire previous session
        VisitorSession::query()->update(['last_activity_at' => Carbon::now()->subHours(2)]);
        $this->flushSession();

        // 3. Google Organic search
        $this->withHeaders(['referer' => 'https://www.google.co.in/'])
            ->get('/');
        $googleOrgSession = VisitorSession::where('referrer_domain', 'www.google.co.in')->latest('id')->first();
        $this->assertNotNull($googleOrgSession);
        $this->assertEquals('Google Organic', $googleOrgSession->platform);
    }
}
