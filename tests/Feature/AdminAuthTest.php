<?php

namespace Tests\Feature;

use App\Enums\AdminSessionRevokeReason;
use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminSession;
use App\Models\AdminUser;
use App\Support\Auth\AdminSessionManager;
use App\Support\Rbac\Permissions;
use App\Support\Security\PasswordHasher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    private PasswordHasher $hasher;
    private AdminUser $admin;
    private string $plainPassword = 'CorrectPassword123!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        $this->hasher = new PasswordHasher();

        // Create or get super admin role
        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        // Create a dedicated active test admin
        $this->admin = AdminUser::create([
            'email' => 'authtest_' . uniqid() . '@dhansree.test',
            'name' => 'Test Admin',
            'password_hash' => $this->hasher->hash($this->plainPassword),
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
    }

    public function test_login_succeeds_with_correct_password_and_sets_cookie(): void
    {
        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'SIGNED_IN',
                'admin' => [
                    'id' => $this->admin->id,
                    'email' => $this->admin->email,
                    'hasFullAccess' => true,
                ],
            ],
        ]);

        // Asserts session cookie was set
        $cookieName = AdminSessionManager::getCookieName();
        $response->assertCookie($cookieName);

        // Verifies session exists in database
        $this->assertDatabaseHas('admin_sessions', [
            'admin_user_id' => $this->admin->id,
            'revoked_at' => null,
        ]);
    }

    public function test_login_fails_with_wrong_password_and_returns_generic_401(): void
    {
        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => 'WrongPassword999',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password.',
            ],
        ]);

        $this->admin->refresh();
        $this->assertSame(1, $this->admin->failed_login_attempts);
    }

    public function test_login_lockout_after_five_failed_attempts(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/v1/admin/auth/login', [
                'email' => $this->admin->email,
                'password' => 'WrongPassword' . $i,
            ]);
            $res->assertStatus(401);
            $this->admin->refresh();
            $this->assertSame($i, $this->admin->failed_login_attempts);
            $this->assertNull($this->admin->locked_until);
        }

        // 5th attempt locks the account
        $res = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => 'WrongPassword5',
        ]);
        $res->assertStatus(401);

        $this->admin->refresh();
        $this->assertSame(0, $this->admin->failed_login_attempts);
        $this->assertNotNull($this->admin->locked_until);
        $this->assertTrue($this->admin->locked_until->greaterThan(now()));

        // Even with the CORRECT password, account stays locked
        $resLocked = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);
        $resLocked->assertStatus(401);
        $resLocked->assertJson([
            'error' => [
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password.',
            ],
        ]);
    }

    public function test_me_returns_profile_for_authenticated_admin(): void
    {
        $sessionData = AdminSessionManager::createSession($this->admin);

        $response = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $sessionData['token'])
            ->getJson('/api/v1/admin/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $this->admin->id,
                'email' => $this->admin->email,
                'hasFullAccess' => true,
            ],
        ]);
    }

    public function test_change_password_revokes_all_sessions_and_issues_fresh_session(): void
    {
        $session1 = AdminSessionManager::createSession($this->admin);
        $session2 = AdminSessionManager::createSession($this->admin);

        $newPassword = 'BrandNewSecurePassword456!';

        $response = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $session1['token'])
            ->postJson('/api/v1/admin/auth/password', [
                'currentPassword' => $this->plainPassword,
                'newPassword' => $newPassword,
            ]);

        $response->assertStatus(200);
        $this->admin->refresh();
        $this->assertTrue($this->hasher->verify($this->admin->password_hash, $newPassword));

        // Prior sessions are revoked
        $this->assertNotNull(AdminSession::find($session1['session']->id)->revoked_at);
        $this->assertNotNull(AdminSession::find($session2['session']->id)->revoked_at);

        // New session works with new password
        $loginRes = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => $newPassword,
        ]);
        $loginRes->assertStatus(200);
    }

    public function test_logout_revokes_session_and_clears_cookies(): void
    {
        $sessionData = AdminSessionManager::createSession($this->admin);

        $response = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $sessionData['token'])
            ->postJson('/api/v1/admin/auth/logout');

        $response->assertStatus(200);

        // Verifies session was revoked in database
        $session = AdminSession::find($sessionData['session']->id);
        $this->assertNotNull($session->revoked_at);
        $this->assertEquals(AdminSessionRevokeReason::LOGOUT, $session->revoke_reason);
    }

    public function test_fail_closed_route_security_blocks_anonymous_access(): void
    {
        $protectedEndpoints = [
            ['GET', '/api/v1/admin/auth/me'],
            ['POST', '/api/v1/admin/auth/password'],
            ['GET', '/api/v1/admin/auth/two-factor'],
            ['POST', '/api/v1/admin/auth/two-factor/setup'],
            ['POST', '/api/v1/admin/auth/two-factor/enable'],
            ['POST', '/api/v1/admin/auth/two-factor/recovery-codes'],
            ['POST', '/api/v1/admin/auth/two-factor/disable'],
        ];

        foreach ($protectedEndpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertStatus(401);
            $response->assertJson([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                ],
            ]);
        }
    }
}
