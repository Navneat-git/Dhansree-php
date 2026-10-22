<?php

namespace App\Services\Admin;

use App\Enums\AuditActorType;
use App\Exceptions\ApiException;
use App\Models\AdminLoginChallenge;
use App\Models\AdminRecoveryCode;
use App\Models\AdminTotpCredential;
use App\Models\AdminUser;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\AdminSessionManager;
use App\Support\Security\PasswordHasher;
use App\Support\Security\RecoveryCodes;
use App\Support\Security\SecretBox;
use App\Support\Security\Totp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTwoFactorService
{
    public const CHALLENGE_TTL_MINUTES = 5;
    public const MAX_CHALLENGE_ATTEMPTS = 5;
    public const INVALID_CODE_MESSAGE = 'That code is not right. Please try again.';

    public function __construct(
        private readonly SecretBox $secrets = new SecretBox(),
        private readonly PasswordHasher $hasher = new PasswordHasher(),
        private readonly AuditLogger $audit = new AuditLogger()
    ) {}

    public function isEnrolled(int $adminUserId): bool
    {
        return AdminTotpCredential::where('admin_user_id', $adminUserId)
            ->whereNotNull('confirmed_at')
            ->exists();
    }

    public function isRequired(AdminUser $admin): bool
    {
        $policy = env('ADMIN_2FA_POLICY', 'optional');
        if ($policy === 'all') {
            return true;
        }
        if ($policy === 'super_admin') {
            return $admin->hasSuperAdmin();
        }
        return false;
    }

    public function status(AdminUser $admin): array
    {
        $credential = AdminTotpCredential::where('admin_user_id', $admin->id)->first();
        $isConfirmed = $credential && $credential->confirmed_at !== null;
        $remaining = 0;

        if ($isConfirmed) {
            $remaining = AdminRecoveryCode::where('credential_id', $credential->id)
                ->whereNull('used_at')
                ->count();
        }

        return [
            'enabled' => $isConfirmed,
            'required' => $this->isRequired($admin),
            'recoveryCodesRemaining' => $remaining,
            'confirmedAt' => $credential?->confirmed_at?->toISOString(),
        ];
    }

    public function beginEnrolment(AdminUser $admin, string $currentPassword, Request $request): array
    {
        if (!$this->secrets->isAvailable()) {
            throw new ApiException(
                code: 'TWO_FACTOR_UNAVAILABLE',
                message: 'Two-factor authentication is not available.',
                status: 503
            );
        }

        if (!$this->hasher->verify($admin->password_hash, $currentPassword)) {
            throw new ApiException(
                code: 'INVALID_CREDENTIALS',
                message: 'Your current password is incorrect.',
                status: 422
            );
        }

        $existing = AdminTotpCredential::where('admin_user_id', $admin->id)->first();
        if ($existing && $existing->confirmed_at !== null) {
            throw new ApiException(
                code: 'ALREADY_ENROLLED',
                message: 'Two-step sign-in is already set up on this account.',
                status: 409
            );
        }

        $secret = Totp::generateSecret();
        $sealed = $this->secrets->seal($secret, "admin-totp:{$admin->id}");

        AdminTotpCredential::updateOrCreate(
            ['admin_user_id' => $admin->id],
            [
                'secret' => $sealed,
                'confirmed_at' => null,
                'last_used_step' => null,
                'last_used_at' => null,
            ]
        );

        $this->audit->log(
            action: 'admin.2fa.enrolment_started',
            actorType: AuditActorType::ADMIN,
            actorId: $admin->id,
            entityType: 'admin_user',
            entityId: $admin->id,
            request: $request
        );

        return [
            'secret' => $secret,
            'uri' => Totp::uri($secret, $admin->email),
        ];
    }

    public function confirmEnrolment(AdminUser $admin, string $code, Request $request): array
    {
        $credential = AdminTotpCredential::where('admin_user_id', $admin->id)->first();
        if (!$credential) {
            throw new ApiException(
                code: 'NOT_ENROLLING',
                message: 'Enrolment has not been started.',
                status: 400
            );
        }

        if ($credential->confirmed_at !== null) {
            throw new ApiException(
                code: 'ALREADY_ENROLLED',
                message: 'Two-step sign-in is already set up on this account.',
                status: 409
            );
        }

        $secret = $this->secrets->open($credential->secret, "admin-totp:{$admin->id}");
        $step = Totp::verify($secret, $code, time(), $credential->last_used_step);

        if ($step === null) {
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        $codes = RecoveryCodes::generate();
        $hashes = array_map(fn ($c) => $this->hasher->hash(RecoveryCodes::normalize($c)), $codes);

        DB::transaction(function () use ($credential, $step, $hashes, $admin, $codes, $request) {
            $now = Carbon::now('UTC');
            $credential->update([
                'confirmed_at' => $now,
                'last_used_step' => $step,
                'last_used_at' => $now,
            ]);

            foreach ($hashes as $hash) {
                AdminRecoveryCode::create([
                    'credential_id' => $credential->id,
                    'code_hash' => $hash,
                    'created_at' => $now,
                ]);
            }

            $this->audit->log(
                action: 'admin.2fa.enabled',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                entityType: 'admin_user',
                entityId: $admin->id,
                metadata: ['recoveryCodes' => count($codes)],
                request: $request
            );
        });

        return $codes;
    }

    public function regenerateRecoveryCodes(AdminUser $admin, string $currentPassword, Request $request): array
    {
        if (!$this->hasher->verify($admin->password_hash, $currentPassword)) {
            throw new ApiException(
                code: 'INVALID_CREDENTIALS',
                message: 'Your current password is incorrect.',
                status: 422
            );
        }

        $credential = AdminTotpCredential::where('admin_user_id', $admin->id)->first();
        if (!$credential || $credential->confirmed_at === null) {
            throw new ApiException(
                code: 'NOT_ENROLLED',
                message: 'Two-step sign-in is not set up on this account.',
                status: 400
            );
        }

        $codes = RecoveryCodes::generate();
        $hashes = array_map(fn ($c) => $this->hasher->hash(RecoveryCodes::normalize($c)), $codes);

        DB::transaction(function () use ($credential, $hashes, $admin, $codes, $request) {
            AdminRecoveryCode::where('credential_id', $credential->id)->delete();

            $now = Carbon::now('UTC');
            foreach ($hashes as $hash) {
                AdminRecoveryCode::create([
                    'credential_id' => $credential->id,
                    'code_hash' => $hash,
                    'created_at' => $now,
                ]);
            }

            $this->audit->log(
                action: 'admin.2fa.recovery_codes_regenerated',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                entityType: 'admin_user',
                entityId: $admin->id,
                metadata: ['recoveryCodes' => count($codes)],
                request: $request
            );
        });

        return $codes;
    }

    public function disable(AdminUser $admin, string $currentPassword, string $code, Request $request): void
    {
        if ($this->isRequired($admin)) {
            throw new ApiException(
                code: 'REQUIRED_BY_POLICY',
                message: 'Your account is required to use two-factor authentication.',
                status: 403
            );
        }

        if (!$this->hasher->verify($admin->password_hash, $currentPassword)) {
            throw new ApiException(
                code: 'INVALID_CREDENTIALS',
                message: 'Your current password is incorrect.',
                status: 422
            );
        }

        $credential = AdminTotpCredential::where('admin_user_id', $admin->id)->first();
        if (!$credential || $credential->confirmed_at === null) {
            throw new ApiException(
                code: 'NOT_ENROLLED',
                message: 'Two-step sign-in is not set up on this account.',
                status: 400
            );
        }

        $secret = $this->secrets->open($credential->secret, "admin-totp:{$admin->id}");
        $step = Totp::verify($secret, $code, time(), $credential->last_used_step);

        if ($step === null) {
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        DB::transaction(function () use ($credential, $admin, $request) {
            AdminRecoveryCode::where('credential_id', $credential->id)->delete();
            $credential->delete();

            $this->audit->log(
                action: 'admin.2fa.disabled',
                actorType: AuditActorType::ADMIN,
                actorId: $admin->id,
                entityType: 'admin_user',
                entityId: $admin->id,
                request: $request
            );
        });
    }

    public function createChallenge(int $adminUserId, Request $request): array
    {
        $token = AdminSessionManager::generateToken();
        $tokenHash = hash('sha256', $token);
        $now = Carbon::now('UTC');
        $expiresAt = $now->copy()->addMinutes(self::CHALLENGE_TTL_MINUTES);

        // Cancel previous pending challenges
        AdminLoginChallenge::where('admin_user_id', $adminUserId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $now]);

        AdminLoginChallenge::create([
            'admin_user_id' => $adminUserId,
            'token_hash' => $tokenHash,
            'attempts' => 0,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'ip_address' => substr($request->ip() ?? '', 0, 45) ?: null,
            'user_agent' => substr($request->userAgent() ?? '', 0, 255) ?: null,
        ]);

        return [
            'token' => $token,
            'expiresAt' => $expiresAt,
        ];
    }

    public function consumeChallenge(string $token, string $code, Request $request): array
    {
        $now = Carbon::now('UTC');
        $tokenHash = hash('sha256', $token);

        $challenge = AdminLoginChallenge::where('token_hash', $tokenHash)->first();
        if (!$challenge || $challenge->consumed_at !== null || $now->greaterThan($challenge->expires_at)) {
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        if ($challenge->attempts >= self::MAX_CHALLENGE_ATTEMPTS) {
            $challenge->update(['consumed_at' => $now]);
            $this->audit->log(
                action: 'admin.2fa.failed',
                actorType: AuditActorType::ADMIN,
                actorId: $challenge->admin_user_id,
                metadata: ['reason' => 'TOO_MANY_ATTEMPTS'],
                request: $request
            );
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        $credential = AdminTotpCredential::where('admin_user_id', $challenge->admin_user_id)->first();
        if (!$credential || $credential->confirmed_at === null) {
            $challenge->update(['consumed_at' => $now]);
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        $usedRecoveryCode = RecoveryCodes::looksLikeRecoveryCode($code);

        if ($usedRecoveryCode) {
            $normalized = RecoveryCodes::normalize($code);
            $recoveryRows = AdminRecoveryCode::where('credential_id', $credential->id)
                ->whereNull('used_at')
                ->get();

            $matchedRow = null;
            foreach ($recoveryRows as $row) {
                if ($this->hasher->verify($row->code_hash, $normalized)) {
                    $matchedRow = $row;
                    break;
                }
            }

            if (!$matchedRow) {
                $challenge->increment('attempts');
                $this->audit->log(
                    action: 'admin.2fa.failed',
                    actorType: AuditActorType::ADMIN,
                    actorId: $challenge->admin_user_id,
                    metadata: ['reason' => 'INVALID_RECOVERY_CODE'],
                    request: $request
                );
                throw new ApiException(
                    code: 'INVALID_CODE',
                    message: self::INVALID_CODE_MESSAGE,
                    status: 401
                );
            }

            $matchedRow->update(['used_at' => $now]);
            $challenge->update(['consumed_at' => $now]);

            $remaining = AdminRecoveryCode::where('credential_id', $credential->id)
                ->whereNull('used_at')
                ->count();

            $this->audit->log(
                action: 'admin.2fa.recovery_code_used',
                actorType: AuditActorType::ADMIN,
                actorId: $challenge->admin_user_id,
                entityType: 'admin_user',
                entityId: $challenge->admin_user_id,
                metadata: ['remaining' => $remaining],
                request: $request
            );

            return [
                'admin_user_id' => $challenge->admin_user_id,
                'used_recovery_code' => true,
            ];
        }

        // TOTP verification
        try {
            $secret = $this->secrets->open($credential->secret, "admin-totp:{$challenge->admin_user_id}");
        } catch (\Throwable $e) {
            $this->audit->log(
                action: 'admin.2fa.failed',
                actorType: AuditActorType::ADMIN,
                actorId: $challenge->admin_user_id,
                metadata: ['reason' => 'SECRET_DECRYPTION_FAILED', 'error' => $e->getMessage()],
                request: $request
            );
            $adminEmail = AdminUser::find($challenge->admin_user_id)?->email ?? 'admin';
            throw new ApiException(
                code: 'SECRET_DECRYPTION_FAILED',
                message: 'Two-factor secret decryption error (' . $e->getMessage() . '). To reset 2FA, run on server: php8.3 artisan admin:2fa:reset ' . $adminEmail,
                status: 401
            );
        }
        $step = Totp::verify($secret, $code, time(), $credential->last_used_step);

        if ($step === null) {
            $challenge->increment('attempts');
            $this->audit->log(
                action: 'admin.2fa.failed',
                actorType: AuditActorType::ADMIN,
                actorId: $challenge->admin_user_id,
                metadata: ['reason' => 'INVALID_CODE'],
                request: $request
            );
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        // Conditional anti-replay update
        $updated = AdminTotpCredential::where('id', $credential->id)
            ->where(function ($q) use ($credential) {
                if ($credential->last_used_step === null) {
                    $q->whereNull('last_used_step');
                } else {
                    $q->where('last_used_step', $credential->last_used_step);
                }
            })
            ->update([
                'last_used_step' => $step,
                'last_used_at' => $now,
            ]);

        if (!$updated) {
            // Racing duplicate request spent this step
            throw new ApiException(
                code: 'INVALID_CODE',
                message: self::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        $challenge->update(['consumed_at' => $now]);

        return [
            'admin_user_id' => $challenge->admin_user_id,
            'used_recovery_code' => false,
        ];
    }
}
