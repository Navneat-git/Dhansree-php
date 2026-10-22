<?php

namespace App\Support\Security;

use RuntimeException;

class SecretBox
{
    public const VERSION = 'v1';
    public const IV_BYTES = 12; // 96 bits for GCM
    public const KEY_BYTES = 32;

    private ?string $key;
    private string $keyId;

    public function __construct(?string $base64Key = null)
    {
        $base64Key = $base64Key ?? config('app.secret_encryption_key') ?? env('SECRET_ENCRYPTION_KEY');

        if ($base64Key) {
            $decoded = base64_decode($base64Key, true);
            if ($decoded !== false && strlen($decoded) === self::KEY_BYTES) {
                $this->key = $decoded;
                $this->keyId = self::fingerprint($this->key);
                return;
            }
        }

        $this->key = null;
        $this->keyId = 'none';
    }

    public function isAvailable(): bool
    {
        return $this->key !== null;
    }

    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Authenticated encryption with AES-256-GCM.
     *
     * @param string $plaintext Value to seal
     * @param string $label Bound into ciphertext as additional authenticated data (AAD)
     * @return string Sealed format: v1.<keyId>.<iv>.<tag>.<ciphertext>
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
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed');
        }

        return implode('.', [
            self::VERSION,
            $this->keyId,
            self::base64UrlEncode($iv),
            self::base64UrlEncode($tag),
            self::base64UrlEncode($ciphertext),
        ]);
    }

    /**
     * Decrypt and verify ciphertext with AES-256-GCM.
     *
     * @param string $sealed Sealed format
     * @param string $label Bound additional authenticated data (AAD)
     * @throws RuntimeException
     */
    public function open(string $sealed, string $label): string
    {
        $key = $this->requireKey();
        $parts = explode('.', $sealed);

        if (count($parts) !== 5 || $parts[0] !== self::VERSION) {
            throw new RuntimeException('Sealed value is not in the expected format');
        }

        [, $keyId, $ivEncoded, $tagEncoded, $ciphertextEncoded] = $parts;

        if ($keyId !== $this->keyId) {
            throw new RuntimeException('Sealed value was encrypted with a different key');
        }

        $iv = self::base64UrlDecode($ivEncoded);
        $tag = self::base64UrlDecode($tagEncoded);
        $ciphertext = self::base64UrlDecode($ciphertextEncoded);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $label
        );

        if ($plaintext === false) {
            throw new RuntimeException('Sealed value could not be decrypted');
        }

        return $plaintext;
    }

    private function requireKey(): string
    {
        if ($this->key === null) {
            throw new RuntimeException('SECRET_ENCRYPTION_KEY is not configured, so secrets cannot be encrypted or read');
        }
        return $this->key;
    }

    /**
     * Eight characters of a key's SHA-256 in base64url.
     */
    public static function fingerprint(string $rawKey): string
    {
        $hash = hash('sha256', $rawKey, true);
        return substr(self::base64UrlEncode($hash), 0, 8);
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
