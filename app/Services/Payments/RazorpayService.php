<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Support\Razorpay\Signature;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RazorpayService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;
    protected bool $enabled;

    public function __construct()
    {
        $this->keyId = (string) config('store.razorpay.key_id', env('RAZORPAY_KEY_ID', ''));
        $this->keySecret = (string) config('store.razorpay.key_secret', env('RAZORPAY_KEY_SECRET', ''));
        $this->webhookSecret = (string) config('store.razorpay.webhook_secret', env('RAZORPAY_WEBHOOK_SECRET', ''));
        $this->enabled = (bool) config('store.razorpay_enabled', env('RAZORPAY_ENABLED', false));
    }

    public function isEnabled(): bool
    {
        $enabled = (bool) config('store.razorpay_enabled', $this->enabled);
        $keyId = $this->getKeyId();
        $keySecret = $this->getKeySecret();

        return $enabled && !empty($keyId) && !empty($keySecret);
    }

    public function getKeyId(): string
    {
        return (string) config('store.razorpay.key_id', $this->keyId);
    }

    public function getKeySecret(): string
    {
        return (string) config('store.razorpay.key_secret', $this->keySecret);
    }

    public function getWebhookSecret(): string
    {
        return (string) config('store.razorpay.webhook_secret', $this->webhookSecret);
    }

    /**
     * Create an order on Razorpay.
     *
     * @param array<string, string> $notes
     * @return array{
     *     id: string,
     *     amount: int,
     *     currency: string
     * }
     */
    public function createOrder(int $amountPaise, string $currency, string $receipt, array $notes = []): array
    {
        if (!$this->isEnabled()) {
            // Simulated mock order for local testing or when keys are unconfigured
            return [
                'id' => 'order_mock_' . bin2hex(random_bytes(8)),
                'amount' => $amountPaise,
                'currency' => $currency,
            ];
        }

        $response = Http::withBasicAuth($this->getKeyId(), $this->getKeySecret())
            ->timeout(15)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => $currency,
                'receipt' => $receipt,
                'notes' => $notes,
                'partial_payment' => false,
            ]);

        if ($response->failed()) {
            Log::error('Razorpay order creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Unable to initialize online payment with Razorpay: ' . ($response->json('error.description') ?? 'Gateway error'));
        }

        $data = $response->json();

        return [
            'id' => $data['id'],
            'amount' => (int) $data['amount'],
            'currency' => (string) $data['currency'],
        ];
    }

    /**
     * Verify client-side checkout signature.
     */
    public function verifyCheckoutSignature(string $orderId, string $paymentId, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        if (!$this->isEnabled() && str_starts_with($orderId, 'order_mock_')) {
            return true;
        }

        return Signature::verifyCheckout($orderId, $paymentId, $signature, $this->getKeySecret());
    }

    /**
     * Verify server-side webhook signature over raw payload.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = $this->getWebhookSecret();
        if (!$signature || empty($secret)) {
            return false;
        }

        return Signature::verifyWebhook($rawBody, $signature, $secret);
    }
}
