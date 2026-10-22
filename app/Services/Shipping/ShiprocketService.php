<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShiprocketService
{
    protected string $apiBaseUrl;
    protected string $email;
    protected string $password;
    protected string $webhookToken;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiBaseUrl = rtrim((string) config('store.shiprocket.api_base_url', 'https://apiv2.shiprocket.in/v1/external'), '/');
        $this->email = (string) config('store.shiprocket.email', env('SHIPROCKET_EMAIL', ''));
        $this->password = (string) config('store.shiprocket.password', env('SHIPROCKET_PASSWORD', ''));
        $this->webhookToken = (string) config('store.shiprocket.webhook_token', env('SHIPROCKET_WEBHOOK_TOKEN', ''));
        $this->enabled = (bool) config('store.shiprocket_enabled', env('SHIPROCKET_ENABLED', false));
    }

    public function isEnabled(): bool
    {
        $enabled = (bool) config('store.shiprocket_enabled', $this->enabled);
        $email = (string) config('store.shiprocket.email', $this->email);
        $password = (string) config('store.shiprocket.password', $this->password);

        return $enabled && !empty($email) && !empty($password);
    }

    /**
     * Get active Bearer token from Cache or perform login.
     */
    public function getToken(): string
    {
        if (!$this->isEnabled()) {
            return 'mock_shiprocket_token';
        }

        return Cache::remember('shiprocket_bearer_token', 8 * 86400, function () {
            $response = Http::timeout(15)->post($this->apiBaseUrl . '/auth/login', [
                'email' => config('store.shiprocket.email', $this->email),
                'password' => config('store.shiprocket.password', $this->password),
            ]);

            if ($response->failed()) {
                Log::error('Shiprocket login authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new RuntimeException('Failed to authenticate with Shiprocket API: ' . ($response->json('message') ?? 'Invalid credentials'));
            }

            $token = (string) $response->json('token');
            if (empty($token)) {
                throw new RuntimeException('Shiprocket did not return a valid bearer token.');
            }

            return $token;
        });
    }

    /**
     * Check available couriers and rates for destination postal code.
     *
     * @return array<int, array{
     *     courier_id: int,
     *     courier_name: string,
     *     rate_paise: int,
     *     estimated_days: int,
     *     etd: string
     * }>
     */
    public function checkServiceability(
        string $pickupPincode,
        string $deliveryPincode,
        int $weightGrams,
        bool $isCod = false
    ): array {
        if (!$this->isEnabled()) {
            // Simulated response for local development / testing
            return [
                [
                    'courier_id' => 1,
                    'courier_name' => 'Bluedart Surface',
                    'rate_paise' => 4900,
                    'estimated_days' => 3,
                    'etd' => now()->addDays(3)->format('Y-m-d'),
                ],
                [
                    'courier_id' => 2,
                    'courier_name' => 'Delhivery Express',
                    'rate_paise' => 6500,
                    'estimated_days' => 2,
                    'etd' => now()->addDays(2)->format('Y-m-d'),
                ],
            ];
        }

        $token = $this->getToken();
        $weightKg = round($weightGrams / 1000, 2);

        $response = Http::withToken($token)
            ->timeout(15)
            ->get($this->apiBaseUrl . '/courier/serviceability/', [
                'pickup_postcode' => $pickupPincode,
                'delivery_postcode' => $deliveryPincode,
                'weight' => (string) $weightKg,
                'cod' => $isCod ? '1' : '0',
            ]);

        if ($response->failed()) {
            Log::warning('Shiprocket serviceability query failed', ['response' => $response->body()]);
            return [];
        }

        $data = $response->json();
        $courierList = $data['data']['available_courier_companies'] ?? [];

        $results = [];
        foreach ($courierList as $c) {
            $rateRupees = (float) ($c['rate'] ?? 0);
            $results[] = [
                'courier_id' => (int) $c['courier_company_id'],
                'courier_name' => (string) $c['courier_name'],
                'rate_paise' => (int) round($rateRupees * 100),
                'estimated_days' => (int) ($c['estimated_delivery_days'] ?? 4),
                'etd' => (string) ($c['etd'] ?? now()->addDays(4)->format('Y-m-d')),
            ];
        }

        return $results;
    }

    /**
     * Create an Adhoc Order on Shiprocket.
     *
     * @return array{
     *     order_id: int|string,
     *     shipment_id: int|string
     * }
     */
    public function createOrder(Order $order, Shipment $shipment): array
    {
        if (!$this->isEnabled()) {
            $mockNum = random_int(100000, 999999);
            return [
                'order_id' => 'SR_ORD_' . $mockNum,
                'shipment_id' => 'SR_SHP_' . $mockNum,
            ];
        }

        $token = $this->getToken();
        $shippingAddress = $order->shippingAddress ?? $order->addresses()->where('type', 'SHIPPING')->first();

        $itemsPayload = [];
        foreach ($order->items as $item) {
            $itemsPayload[] = [
                'name' => $item->product_name,
                'sku' => $item->sku,
                'units' => $item->quantity,
                'selling_price' => round($item->unit_price_paise / 100, 2),
                'discount' => round($item->discount_paise / 100, 2),
                'tax' => round($item->tax_paise / 100, 2),
                'hsn' => $item->hsn_code ?? '6204',
            ];
        }

        $isCod = $order->payment_method->value === 'COD';

        $payload = [
            'order_id' => $shipment->reference,
            'order_date' => $order->placed_at ? $order->placed_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
            'pickup_location' => $shipment->pickup_location,
            'channel_id' => '',
            'comment' => $order->customer_note ?? 'Dhansree Web Order',
            'billing_customer_name' => $shippingAddress->recipient_name ?? $order->customer_name,
            'billing_last_name' => '',
            'billing_address' => $shippingAddress->line1,
            'billing_address_2' => $shippingAddress->line2 ?? '',
            'billing_city' => $shippingAddress->city,
            'billing_pincode' => $shippingAddress->postal_code,
            'billing_state' => $shippingAddress->state_name ?? $shippingAddress->state_code,
            'billing_country' => 'India',
            'billing_email' => $order->customer_email,
            'billing_phone' => substr(preg_replace('/\D/', '', $order->customer_phone), -10),
            'shipping_is_billing' => true,
            'order_items' => $itemsPayload,
            'payment_method' => $isCod ? 'COD' : 'Prepaid',
            'sub_total' => round($order->subtotal_paise / 100, 2),
            'length' => $shipment->length_cm,
            'breadth' => $shipment->breadth_cm,
            'height' => $shipment->height_cm,
            'weight' => round($shipment->weight_grams / 1000, 2),
        ];

        $response = Http::withToken($token)
            ->timeout(20)
            ->post($this->apiBaseUrl . '/orders/create/adhoc', $payload);

        if ($response->failed()) {
            Log::error('Shiprocket create order failed', ['body' => $response->body()]);
            throw new RuntimeException('Shiprocket order creation failed: ' . ($response->json('message') ?? 'Service error'));
        }

        $res = $response->json();

        return [
            'order_id' => $res['order_id'] ?? $res['data']['order_id'] ?? null,
            'shipment_id' => $res['shipment_id'] ?? $res['data']['shipment_id'] ?? null,
        ];
    }

    /**
     * Request AWB assignment for a Shiprocket shipment.
     *
     * @return array{
     *     awb: string,
     *     courier_name: string,
     *     courier_id: string|int,
     *     tracking_url: string|null
     * }
     */
    public function assignAwb(string|int $shipmentId, ?int $courierId = null): array
    {
        if (!$this->isEnabled()) {
            $mockAwb = 'AWB' . random_int(1000000000, 9999999999);
            return [
                'awb' => $mockAwb,
                'courier_name' => 'Bluedart Surface',
                'courier_id' => 1,
                'tracking_url' => 'https://shiprocket.co/tracking/' . $mockAwb,
            ];
        }

        $token = $this->getToken();
        $payload = [
            'shipment_id' => (int) $shipmentId,
        ];
        if ($courierId) {
            $payload['courier_id'] = $courierId;
        }

        $response = Http::withToken($token)
            ->timeout(20)
            ->post($this->apiBaseUrl . '/courier/assign/awb', $payload);

        if ($response->failed()) {
            Log::error('Shiprocket AWB assignment failed', ['body' => $response->body()]);
            throw new RuntimeException('Shiprocket AWB assignment failed: ' . ($response->json('message') ?? 'Service error'));
        }

        $data = $response->json();
        $awbCode = $data['response']['data']['awb_code'] ?? null;

        if (!$awbCode) {
            throw new RuntimeException('Shiprocket did not return an AWB code: ' . ($data['message'] ?? ''));
        }

        return [
            'awb' => (string) $awbCode,
            'courier_name' => (string) ($data['response']['data']['courier_name'] ?? 'Courier Partner'),
            'courier_id' => $data['response']['data']['courier_company_id'] ?? 1,
            'tracking_url' => 'https://shiprocket.co/tracking/' . $awbCode,
        ];
    }

    /**
     * Generate Shipping Label URL.
     */
    public function generateLabel(string|int $shipmentId): string
    {
        if (!$this->isEnabled()) {
            return 'https://shiprocket.co/sample-label.pdf';
        }

        $token = $this->getToken();
        $response = Http::withToken($token)
            ->timeout(20)
            ->post($this->apiBaseUrl . '/courier/generate/label', [
                'shipment_id' => [(int) $shipmentId],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to generate shipping label from Shiprocket');
        }

        return (string) ($response->json('label_url') ?? '');
    }

    /**
     * Verify Shiprocket Webhook security token.
     */
    public function verifyWebhookToken(?string $headerToken): bool
    {
        $expected = (string) config('store.shiprocket.webhook_token', $this->webhookToken);
        if (empty($expected)) {
            return true; // Token check disabled if unconfigured
        }

        return hash_equals($expected, (string) $headerToken);
    }
}
