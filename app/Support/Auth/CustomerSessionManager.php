<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\CustomerSessionRevokeReason;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class CustomerSessionManager
{
    public const TOKEN_LENGTH = 32; // 32 bytes -> 43 chars base64url
    public const COOKIE_NAME_DEV = 'dhansree_customer';
    public const COOKIE_NAME_PROD = '__Host-dhansree_customer';
    public const SESSION_TTL_SECONDS = 30 * 86400; // 30 days

    public static function getCookieName(): string
    {
        $appEnv = config('app.env', 'local');
        $isSecure = request()?->isSecure() ?? false;
        return (($appEnv === 'production' || $appEnv === 'staging') && $isSecure)
            ? self::COOKIE_NAME_PROD
            : self::COOKIE_NAME_DEV;
    }

    public static function extractTokenFromRequest(Request $request): ?string
    {
        $sessionToken = $request->hasSession() ? $request->session()->get('customer_session_token') : null;
        return $sessionToken
            ?? $request->cookie(self::getCookieName())
            ?? $request->cookie(self::COOKIE_NAME_DEV)
            ?? $request->cookie(self::COOKIE_NAME_PROD)
            ?? $request->bearerToken();
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
     * Issue a new customer session.
     *
     * @return array{token: string, session: CustomerSession, cookie: Cookie}
     */
    public static function createSession(
        Customer $customer,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $token = self::generateToken();
        $tokenHash = self::hashToken($token);
        $now = now();
        $expiresAt = $now->copy()->addSeconds(self::SESSION_TTL_SECONDS);

        $session = CustomerSession::create([
            'customer_id' => $customer->id,
            'token_hash' => $tokenHash,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
            'created_at' => $now,
            'last_seen_at' => $now,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'revoke_reason' => null,
        ]);

        $cookie = self::makeSessionCookie($token, self::SESSION_TTL_SECONDS);

        return [
            'token' => $token,
            'session' => $session,
            'cookie' => $cookie,
        ];
    }

    /**
     * Authenticate token and return active Customer.
     */
    public static function authenticate(string $token): ?Customer
    {
        if (!self::isValidTokenFormat($token)) {
            return null;
        }

        $tokenHash = self::hashToken($token);

        /** @var CustomerSession|null $session */
        $session = CustomerSession::where('token_hash', $tokenHash)
            ->with('customer')
            ->first();

        if (!$session) {
            return null;
        }

        // Check if session is revoked
        if ($session->revoked_at !== null) {
            return null;
        }

        // Check if session expired
        if ($session->expires_at->isPast()) {
            $session->update([
                'revoked_at' => now(),
                'revoke_reason' => CustomerSessionRevokeReason::EXPIRED,
            ]);
            return null;
        }

        // Verify customer account is active
        $customer = $session->customer;
        if (!$customer || $customer->status !== CustomerStatus::ACTIVE) {
            $session->update([
                'revoked_at' => now(),
                'revoke_reason' => CustomerSessionRevokeReason::ACCOUNT_DISABLED,
            ]);
            return null;
        }

        // Touch last_seen_at if more than 5 minutes since last touch
        if ($session->last_seen_at->diffInMinutes(now()) >= 5) {
            $session->update(['last_seen_at' => now()]);
        }

        return $customer;
    }

    /**
     * Revoke customer session.
     */
    public static function revokeSession(string $token, CustomerSessionRevokeReason $reason = CustomerSessionRevokeReason::LOGOUT): bool
    {
        if (!self::isValidTokenFormat($token)) {
            return false;
        }

        $tokenHash = self::hashToken($token);
        $session = CustomerSession::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->first();

        if (!$session) {
            return false;
        }

        $session->update([
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Create session cookie.
     */
    public static function makeSessionCookie(string $token, int $ttlSeconds = self::SESSION_TTL_SECONDS): Cookie
    {
        $appEnv = config('app.env', 'local');
        $isSecure = ($appEnv === 'production' || $appEnv === 'staging') && request()->isSecure();

        return new Cookie(
            name: self::getCookieName(),
            value: $token,
            expire: time() + $ttlSeconds,
            path: '/',
            domain: null, // Host-only cookie
            secure: $isSecure,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX
        );
    }

    /**
     * Create expired cookie to clear customer session from browser.
     */
    public static function makeForgetCookie(): Cookie
    {
        $appEnv = config('app.env', 'local');
        $isSecure = ($appEnv === 'production' || $appEnv === 'staging') && request()->isSecure();

        return new Cookie(
            name: self::getCookieName(),
            value: '',
            expire: time() - 86400,
            path: '/',
            domain: null,
            secure: $isSecure,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX
        );
    }
}
