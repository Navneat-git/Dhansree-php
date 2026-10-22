<?php

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRecoveryCode;
use App\Models\AdminRole;
use App\Models\AdminSession;
use App\Models\AdminTotpCredential;
use App\Models\AdminUser;
use App\Support\Auth\AdminSessionManager;
use App\Support\Security\PasswordHasher;
use App\Support\Security\RecoveryCodes;
use App\Support\Security\SecretBox;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use DatabaseTransactions;

    private PasswordHasher $hasher;
    private AdminUser $admin;
    private string $plainPassword = 'StrongPassword987!';

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
            'email' => 'twofactor_' . uniqid() . '@dhansree.test',
            'name' => '2FA Test Admin',
            'password_hash' => $this->hasher->hash($this->plainPassword),
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
    }

    public function test_full_two_factor_enrolment_and_login_lifecycle(): void
    {
        $sessionData = AdminSessionManager::createSession($this->admin);
        $cookieName = AdminSessionManager::getCookieName();

        // 1. Begin Enrolment
        $resStart = $this->withUnencryptedCookie($cookieName, $sessionData['token'])
            ->postJson('/api/v1/admin/auth/two-factor/setup', [
                'currentPassword' => $this->plainPassword,
            ]);

        $resStart->assertStatus(200);
        $secret = $resStart->json('data.secret');
        $this->assertNotEmpty($secret);
        $this->assertStringStartsWith('otpauth://totp/', $resStart->json('data.uri'));

        // 2. Confirm Enrolment with valid code
        $code = Totp::code($secret, Totp::timeStep(time()));
        $resConfirm = $this->withUnencryptedCookie($cookieName, $sessionData['token'])
            ->postJson('/api/v1/admin/auth/two-factor/enable', [
                'code' => $code,
            ]);

        $resConfirm->assertStatus(200);
        $recoveryCodes = $resConfirm->json('data.codes');
        $this->assertCount(10, $recoveryCodes);

        $credential = AdminTotpCredential::where('admin_user_id', $this->admin->id)->first();
        $this->assertNotNull($credential->confirmed_at);

        // 3. Login Step 1: Password earns challenge, not session
        $resLogin1 = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);

        $resLogin1->assertStatus(200);
        $resLogin1->assertJson([
            'data' => [
                'status' => 'CODE_REQUIRED',
            ],
        ]);
        $challengeCookieName = AdminSessionManager::getChallengeCookieName();
        $resLogin1->assertCookie($challengeCookieName);

        // Extract challenge cookie from response without auto-decrypt
        $challengeToken = $resLogin1->getCookie($challengeCookieName, false)->getValue();
        $this->assertNotEmpty($challengeToken);

        // 4. Login Step 2: Use a recovery code to complete sign in
        $testRecoveryCode = $recoveryCodes[0];
        $resLogin2 = $this->withUnencryptedCookie($challengeCookieName, $challengeToken)
            ->postJson('/api/v1/admin/auth/login/code', [
                'code' => $testRecoveryCode,
            ]);

        $resLogin2->assertStatus(200);
        $resLogin2->assertJson([
            'data' => [
                'status' => 'SIGNED_IN',
                'admin' => [
                    'id' => $this->admin->id,
                    'email' => $this->admin->email,
                    'twoFactorEnabled' => true,
                ],
            ],
        ]);

        // Verifies the used recovery code is now burned
        $normalizedCode = RecoveryCodes::normalize($testRecoveryCode);
        $burnedRow = AdminRecoveryCode::where('credential_id', $credential->id)
            ->whereNotNull('used_at')
            ->first();
        $this->assertNotNull($burnedRow);
        $this->assertTrue($this->hasher->verify($burnedRow->code_hash, $normalizedCode));

        // Reusing the same recovery code is refused
        $resLoginReused = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);
        $newToken = $resLoginReused->getCookie($challengeCookieName, false)->getValue();

        $resReusedCode = $this->withUnencryptedCookie($challengeCookieName, $newToken)
            ->postJson('/api/v1/admin/auth/login/code', [
                'code' => $testRecoveryCode,
            ]);
        $resReusedCode->assertStatus(401);
    }

    public function test_artisan_2fa_reset_removes_authenticator_and_revokes_sessions(): void
    {
        $sessionData = AdminSessionManager::createSession($this->admin);

        // Set up confirmed 2FA
        $box = new SecretBox();
        $secret = Totp::generateSecret();
        $credential = AdminTotpCredential::create([
            'admin_user_id' => $this->admin->id,
            'secret' => $box->seal($secret, "admin-totp:{$this->admin->id}"),
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AdminRecoveryCode::create([
            'credential_id' => $credential->id,
            'code_hash' => $this->hasher->hash('ABCDE12345'),
            'created_at' => now(),
        ]);

        $this->artisan('admin:2fa:reset', ['email' => $this->admin->email])
            ->expectsOutputToContain("Two-step sign-in reset for {$this->admin->name}")
            ->assertExitCode(0);

        // Verify credential and recovery codes deleted
        $this->assertNull(AdminTotpCredential::where('admin_user_id', $this->admin->id)->first());
        $this->assertSame(0, AdminRecoveryCode::where('credential_id', $credential->id)->count());

        // Verify session revoked
        $this->assertNotNull(AdminSession::find($sessionData['session']->id)->revoked_at);

        // Can now sign in without 2FA
        $res = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $this->admin->email,
            'password' => $this->plainPassword,
        ]);
        $res->assertStatus(200);
        $res->assertJson([
            'data' => [
                'status' => 'SIGNED_IN',
                'admin' => ['twoFactorEnabled' => false],
            ],
        ]);
    }
}
