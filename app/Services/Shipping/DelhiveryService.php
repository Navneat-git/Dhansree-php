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

class DelhiveryService
{
    protected string $apiBaseUrl;
    protected string $apiToken;
    protected string $clientName;
    protected string $pickupLocation;
    protected string $mode;
    protected bool $enabled;

    public function __construct()
    {
        $this->mode = (string) config('store.delhivery.mode', env('DELHIVERY_MODE', 'production'));
        $defaultUrl = $this->mode === 'staging'
            ? 'https://staging-express.delhivery.com'
            : 'https://track.delhivery.com';

        $this->apiBaseUrl = rtrim((string) config('store.delhivery.api_base_url', $defaultUrl), '/');
        $this->apiToken = trim((string) config('store.delhivery.api_token', env('DELHIVERY_API_TOKEN', '')));
        $this->clientName = (string) config('store.delhivery.client_name', env('DELHIVERY_CLIENT_NAME', 'DHANSREE'));
        $this->pickupLocation = (string) config('store.delhivery.pickup_location', env('DELHIVERY_PICKUP_LOCATION', 'DHANSHREE TRENDS B2C'));
        $this->enabled = (bool) config('store.delhivery_enabled', env('DELHIVERY_ENABLED', false));
    }

    public function isEnabled(): bool
    {
        $enabled = (bool) config('store.delhivery_enabled', $this->enabled);
        $token = (string) config('store.delhivery.api_token', $this->apiToken);

        return $enabled && !empty($token);
    }

    public function isMock(): bool
    {
        return app()->environment('testing') || !$this->isEnabled();
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getPickupLocation(): string
    {
        return $this->pickupLocation;
    }

    /**
     * Check Delhivery postal code serviceability (COD, Prepaid, Pickup).
     *
     * @return array{
     *     serviceable: bool,
     *     prepaid: bool,
     *     cod: bool,
     *     pickup: bool,
     *     city: string|null,
     *     district: string|null,
     *     state_code: string|null
     * }
     */
    public function checkPincode(string $pincode): array
    {
        $cleanPin = trim($pincode);
        if (!preg_match('/^[1-9][0-9]{5}$/', $cleanPin)) {
            return [
                'serviceable' => false,
                'prepaid' => false,
                'cod' => false,
                'pickup' => false,
                'city' => null,
                'district' => null,
                'state_code' => null,
            ];
        }

        if ($this->isMock()) {
            return [
                'serviceable' => true,
                'prepaid' => true,
                'cod' => true,
                'pickup' => true,
                'city' => 'Jaipur',
                'district' => 'Jaipur',
                'state_code' => 'RJ',
            ];
        }

        return Cache::remember("delhivery_pin_{$cleanPin}", 86400, function () use ($cleanPin) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $this->apiToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(12)
                ->get($this->apiBaseUrl . '/c/api/pin-codes/json/', [
                    'filter_codes' => $cleanPin,
                ]);

                if ($response->failed()) {
                    Log::warning('Delhivery pincode check failed', [
                        'status' => $response->status(),
                        'pincode' => $cleanPin,
                    ]);
                    return [
                        'serviceable' => false,
                        'prepaid' => false,
                        'cod' => false,
                        'pickup' => false,
                        'city' => null,
                        'district' => null,
                        'state_code' => null,
                    ];
                }

                $data = $response->json();
                $codes = $data['delivery_codes'] ?? [];
                if (empty($codes)) {
                    return [
                        'serviceable' => false,
                        'prepaid' => false,
                        'cod' => false,
                        'pickup' => false,
                        'city' => null,
                        'district' => null,
                        'state_code' => null,
                    ];
                }

                $pinInfo = $codes[0]['postal_code'] ?? [];
                $cod = strtoupper((string) ($pinInfo['cod'] ?? 'N')) === 'Y';
                $prepaid = strtoupper((string) ($pinInfo['pre_paid'] ?? 'N')) === 'Y';
                $pickup = strtoupper((string) ($pinInfo['pickup'] ?? 'N')) === 'Y';

                return [
                    'serviceable' => $cod || $prepaid,
                    'prepaid' => $prepaid,
                    'cod' => $cod,
                    'pickup' => $pickup,
                    'city' => $pinInfo['city'] ?? null,
                    'district' => $pinInfo['district'] ?? null,
                    'state_code' => $pinInfo['state_code'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::warning('Delhivery pincode query exception', ['error' => $e->getMessage()]);
                return [
                    'serviceable' => false,
                    'prepaid' => false,
                    'cod' => false,
                    'pickup' => false,
                    'city' => null,
                    'district' => null,
                    'state_code' => null,
                ];
            }
        });
    }

    /**
     * Calculate delivery rate using Delhivery's live invoice charges API.
     *
     * @param string $originPin (e.g. '302039')
     * @param string $destPin
     * @param int $weightGrams
     * @param bool $isCod
     * @param string $mode 'S' (Surface) or 'E' (Express)
     * @return array{
     *     rate_paise: int,
     *     gross_paise: int,
     *     zone: string|null,
     *     charged_weight: int
     * }|null
     */
    public function calculateRate(
        string $originPin,
        string $destPin,
        int $weightGrams,
        bool $isCod = false,
        string $mode = 'S'
    ): ?array {
        if ($this->isMock()) {
            $basePaise = $mode === 'E' ? 6500 : 4500;
            return [
                'rate_paise' => $isCod ? $basePaise + 4000 : $basePaise,
                'gross_paise' => $basePaise,
                'zone' => 'B',
                'charged_weight' => max(100, $weightGrams),
            ];
        }

        $cacheKey = "delhivery_rate_{$originPin}_{$destPin}_{$weightGrams}_" . ($isCod ? 'cod' : 'prep') . "_{$mode}";

        return Cache::remember($cacheKey, 3600, function () use ($originPin, $destPin, $weightGrams, $isCod, $mode) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $this->apiToken,
                    'Accept' => 'application/json',
                ])
                ->timeout(12)
                ->get($this->apiBaseUrl . '/api/kinko/v1/invoice/charges/.json', [
                    'md' => $mode === 'E' ? 'E' : 'S',
                    'ss' => 'Delivered',
                    'd_pin' => trim($destPin),
                    'o_pin' => trim($originPin),
                    'cgm' => max(100, $weightGrams),
                    'pt' => $isCod ? 'COD' : 'Pre-paid',
                ]);

                if ($response->failed()) {
                    Log::warning('Delhivery rate calculation failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return null;
                }

                $data = $response->json();
                if (!is_array($data) || empty($data[0])) {
                    return null;
                }

                $charge = $data[0];
                $totalAmount = (float) ($charge['total_amount'] ?? 0);
                $grossAmount = (float) ($charge['gross_amount'] ?? $totalAmount);

                return [
                    'rate_paise' => (int) round($totalAmount * 100),
                    'gross_paise' => (int) round($grossAmount * 100),
                    'zone' => $charge['zone'] ?? null,
                    'charged_weight' => (int) ($charge['charged_weight'] ?? $weightGrams),
                ];
            } catch (\Throwable $e) {
                Log::warning('Delhivery rate query exception', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get available shipping options for checkout / order preview.
     *
     * @return array<int, array{
     *     courier_id: string,
     *     courier_name: string,
     *     provider: string,
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
        $pinCheck = $this->checkPincode($deliveryPincode);
        if (!$pinCheck['serviceable']) {
            return [];
        }

        if ($isCod && !$pinCheck['cod']) {
            return [];
        }

        $results = [];

        // 1. Delhivery Surface
        $surfaceRate = $this->calculateRate($pickupPincode, $deliveryPincode, $weightGrams, $isCod, 'S');
        $ratePaiseSurface = $surfaceRate['rate_paise'] ?? ($isCod ? 8500 : 4900);
        $results[] = [
            'courier_id' => 'delhivery_surface',
            'courier_name' => 'Delhivery Surface',
            'provider' => 'delhivery',
            'rate_paise' => $ratePaiseSurface,
            'estimated_days' => 4,
            'etd' => now()->addDays(4)->format('Y-m-d'),
        ];

        // 2. Delhivery Express (Air)
        $expressRate = $this->calculateRate($pickupPincode, $deliveryPincode, $weightGrams, $isCod, 'E');
        $ratePaiseExpress = $expressRate['rate_paise'] ?? ($isCod ? 10500 : 6900);
        $results[] = [
            'courier_id' => 'delhivery_express',
            'courier_name' => 'Delhivery Express (Air)',
            'provider' => 'delhivery',
            'rate_paise' => $ratePaiseExpress,
            'estimated_days' => 2,
            'etd' => now()->addDays(2)->format('Y-m-d'),
        ];

        return $results;
    }

    /**
     * Create forward shipment on Delhivery and automatically generate AWB.
     *
     * @return array{
     *     awb: string,
     *     order_id: string,
     *     status: string,
     *     courier_name: string,
     *     tracking_url: string
     * }
     */
    public function createShipment(Order $order, Shipment $shipment, array $options = []): array
    {
        if ($this->isMock()) {
            $mockAwb = 'DEL' . random_int(1000000000, 9999999999);
            return [
                'awb' => $mockAwb,
                'order_id' => $shipment->reference,
                'status' => 'Success',
                'courier_name' => 'Delhivery Surface',
                'tracking_url' => "https://www.delhivery.com/track/package/{$mockAwb}",
            ];
        }

        $shippingAddress = $order->shippingAddress ?? $order->addresses()->where('type', 'SHIPPING')->first();
        if (!$shippingAddress) {
            throw new RuntimeException("Order {$order->order_number} has no valid shipping address.");
        }

        $store = StoreSettings::current();
        $isCod = $order->payment_method->value === 'COD';
        $shippingMode = ($options['shipping_mode'] ?? 'Surface') === 'Express' ? 'Express' : 'Surface';

        // Products description
        $productTitles = [];
        foreach ($order->items as $item) {
            $productTitles[] = $item->quantity . 'x ' . ($item->product_name ?: 'Saree');
        }
        $productsDesc = mb_substr(implode(', ', $productTitles), 0, 200);

        $customerName = trim((string) ($shippingAddress->recipient_name ?: $order->customer_name ?: 'Valued Customer'));
        $cleanPhone = substr(preg_replace('/\D/', '', (string) ($shippingAddress->phone ?: $order->customer_phone ?: '')), -10);
        if (strlen($cleanPhone) < 10) {
            $cleanPhone = '9999999999';
        }

        $fullAddress = trim($shippingAddress->line1 . ($shippingAddress->line2 ? ', ' . $shippingAddress->line2 : ''));
        $pickupLoc = !empty($shipment->pickup_location) && $shipment->pickup_location !== 'Store / By Hand'
            ? $shipment->pickup_location
            : $this->pickupLocation;

        $returnPincode = (string) ($store->postal_code ?? '302039');
        $returnAddress = (string) ($store->address_line1 ?? 'C-197 MURLIPURA SCHEME JAIPUR');
        $returnCity = (string) ($store->city ?? 'JAIPUR');
        $returnState = (string) ($store->state_code ?? 'Rajasthan');
        $returnPhone = substr(preg_replace('/\D/', '', (string) ($store->support_phone ?? '919887776777')), -10);

        $packageData = [
            'name' => $customerName,
            'add' => $fullAddress,
            'pin' => (string) $shippingAddress->postal_code,
            'city' => (string) $shippingAddress->city,
            'state' => (string) ($shippingAddress->state_name ?: $shippingAddress->state_code ?: 'Rajasthan'),
            'country' => 'India',
            'phone' => $cleanPhone,
            'order' => $shipment->reference,
            'payment_mode' => $isCod ? 'COD' : 'Pre-paid',
            'return_pin' => $returnPincode,
            'return_city' => $returnCity,
            'return_phone' => $returnPhone,
            'return_add' => $returnAddress,
            'return_state' => $returnState,
            'return_country' => 'India',
            'products_desc' => $productsDesc,
            'hsn_code' => $order->items->first()?->hsn_code ?? '6204',
            'cod_amount' => $isCod ? number_format($order->total_rupees, 2, '.', '') : '0',
            'order_date' => $order->placed_at ? $order->placed_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'total_amount' => number_format($order->total_rupees, 2, '.', ''),
            'seller_add' => $returnAddress,
            'seller_name' => $this->clientName,
            'seller_inv' => $order->order_number,
            'quantity' => (string) $order->items->sum('quantity'),
            'waybill' => '', // Empty waybill tells Delhivery to allocate AWB automatically!
            'shipment_width' => max(1, $shipment->breadth_cm),
            'shipment_height' => max(1, $shipment->height_cm),
            'weight' => max(100, $shipment->weight_grams),
            'shipping_mode' => $shippingMode,
            'address_type' => 'home',
        ];

        $payload = [
            'shipments' => [$packageData],
            'pickup_location' => [
                'name' => $pickupLoc,
            ],
        ];

        Log::info('Delhivery create shipment request', [
            'order' => $order->order_number,
            'pickup_location' => $pickupLoc,
            'shipping_mode' => $shippingMode,
            'payment_mode' => $isCod ? 'COD' : 'Pre-paid',
        ]);

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Token ' . $this->apiToken,
                'Accept' => 'application/json',
            ])
            ->timeout(25)
            ->post($this->apiBaseUrl . '/api/cmu/create.json', [
                'format' => 'json',
                'data' => json_encode($payload),
            ]);

        if ($response->failed()) {
            Log::error('Delhivery create shipment HTTP failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Delhivery API returned HTTP ' . $response->status() . ': ' . $response->body());
        }

        $res = $response->json();
        Log::info('Delhivery create shipment response', ['response' => $res]);

        $packages = $res['packages'] ?? [];
        if (empty($packages) || empty($packages[0]['waybill'])) {
            $rawRemarks = [];
            if (!empty($packages[0]['remarks'])) {
                $rawRemarks = is_array($packages[0]['remarks']) ? $packages[0]['remarks'] : [$packages[0]['remarks']];
            }
            $allRemarksText = implode(' ', $rawRemarks);
            if (!empty($res['rmk'])) {
                $allRemarksText .= ' ' . (string) $res['rmk'];
            }

            // Check for wallet/balance exhaustion on prepaid Delhivery accounts
            if (stripos($allRemarksText, 'insufficient balance') !== false || stripos($allRemarksText, 'recharge your wallet') !== false) {
                throw new RuntimeException(
                    "Insufficient Delhivery Wallet Balance. Your Delhivery account is on Prepaid/Wallet billing and requires funds to cover shipment manifestation charges. Please log into one.delhivery.com (Finances > Wallet), recharge your wallet, and try booking again."
                );
            }

            // Extract inner message if wrapped in exception '...'
            $errorMsg = null;
            if (!empty($rawRemarks[0])) {
                if (preg_match("/exception '([^']+)'/i", $rawRemarks[0], $match)) {
                    $errorMsg = $match[1];
                } else {
                    $errorMsg = $rawRemarks[0];
                }
            }

            if (!$errorMsg && !empty($res['rmk'])) {
                $errorMsg = (string) $res['rmk'];
            }

            $errorMsg = $errorMsg ?: 'Delhivery did not return a valid waybill number.';
            throw new RuntimeException("Delhivery shipment booking failed: {$errorMsg}");
        }

        $package = $packages[0];
        $awb = (string) $package['waybill'];
        $courierName = 'Delhivery ' . $shippingMode;

        return [
            'awb' => $awb,
            'order_id' => $shipment->reference,
            'status' => $package['status'] ?? 'Success',
            'courier_name' => $courierName,
            'tracking_url' => "https://www.delhivery.com/track/package/{$awb}",
        ];
    }

    /**
     * Schedule courier agent pickup request with Delhivery.
     *
     * @param string|null $pickupDate 'YYYY-MM-DD' (defaults to today or tomorrow if after 2 PM)
     * @param string|null $pickupTime 'HH:MM:SS' (defaults to '14:00:00')
     * @param int $packageCount
     * @param string|null $pickupLocationName
     * @return array{
     *     success: bool,
     *     pickup_id: string|int|null,
     *     message: string,
     *     pickup_date: string,
     *     pickup_time: string
     * }
     */
    public function schedulePickup(
        ?string $pickupDate = null,
        ?string $pickupTime = null,
        int $packageCount = 1,
        ?string $pickupLocationName = null
    ): array {
        $now = now();
        $date = $pickupDate ?? ($now->hour >= 14 ? $now->copy()->addDay()->format('Y-m-d') : $now->format('Y-m-d'));
        $time = $pickupTime ?? '14:00:00';
        $location = $pickupLocationName ?: $this->pickupLocation;

        if ($this->isMock()) {
            $mockPickupId = 'PR_' . random_int(100000, 999999);
            return [
                'success' => true,
                'pickup_id' => $mockPickupId,
                'message' => 'Delhivery pickup scheduled successfully (mock)',
                'pickup_date' => $date,
                'pickup_time' => $time,
            ];
        }

        $payload = [
            'pickup_location' => $location,
            'pickup_date' => $date,
            'pickup_time' => $time,
            'expected_package_count' => max(1, $packageCount),
        ];

        Log::info('Delhivery schedule pickup request', $payload);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(20)
            ->post($this->apiBaseUrl . '/fm/request/new/', $payload);

            if ($response->failed()) {
                Log::error('Delhivery pickup schedule failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'pickup_id' => null,
                    'message' => 'Delhivery pickup request failed: ' . ($response->json('message') ?? $response->body()),
                    'pickup_date' => $date,
                    'pickup_time' => $time,
                ];
            }

            $res = $response->json();
            $prId = $res['pr_id'] ?? $res['pickup_id'] ?? $res['data']['pr_id'] ?? null;
            $msg = $res['message'] ?? $res['status'] ?? 'Pickup scheduled successfully';

            return [
                'success' => true,
                'pickup_id' => $prId,
                'message' => (string) $msg,
                'pickup_date' => $date,
                'pickup_time' => $time,
            ];
        } catch (\Throwable $e) {
            Log::error('Delhivery pickup exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'pickup_id' => null,
                'message' => 'Delhivery pickup error: ' . $e->getMessage(),
                'pickup_date' => $date,
                'pickup_time' => $time,
            ];
        }
    }

    /**
     * Download or retrieve printable PDF shipping label from Delhivery.
     *
     * @return array{pdf: string|null, url: string|null}
     */
    public function getShippingLabel(string $waybill): array
    {
        if ($this->isMock()) {
            return [
                'pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF",
                'url' => "https://example.com/mock-labels/{$waybill}.pdf",
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiToken,
                'Accept' => 'application/json, application/pdf',
            ])
            ->timeout(20)
            ->get($this->apiBaseUrl . '/api/p/packing_slip', [
                'wbns' => trim($waybill),
                'pdf' => 'true',
            ]);

            if (!$response->successful()) {
                Log::warning('Delhivery shipping label fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['pdf' => null, 'url' => null];
            }

            $body = $response->body();

            // 1. Direct binary PDF
            if (str_starts_with($body, '%PDF')) {
                return ['pdf' => $body, 'url' => null];
            }

            // 2. Delhivery returns JSON with signed S3 download link in `packages[0]['pdf_download_link']`
            $data = $response->json();
            $s3Url = $data['packages'][0]['pdf_download_link'] ?? null;

            if ($s3Url) {
                // Fetch binary PDF from S3 so we can stream it directly with proper headers
                $pdfResponse = Http::timeout(25)->get($s3Url);
                if ($pdfResponse->successful() && str_starts_with($pdfResponse->body(), '%PDF')) {
                    return ['pdf' => $pdfResponse->body(), 'url' => $s3Url];
                }

                // If S3 fetch fails, return the S3 URL as a redirect fallback
                return ['pdf' => null, 'url' => $s3Url];
            }

            Log::warning('Delhivery shipping label response missing pdf_download_link', ['response' => $data]);
            return ['pdf' => null, 'url' => null];
        } catch (\Throwable $e) {
            Log::error('Delhivery shipping label exception', ['error' => $e->getMessage()]);
            return ['pdf' => null, 'url' => null];
        }
    }

    /**
     * Backward-compatible helper to get raw PDF bytes.
     */
    public function getShippingLabelPdf(string $waybill): ?string
    {
        $label = $this->getShippingLabel($waybill);
        return $label['pdf'];
    }

    /**
     * Track package live with Delhivery.
     */
    public function trackPackage(string $waybill): array
    {
        if ($this->isMock()) {
            return [
                'ShipmentData' => [
                    [
                        'Shipment' => [
                            'AWB' => trim($waybill),
                            'Status' => [
                                'Status' => 'In Transit',
                                'StatusType' => 'UD',
                                'Instructions' => 'In Transit to destination hub',
                                'StatusDateTime' => now()->toISOString(),
                                'StatusLocation' => 'Jaipur Hub',
                                'StatusCode' => 'X-IT',
                            ],
                            'Scans' => [
                                [
                                    'ScanDetail' => [
                                        'Instructions' => 'Manifested and Dispatched',
                                        'Scan' => 'In Transit',
                                        'ScanDateTime' => now()->subDay()->toISOString(),
                                        'ScanType' => 'UD',
                                        'ScannedLocation' => 'Jaipur Hub',
                                        'StatusCode' => 'X-MF',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiToken,
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->get($this->apiBaseUrl . '/api/v1/packages/json/', [
                'waybill' => trim($waybill),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
