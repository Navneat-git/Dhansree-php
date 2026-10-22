<?php

namespace App\Support\Auth;

use App\Enums\AdminSessionRevokeReason;
use App\Enums\AdminUserStatus;
use App\Models\AdminSession;
use App\Models\AdminUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AdminSessionManager
{
    public const TOKEN_LENGTH = 32; // 32 bytes -> 43 chars base64url
    public const COOKIE_NAME_DEV = 'dhansree_admin_session';
    public const COOKIE_NAME_PROD = '__Host-dhansree_admin_session';
    public const CHALLENGE_COOKIE_NAME_DEV = 'dhansree_admin_2fa';
    public const CHALLENGE_COOKIE_NAME_PROD = '__Host-dhansree_admin_2fa';

    public static function getCookieName(): string
    {
        $appEnv = config('app.env', 'local');
        return ($appEnv === 'production' || $appEnv === 'staging')
            ? self::COOKIE_NAME_PROD
            : self::COOKIE_NAME_DEV;
    }

    public static function getChallengeCookieName(): string
    {
        $appEnv = config('app.env', 'local');
        return ($appEnv === 'production' || $appEnv === 'staging')
            ? self::CHALLENGE_COOKIE_NAME_PROD
            : self::CHALLENGE_COOKIE_NAME_DEV;
    }

    public static function extractTokenFromRequest(Request $request): ?string
    {
        return $request->cookie(self::getCookieName())
            ?? $request->cookie(self::COOKIE_NAME_DEV)
            ?? $request->cookie('dhansree_admin')
            ?? $request->cookie('__Host-dhansree_admin')
            ?? $request->bearerToken();
    }

    public static function extractChallengeTokenFromRequest(Request $request): ?string
    {
        return $request->cookie(self::getChallengeCookieName())
            ?? $request->cookie(self::CHALLENGE_COOKIE_NAME_DEV)
            ?? $request->cookie('dhansree_admin_2fa_challenge')
            ?? $request->cookie('__Host-dhansree_admin_2fa_challenge')
            ?? $request->header('X-Challenge-Token');
    }

    public static function generateToken(): string
    {
        $bytes = random_bytes(self::TOKEN_LENGTH);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function isValidTokenFormat(string $token): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{43}$/', $token);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Issue a new administrator session.
     */
    public static function createSession(
        AdminUser $admin,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $token = self::generateToken();
        $tokenHash = self::hashToken($token);

        $idleMinutes = (int) env('ADMIN_SESSION_IDLE_TIMEOUT_MINUTES', 120);
        $absoluteHours = (int) env('ADMIN_SESSION_ABSOLUTE_TIMEOUT_HOURS', 12);

        $now = Carbon::now('UTC');
        $expiresAt = $now->copy()->addHours($absoluteHours);

        $session = AdminSession::create([
            'admin_user_id' => $admin->id,
            'token_hash' => $tokenHash,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
            'last_seen_at' => $now,
            'expires_at' => $expiresAt,
            'created_at' => $now,
        ]);

        $cookieName = self::getCookieName();
        $isSecure = ($cookieName === self::COOKIE_NAME_PROD);

        $cookie = new Cookie(
            name: $cookieName,
            value: $token,
            expire: $expiresAt->timestamp,
            path: '/',
            domain: null, // mandatory for __Host-
            secure: $isSecure,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX
        );

        return [
            'token' => $token,
            'session' => $session,
            'cookie' => $cookie,
        ];
    }

    /**
     * Authenticate an incoming token.
     */
    public static function authenticate(?string $token): ?AdminUser
    {
        if (!$token || !self::isValidTokenFormat($token)) {
            return null;
        }

        $tokenHash = self::hashToken($token);
        $session = AdminSession::where('token_hash', $tokenHash)->first();

        if (!$session || $session->revoked_at !== null) {
            return null;
        }

        $now = Carbon::now('UTC');

        // Check absolute expiry
        if ($session->expires_at && $now->greaterThan($session->expires_at)) {
            $session->update([
                'revoked_at' => $now,
                'revoke_reason' => AdminSessionRevokeReason::EXPIRED,
            ]);
            return null;
        }

        // Check idle timeout
        $idleMinutes = (int) env('ADMIN_SESSION_IDLE_TIMEOUT_MINUTES', 120);
        if ($session->last_seen_at && $now->diffInMinutes($session->last_seen_at) > $idleMinutes) {
            $session->update([
                'revoked_at' => $now,
                'revoke_reason' => AdminSessionRevokeReason::IDLE_TIMEOUT,
            ]);
            return null;
        }

        // Check admin user status
        $admin = AdminUser::find($session->admin_user_id);
        if (!$admin || $admin->status !== AdminUserStatus::ACTIVE) {
            $session->update([
                'revoked_at' => $now,
                'revoke_reason' => AdminSessionRevokeReason::ACCOUNT_DISABLED,
            ]);
            return null;
        }

        // Touch last_seen_at if older than 5 minutes (avoids write on every request)
        if ($now->diffInMinutes($session->last_seen_at) >= 5) {
            $session->update(['last_seen_at' => $now]);
        }

        return $admin;
    }

    /**
     * Revoke a single session.
     */
    public static function revokeSession(string $token, AdminSessionRevokeReason $reason): void
    {
        if (!self::isValidTokenFormat($token)) {
            return;
        }

        $tokenHash = self::hashToken($token);
        AdminSession::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => Carbon::now('UTC'),
                'revoke_reason' => $reason,
            ]);
    }

    /**
     * Revoke all active sessions for a user.
     */
    public static function revokeAllSessionsForUser(int $adminUserId, AdminSessionRevokeReason $reason): void
    {
        AdminSession::where('admin_user_id', $adminUserId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => Carbon::now('UTC'),
                'revoke_reason' => $reason,
            ]);
    }
}
