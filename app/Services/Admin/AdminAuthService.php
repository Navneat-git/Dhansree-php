<?php

namespace App\Services\Admin;

use App\Enums\AdminSessionRevokeReason;
use App\Enums\AdminUserStatus;
use App\Enums\AuditActorType;
use App\Exceptions\ApiException;
use App\Models\AdminUser;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\AdminSessionManager;
use App\Support\Security\PasswordHasher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAuthService
{
    public const MAX_FAILED_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;
    public const INVALID_CREDENTIALS_MESSAGE = 'Invalid email or password.';

    public function __construct(
        private readonly PasswordHasher $hasher = new PasswordHasher(),
        private readonly AdminTwoFactorService $twoFactor = new AdminTwoFactorService(),
        private readonly AuditLogger $audit = new AuditLogger()
    ) {}

    public function login(
        string $email,
        string $password,
        Request $request,
        ?string $previousToken = null
    ): array {
        $email = trim(strtolower($email));
        $admin = AdminUser::where('email', $email)->first();

        // Always pay the hashing cost so response timing does not reveal if account exists
        $passwordValid = $admin
            ? $this->hasher->verify($admin->password_hash, $password)
            : $this->hasher->verifyAgainstDummy($password);

        $now = Carbon::now('UTC');

        // Check if locked
        if ($admin && $admin->locked_until && $admin->locked_until->greaterThan($now)) {
            $this->audit->log(
                action: 'admin.login_failed',
                actorType: AuditActorType::ANONYMOUS,
                metadata: ['reason' => 'ACCOUNT_LOCKED'],
                request: $request
            );
            throw new ApiException(
                code: 'INVALID_CREDENTIALS',
                message: self::INVALID_CREDENTIALS_MESSAGE,
                status: 401
            );
        }

        if (!$admin || !$passwordValid || $admin->status !== AdminUserStatus::ACTIVE) {
            $this->recordFailedAttempt($admin, $now, $request);
            throw new ApiException(
                code: 'INVALID_CREDENTIALS',
                message: self::INVALID_CREDENTIALS_MESSAGE,
                status: 401
            );
        }

        // Two-factor check
        if ($this->twoFactor->isEnrolled($admin->id)) {
            $challenge = $this->twoFactor->createChallenge($admin->id, $request);
            $this->audit->log(
                action: 'admin.2fa.challenged',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                request: $request
            );

            return [
                'status' => 'CODE_REQUIRED',
                'challenge_token' => $challenge['token'],
                'expires_at' => $challenge['expiresAt'],
            ];
        }

        // Issue session
        return $this->issueSession($admin, $now, $request, $previousToken, $password);
    }

    public function completeLogin(
        string $challengeToken,
        string $code,
        Request $request,
        ?string $previousToken = null
    ): array {
        $result = $this->twoFactor->consumeChallenge($challengeToken, $code, $request);
        $admin = AdminUser::findOrFail($result['admin_user_id']);
        $now = Carbon::now('UTC');

        $sessionData = $this->issueSession($admin, $now, $request, $previousToken);

        $this->audit->log(
            action: 'admin.2fa.succeeded',
            actorType: AuditActorType::ADMIN,
            actorId: $admin->id,
            metadata: ['method' => $result['used_recovery_code'] ? 'recovery_code' : 'authenticator'],
            request: $request
        );

        return $sessionData;
    }

    public function logout(?string $token, Request $request): void
    {
        if ($token && AdminSessionManager::isValidTokenFormat($token)) {
            $admin = AdminSessionManager::authenticate($token);
            AdminSessionManager::revokeSession($token, AdminSessionRevokeReason::LOGOUT);

            if ($admin) {
                $this->audit->log(
                    action: 'admin.logout',
                    actorType: AuditActorType::ADMIN,
                    actorId: $admin->id,
                    request: $request
                );
            }
        }
    }

    public function changePassword(
        AdminUser $admin,
        string $currentPassword,
        string $newPassword,
        Request $request
    ): array {
        if (!$this->hasher->verify($admin->password_hash, $currentPassword)) {
            $this->audit->log(
                action: 'admin.password_change_failed',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                entityType: 'admin_user',
                entityId: $admin->id,
                request: $request
            );

            throw new ApiException(
                code: 'INVALID_CREDENTIALS',
                message: 'Your current password is incorrect.',
                status: 422
            );
        }

        if (strlen($newPassword) < 10) {
            throw new ApiException(
                code: 'VALIDATION_ERROR',
                message: 'Password must be at least 10 characters long.',
                status: 422
            );
        }

        $newHash = $this->hasher->hash($newPassword);
        $now = Carbon::now('UTC');

        return DB::transaction(function () use ($admin, $newHash, $now, $request) {
            AdminSessionManager::revokeAllSessionsForUser($admin->id, AdminSessionRevokeReason::PASSWORD_CHANGED);

            $admin->update([
                'password_hash' => $newHash,
                'password_changed_at' => $now,
                'password_change_required' => false,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            $sessionData = AdminSessionManager::createSession(
                $admin,
                $request->ip(),
                $request->userAgent()
            );

            $this->audit->log(
                action: 'admin.password_changed',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                entityType: 'admin_user',
                entityId: $admin->id,
                request: $request
            );

            return [
                'token' => $sessionData['token'],
                'cookie' => $sessionData['cookie'],
                'expires_at' => $sessionData['session']->expires_at,
                'admin' => $admin->fresh(),
            ];
        });
    }

    private function issueSession(
        AdminUser $admin,
        Carbon $now,
        Request $request,
        ?string $previousToken = null,
        ?string $plainPassword = null
    ): array {
        return DB::transaction(function () use ($admin, $now, $request, $previousToken, $plainPassword) {
            if ($previousToken && AdminSessionManager::isValidTokenFormat($previousToken)) {
                AdminSessionManager::revokeSession($previousToken, AdminSessionRevokeReason::REPLACED);
            }

            $updateData = [
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => $now,
            ];

            // Rehash if needed
            if ($plainPassword !== null && $this->hasher->needsRehash($admin->password_hash)) {
                $updateData['password_hash'] = $this->hasher->hash($plainPassword);
            }

            $admin->update($updateData);

            $sessionData = AdminSessionManager::createSession(
                $admin,
                $request->ip(),
                $request->userAgent()
            );

            $this->audit->log(
                action: 'admin.login_succeeded',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                request: $request
            );

            return [
                'status' => 'SIGNED_IN',
                'token' => $sessionData['token'],
                'cookie' => $sessionData['cookie'],
                'expires_at' => $sessionData['session']->expires_at,
                'admin' => $admin->fresh(),
            ];
        });
    }

    private function recordFailedAttempt(?AdminUser $admin, Carbon $now, Request $request): void
    {
        if (!$admin) {
            $this->audit->log(
                action: 'admin.login_failed',
                actorType: AuditActorType::ANONYMOUS,
                metadata: ['reason' => 'UNKNOWN_ACCOUNT'],
                request: $request
            );
            return;
        }

        $attempts = $admin->failed_login_attempts + 1;

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $lockedUntil = $now->copy()->addMinutes(self::LOCKOUT_MINUTES);
            $admin->update([
                'failed_login_attempts' => 0,
                'locked_until' => $lockedUntil,
            ]);

            $this->audit->log(
                action: 'admin.account_locked',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                metadata: ['lockedUntil' => $lockedUntil->toISOString()],
                request: $request
            );
        } else {
            $admin->update(['failed_login_attempts' => $attempts]);
        }

        $this->audit->log(
            action: 'admin.login_failed',
            actorType: AuditActorType::ADMIN,
            actorId: $admin->id,
            metadata: ['reason' => 'INVALID_PASSWORD'],
            request: $request
        );
    }
}
