<?php

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminTotpCredential;
use App\Models\AdminUser;
use App\Support\Auth\AdminSessionManager;
use App\Support\Security\PasswordHasher;
use App\Support\Security\SecretBox;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminWebPanelTest extends TestCase
{
    use DatabaseTransactions;

    private PasswordHasher $hasher;
    private AdminUser $admin;
    private string $plainPassword = 'WebAdminPassword123!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        $this->hasher = new PasswordHasher();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'webadmin_' . uniqid() . '@dhansree.test',
            'name' => 'Web Test Admin',
            'password_hash' => $this->hasher->hash($this->plainPassword),
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');

        $rootResponse = $this->get('/admin');
        $rootResponse->assertRedirect('/admin/login');
    }

    public function test_login_page_renders_with_brand_and_form(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Dhansree');
        $response->assertSee('Administrator Sign In');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
    }

    public function test_web_login_flow_authenticates_and_redirects_to_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);

        $response->assertRedirect('/admin/dashboard');
        $cookieName = AdminSessionManager::getCookieName();
        $response->assertCookie($cookieName);

        // Extract session token from cookie
        $token = $response->getCookie($cookieName, false)->getValue();
        $this->assertNotEmpty($token);

        // Access dashboard using the unencrypted cookie
        $dashResponse = $this->withUnencryptedCookie($cookieName, $token)
            ->get('/admin/dashboard');

        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Total Revenue');
        $dashResponse->assertSee('Recent Customer Orders');
        $dashResponse->assertSee($this->admin->name);
    }

    public function test_web_two_factor_challenge_redirects_and_verifies(): void
    {
        // Enrol 2FA on this admin
        $box = new SecretBox();
        $secret = Totp::generateSecret();
        AdminTotpCredential::create([
            'admin_user_id' => $this->admin->id,
            'secret' => $box->seal($secret, "admin-totp:{$this->admin->id}"),
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 1: Login should redirect to /admin/two-factor with challenge cookie
        $loginRes = $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);

        $loginRes->assertRedirect('/admin/two-factor');
        $challengeCookieName = AdminSessionManager::getChallengeCookieName();
        $loginRes->assertCookie($challengeCookieName);

        $challengeToken = $loginRes->getCookie($challengeCookieName, false)->getValue();

        // 2FA page renders
        $pageRes = $this->withUnencryptedCookie($challengeCookieName, $challengeToken)
            ->get('/admin/two-factor');
        $pageRes->assertStatus(200);
        $pageRes->assertSee('Two-Step Verification');

        // Step 2: Submit valid TOTP code
        $code = Totp::code($secret, Totp::timeStep(time()));
        $verifyRes = $this->withUnencryptedCookie($challengeCookieName, $challengeToken)
            ->post('/admin/two-factor', [
                'code' => $code,
            ]);

        $verifyRes->assertRedirect('/admin/dashboard');
        $sessionCookieName = AdminSessionManager::getCookieName();
        $verifyRes->assertCookie($sessionCookieName);
    }

    public function test_web_logout_clears_session(): void
    {
        $sessionData = AdminSessionManager::createSession($this->admin);
        $cookieName = AdminSessionManager::getCookieName();

        $response = $this->withUnencryptedCookie($cookieName, $sessionData['token'])
            ->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }
}
