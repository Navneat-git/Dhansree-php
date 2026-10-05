<?php

declare(strict_types=1);

namespace App\Support\Crypto;

/**
 * Argon2id password hashing, parameter-compatible with the Node implementation
 * (apps/api/src/auth/password-hasher.ts).
 *
 * The parameters are not a preference: they are what the existing `admin_users.password_hash`
 * column already contains. PHP's `password_verify()` reads those PHC strings directly, which is
 * why migrating does NOT require resetting a single administrator's password. Changing these
 * numbers is therefore a migration, not a tuning decision.
 *
 * OWASP Password Storage baseline: 19 MiB, 2 iterations, 1 lane (~30 ms/hash). Chosen over heavier
 * settings so concurrent sign-ins cannot exhaust a small VPS's memory; brute force is limited by
 * rate limiting and account lockout instead.
 */
final class PasswordHasher
{
    public const MEMORY_COST = 19456;
    public const TIME_COST = 2;
    public const THREADS = 1;

    /**
     * Mirrors the Node PHC_PATTERN. A malformed hash must verify false rather than throw, so a
     * corrupt row cannot turn a sign-in into a 500.
     */
    private const PHC_PATTERN = '#^\$argon2id\$v=19\$m=(\d+),t=(\d+),p=(\d+)\$[A-Za-z0-9+/]+\$[A-Za-z0-9+/]+$#';

    public function hash(string $password): string
    {
        return password_hash($this->normalize($password), PASSWORD_ARGON2ID, [
            'memory_cost' => self::MEMORY_COST,
            'time_cost' => self::TIME_COST,
            'threads' => self::THREADS,
        ]);
    }

    /** Constant-time verification. Returns false (never throws) for malformed hashes. */
    public function verify(string $passwordHash, string $password): bool
    {
        if (preg_match(self::PHC_PATTERN, $passwordHash) !== 1) {
            return false;
        }

        return password_verify($this->normalize($password), $passwordHash);
    }

    /** True when a stored hash was made with weaker parameters and should be upgraded on sign-in. */
    public function needsRehash(string $passwordHash): bool
    {
        return password_needs_rehash($passwordHash, PASSWORD_ARGON2ID, [
            'memory_cost' => self::MEMORY_COST,
            'time_cost' => self::TIME_COST,
            'threads' => self::THREADS,
        ]);
    }

    /**
     * Unicode normalisation must match the Node side exactly, or a password typed with a composed
     * vs decomposed accent would hash differently between the two implementations.
     */
    private function normalize(string $password): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($password, \Normalizer::FORM_KC);
            if (is_string($normalized)) {
                return $normalized;
            }
        }

        return $password;
    }
}
