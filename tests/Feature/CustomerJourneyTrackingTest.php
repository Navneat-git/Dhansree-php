<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\VisitorPageView;
use App\Models\VisitorSession;
use App\Support\Auth\AdminSessionManager;
use Tests\TestCase;

class CustomerJourneyTrackingTest extends TestCase
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
}
