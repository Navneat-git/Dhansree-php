<?php

declare(strict_types=1);

namespace App\Support\Crypto;

/**
 * Authenticated encryption for the few secrets that must be stored and read back rather than
 * hashed — today only an admin's TOTP seed, which the server needs in the clear to compute a code.
 *
 * Wire-compatible with the Node implementation (apps/api/src/auth/secret-box.ts), so the existing
 * `admin_totp_credentials` rows decrypt without re-enrolment:
 *
 *   v1.<key id>.<iv>.<tag>.<ciphertext>      — all base64url
 *
 * AES-256-GCM rather than CBC so a tampered ciphertext fails loudly instead of decrypting to
 * rubbish, and so a row copied from another account cannot be made to verify: the record's own
 * label is bound in as additional authenticated data.
 *
 * Laravel's own Crypt facade is deliberately NOT used here. It has its own envelope format and key
 * derivation, and would not read a single existing row.
 */
final class SecretBox
{
    public const VERSION = 'v1';

    private const IV_BYTES = 12;   // 96 bits, the size GCM is specified for
    private const TAG_BYTES = 16;
    private const KEY_BYTES = 32;

    private ?string $key;
    private string $keyId;

    /** @param string|null $base64Key the raw value of SECRET_ENCRYPTION_KEY (32 bytes, base64). */
    public function __construct(?string $base64Key)
    {
        $decoded = $base64Key === null || $base64Key === '' ? false : base64_decode($base64Key, true);
        $this->key = is_string($decoded) && strlen($decoded) === self::KEY_BYTES ? $decoded : null;
        $this->keyId = $this->key === null ? 'none' : self::fingerprint($this->key);
    }

    /** False when no key is configured: features that need one must refuse rather than store plaintext. */
    public function available(): bool
    {
        return $this->key !== null;
    }

    /**
     * @param string $label bound into the ciphertext as additional authenticated data. Pass
     *   something identifying the row (e.g. `admin-totp:42`) so a sealed value cannot be moved.
     */
    public function seal(string $plaintext, string $label): string
    {
        $key = $this->requireKey();
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $label,
            self::TAG_BYTES,
        );

        if ($ciphertext === false) {
            throw new SecretBoxException('Value could not be encrypted');
        }

        return implode('.', [
            self::VERSION,
            $this->keyId,
            self::b64UrlEncode($iv),
            self::b64UrlEncode($tag),
            self::b64UrlEncode($ciphertext),
        ]);
    }

    /** @throws SecretBoxException if tampered with, sealed under another key, or relabelled. */
    public function open(string $sealed, string $label): string
    {
        $key = $this->requireKey();
        $parts = explode('.', $sealed);

        if (count($parts) !== 5 || $parts[0] !== self::VERSION) {
            throw new SecretBoxException('Sealed value is not in the expected format');
        }

        [, $keyId, $iv, $tag, $ciphertext] = $parts;

        if (! hash_equals($this->keyId, $keyId)) {
            throw new SecretBoxException('Sealed value was encrypted with a different key');
        }

        $plaintext = openssl_decrypt(
            self::b64UrlDecode($ciphertext),
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            self::b64UrlDecode($iv),
            self::b64UrlDecode($tag),
            $label,
        );

        if ($plaintext === false) {
            // Never surface the underlying reason: it distinguishes a wrong tag from wrong padding.
            throw new SecretBoxException('Sealed value could not be decrypted');
        }

        return $plaintext;
    }

    /** Exactly 32 bytes of base64. Used when validating the environment. */
    public static function isValidEncryptionKey(string $value): bool
    {
        $decoded = base64_decode($value, true);

        return is_string($decoded) && strlen($decoded) === self::KEY_BYTES;
    }

    private function requireKey(): string
    {
        if ($this->key === null) {
            throw new SecretBoxException(
                'SECRET_ENCRYPTION_KEY is not configured, so secrets cannot be encrypted or read',
            );
        }

        return $this->key;
    }

    /** Eight characters of the key's own SHA-256: identifies a key without revealing any of it. */
    private static function fingerprint(string $key): string
    {
        return substr(self::b64UrlEncode(hash('sha256', $key, true)), 0, 8);
    }

    private static function b64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if (! is_string($decoded)) {
            throw new SecretBoxException('Sealed value is not valid base64url');
        }

        return $decoded;
    }
}
