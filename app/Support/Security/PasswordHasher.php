<?php

namespace App\Support\Security;

use Normalizer;

class PasswordHasher
{
    /**
     * OWASP Password Storage Cheat Sheet baseline for Argon2id: 19 MiB memory, 2 iterations,
     * 1 lane (~30 ms per hash). Matches Node.js backend.
     */
    public const MEMORY_COST = 19456;
    public const TIME_COST = 2;
    public const THREADS = 1;

    private const PHC_PATTERN = '/^\$argon2id\$v=19\$m=(\d+),t=(\d+),p=(\d+)\$[A-Za-z0-9+\/]+\$[A-Za-z0-9+\/]+$/';

    private ?string $dummyHash = null;

    /**
     * Hash a password using Argon2id with OWASP baseline parameters.
     */
    public function hash(string $password): string
    {
        return password_hash(self::normalize($password), PASSWORD_ARGON2ID, [
            'memory_cost' => self::MEMORY_COST,
            'time_cost' => self::TIME_COST,
            'threads' => self::THREADS,
        ]);
    }

    /**
     * Constant-time verification against an Argon2id hash. Returns false for invalid hashes.
     */
    public function verify(string $passwordHash, string $password): bool
    {
        if (!preg_match(self::PHC_PATTERN, $passwordHash)) {
            return false;
        }

        return password_verify(self::normalize($password), $passwordHash);
    }

    /**
     * Constant-time verification against a throwaway hash when account does not exist.
     * Always returns false.
     */
    public function verifyAgainstDummy(string $password): false
    {
        try {
            if ($this->dummyHash === null) {
                $this->dummyHash = $this->hash(bin2hex(random_bytes(16)));
            }
            $this->verify($this->dummyHash, $password);
        } catch (\Throwable) {
            $this->dummyHash = null;
        }

        return false;
    }

    /**
     * Returns true when the hash uses weaker or different parameters than current policy.
     */
    public function needsRehash(string $passwordHash): bool
    {
        return password_needs_rehash($passwordHash, PASSWORD_ARGON2ID, [
            'memory_cost' => self::MEMORY_COST,
            'time_cost' => self::TIME_COST,
            'threads' => self::THREADS,
        ]);
    }

    /**
     * Unicode NFKC normalisation (NIST SP 800-63B §5.1.1.2).
     */
    public static function normalize(string $password): string
    {
        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($password, Normalizer::FORM_KC);
            if ($normalized !== false) {
                return $normalized;
            }
        }

        return $password;
    }
}
