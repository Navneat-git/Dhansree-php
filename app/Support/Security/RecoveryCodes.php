<?php

namespace App\Support\Security;

class RecoveryCodes
{
    public const RECOVERY_CODE_COUNT = 10;
    public const GROUP_LENGTH = 5;
    public const GROUPS = 2;

    /**
     * Crockford-style unambiguous alphabet (no 0, 1, I, L, O, U).
     */
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generate an array of 10 one-time recovery codes formatted as XXXXX-XXXXX.
     *
     * @return string[]
     */
    public static function generate(int $count = self::RECOVERY_CODE_COUNT): array
    {
        $alphabetLen = strlen(self::ALPHABET);
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $parts = [];
            for ($g = 0; $g < self::GROUPS; $g++) {
                $chunk = '';
                for ($j = 0; $j < self::GROUP_LENGTH; $j++) {
                    $chunk .= self::ALPHABET[random_int(0, $alphabetLen - 1)];
                }
                $parts[] = $chunk;
            }
            $codes[] = implode('-', $parts);
        }

        return $codes;
    }

    /**
     * Normalize typed recovery code (remove spaces and dashes, convert to uppercase).
     */
    public static function normalize(string $input): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', $input));
    }

    /**
     * Check if a string has the structural characteristics of a recovery code.
     */
    public static function looksLikeRecoveryCode(string $input): bool
    {
        $normalized = self::normalize($input);
        $expectedLength = self::GROUP_LENGTH * self::GROUPS;

        if (strlen($normalized) !== $expectedLength) {
            return false;
        }

        for ($i = 0; $i < $expectedLength; $i++) {
            if (strpos(self::ALPHABET, $normalized[$i]) === false) {
                return false;
            }
        }

        return true;
    }
}
