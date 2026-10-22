<?php

namespace Tests\Unit\Security;

use App\Support\Security\PasswordHasher;
use App\Support\Security\RecoveryCodes;
use App\Support\Security\SecretBox;
use App\Support\Security\Totp;
use Tests\TestCase;

class SecuritySupportTest extends TestCase
{
    public function test_password_hasher_creates_valid_argon2id_hash(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('Test@SecretPassword123');

        $this->assertStringStartsWith('$argon2id$v=19$m=19456,t=2,p=1$', $hash);
        $this->assertTrue($hasher->verify($hash, 'Test@SecretPassword123'));
        $this->assertFalse($hasher->verify($hash, 'WrongPassword'));
        $this->assertFalse($hasher->verifyAgainstDummy('RandomPassword'));
        $this->assertFalse($hasher->needsRehash($hash));
    }

    public function test_secret_box_authenticated_encryption(): void
    {
        $key = base64_encode(random_bytes(32));
        $box = new SecretBox($key);

        $this->assertTrue($box->isAvailable());
        $this->assertSame(8, strlen($box->getKeyId()));

        $secret = 'JBSWY3DPEHPK3PXP';
        $label = 'admin-totp:42';

        $sealed = $box->seal($secret, $label);
        $parts = explode('.', $sealed);

        $this->assertCount(5, $parts);
        $this->assertSame('v1', $parts[0]);
        $this->assertSame($box->getKeyId(), $parts[1]);

        // Decrypt with correct label
        $decrypted = $box->open($sealed, $label);
        $this->assertSame($secret, $decrypted);

        // Fails with tampered label (AAD verification)
        $this->expectException(\RuntimeException::class);
        $box->open($sealed, 'admin-totp:99');
    }

    public function test_totp_code_generation_and_verification(): void
    {
        $secret = Totp::generateSecret();
        $this->assertNotEmpty($secret);

        $now = 1700000000;
        $step = Totp::timeStep($now);

        $code = Totp::code($secret, $step);
        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // Verify matches within current step
        $verifiedStep = Totp::verify($secret, $code, $now);
        $this->assertSame($step, $verifiedStep);

        // Verify within ±1 window (25 seconds earlier)
        $verifiedWindow = Totp::verify($secret, $code, $now + 25);
        $this->assertSame($step, $verifiedWindow);

        // Anti-replay: rejected if step already used
        $replayed = Totp::verify($secret, $code, $now, $step);
        $this->assertNull($replayed);

        // URI generation
        $uri = Totp::uri($secret, 'admin@dhansree.com');
        $this->assertStringStartsWith('otpauth://totp/Dhansree:admin%40dhansree.com?', $uri);
        $this->assertStringContainsString('secret=' . $secret, $uri);
    }

    public function test_recovery_codes_generation_and_normalization(): void
    {
        $codes = RecoveryCodes::generate(10);
        $this->assertCount(10, $codes);

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKMNPQRSTVWXYZ]{5}-[23456789ABCDEFGHJKMNPQRSTVWXYZ]{5}$/', $code);
            $this->assertTrue(RecoveryCodes::looksLikeRecoveryCode($code));

            $normalized = RecoveryCodes::normalize($code);
            $this->assertSame(10, strlen($normalized));
            $this->assertTrue(RecoveryCodes::looksLikeRecoveryCode($normalized));
        }

        // Invalid codes rejected
        $this->assertFalse(RecoveryCodes::looksLikeRecoveryCode('INVALID-CODE-TOO-LONG'));
        $this->assertFalse(RecoveryCodes::looksLikeRecoveryCode('SHORT'));
        $this->assertFalse(RecoveryCodes::looksLikeRecoveryCode('11111-00000')); // 0 and 1 not in alphabet
    }
}
