<?php

namespace App\Support\Security;

use InvalidArgumentException;

class Totp
{
    public const DIGITS = 6;
    public const PERIOD_SECONDS = 30;
    public const WINDOW_STEPS = 1;
    public const SECRET_BYTES = 20; // 160 bits

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random base32 TOTP secret.
     */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /**
     * The time step a unix timestamp falls in.
     */
    public static function timeStep(int $timestamp): int
    {
        return (int) floor($timestamp / self::PERIOD_SECONDS);
    }

    /**
     * Generate 6-digit TOTP code for a secret and time step.
     */
    public static function code(string $secret, int $timeStep): string
    {
        $key = self::base32Decode($secret);
        // 8-byte big-endian binary counter
        $counter = pack('J', $timeStep);

        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;

        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a code against the current window (±1 step).
     *
     * @param string $secret Base32 secret
     * @param string $code User-submitted code
     * @param int $timestamp Current unix timestamp
     * @param int|null $usedStep The last spent time step (anti-replay)
     * @return int|null Matching time step on success, null on failure
     */
    public static function verify(string $secret, string $code, int $timestamp, ?int $usedStep = null): ?int
    {
        $candidate = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $candidate)) {
            return null;
        }

        $currentStep = self::timeStep($timestamp);

        for ($offset = -self::WINDOW_STEPS; $offset <= self::WINDOW_STEPS; $offset++) {
            $step = $currentStep + $offset;
            if ($usedStep !== null && $step <= $usedStep) {
                continue;
            }

            if (hash_equals(self::code($secret, $step), $candidate)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Generate standard otpauth:// URI for QR codes.
     */
    public static function uri(string $secret, string $account, string $issuer = 'Dhansree'): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD_SECONDS,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    public static function base32Encode(string $data): string
    {
        $bits = 0;
        $value = 0;
        $output = '';

        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            $value = ($value << 8) | $byte;
            $bits += 8;

            while ($bits >= 5) {
                $output .= self::BASE32_ALPHABET[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }

        if ($bits > 0) {
            $output .= self::BASE32_ALPHABET[($value << (5 - bits)) & 31];
        }

        return $output;
    }

    public static function base32Decode(string $input): string
    {
        $normalized = strtoupper(rtrim(preg_replace('/\s+/', '', $input), '='));
        $bits = 0;
        $value = 0;
        $output = '';

        for ($i = 0; $i < strlen($normalized); $i++) {
            $char = $normalized[$i];
            $index = strpos(self::BASE32_ALPHABET, $char);
            if ($index === false) {
                throw new InvalidArgumentException('Secret is not valid base32');
            }

            $value = ($value << 5) | $index;
            $bits += 5;

            if ($bits >= 8) {
                $output .= chr(($value >> ($bits - 8)) & 0xff);
                $bits -= 8;
            }
        }

        return $output;
    }
}
