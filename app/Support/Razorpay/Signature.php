<?php

declare(strict_types=1);

namespace App\Support\Razorpay;

/**
 * Razorpay signature verification, ported from
 * apps/api/src/payments/razorpay/razorpay-signature.ts.
 *
 * Two distinct signatures, with different secrets and different payloads. Confusing them means
 * accepting a forged payment, so they are separate functions here as they are there.
 */
final class Signature
{
    public static function hmacSha256Hex(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /** Constant-time hex digest comparison. */
    public static function sameHexDigest(string $expected, ?string $received): bool
    {
        if ($received === null || $received === '') {
            return false;
        }

        return hash_equals($expected, $received);
    }

    /** The signature Razorpay Checkout returns to the browser after a successful payment. */
    public static function checkout(string $providerOrderId, string $providerPaymentId, string $keySecret): string
    {
        return self::hmacSha256Hex($providerOrderId.'|'.$providerPaymentId, $keySecret);
    }

    public static function verifyCheckout(
        string $providerOrderId,
        string $providerPaymentId,
        ?string $signature,
        string $keySecret,
    ): bool {
        return self::sameHexDigest(self::checkout($providerOrderId, $providerPaymentId, $keySecret), $signature);
    }

    /**
     * The webhook signature, computed over the EXACT bytes Razorpay sent.
     *
     * The raw body must be captured before any middleware parses and re-serialises it: re-encoding
     * JSON changes key order and whitespace, which changes the digest, which rejects every genuine
     * delivery. This is the single easiest thing to get wrong in this port.
     */
    public static function webhook(string $rawBody, string $webhookSecret): string
    {
        return self::hmacSha256Hex($rawBody, $webhookSecret);
    }

    public static function verifyWebhook(string $rawBody, ?string $signature, string $webhookSecret): bool
    {
        return self::sameHexDigest(self::webhook($rawBody, $webhookSecret), $signature);
    }
}
