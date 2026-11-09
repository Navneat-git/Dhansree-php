<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Enums\GstSupplyType;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Support\Razorpay\Signature;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = \App\Models\StoreSettings::current();
        if ($settings) {
            $settings->update([
                'free_shipping_threshold_paise' => null,
                'shipping_flat_rate_paise' => 5000,
            ]);
        }

        // Ensure we have an active variant with available inventory
        $this->variant = ProductVariant::whereNull('archived_at')
            ->whereHas('product', fn($q) => $q->where('status', 'ACTIVE'))
            ->whereHas('inventoryLevels', fn($q) => $q->where('on_hand', '>', 5))
            ->firstOrFail();
    }

    protected function createTestOrder(array $overrides = []): Order
    {
        $orderNumber = \App\Support\Commerce\OrderNumber::generate();
        $isOnline = ($overrides['payment_method'] ?? PaymentMethod::ONLINE) === PaymentMethod::ONLINE;

        $defaults = [
            'order_number' => $orderNumber,
            'checkout_key_hash' => hash('sha256', uniqid('order_key_', true)),
            'request_hash' => hash('sha256', uniqid('request_key_', true)),
            'status' => $isOnline ? OrderStatus::PENDING_PAYMENT : OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => $isOnline ? PaymentMethod::ONLINE : PaymentMethod::COD,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '+919899887766',
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => \App\Enums\GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 29900,
            'discount_paise' => 0,
            'shipping_paise' => 5000,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 4237,
            'shipping_tax_paise' => 763,
            'taxable_paise' => 29576,
            'cgst_paise' => 2662,
            'sgst_paise' => 2662,
            'igst_paise' => 0,
            'tax_paise' => 5324,
            'total_paise' => 34900,
            'placed_at' => now(),
            'updated_at' => now(),
            'payment_due_at' => $isOnline ? now()->addMinutes(30) : null,
        ];

        return Order::create(array_merge($defaults, $overrides));
    }

    public function test_empty_cart_redirects_to_cart_page(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/cart');
        $response->assertSessionHas('error');
    }

    public function test_checkout_screen_renders_when_cart_has_items(): void
    {
        config([
            'store.razorpay_enabled' => true,
            'store.razorpay.key_id' => 'rzp_test_sample',
            'store.razorpay.key_secret' => 'rzp_sec_sample',
        ]);

        $response = $this->withSession([
            'dhansree_cart' => [$this->variant->id => 1],
        ])->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee('Express Checkout');
        $response->assertSee('Delivery Address');
        $response->assertSee('Payment Method');
        $response->assertSee('Cash on Delivery (COD)');
        $response->assertSee('Online Payment (UPI, Cards, NetBanking)');
        $response->assertSee('item.variant.product.name');
    }

    public function test_checkout_quote_calculation_with_tax_and_shipping(): void
    {
        $expectedShipping = (int) \App\Models\StoreSettings::current()->shipping_flat_rate_paise;

        $response = $this->withSession([
            'dhansree_cart' => [$this->variant->id => 1],
        ])->postJson('/checkout/quote', [
            'state_code' => '08', // Intra-state (Rajasthan)
            'postal_code' => '302001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'quote' => [
                'item_count' => 1,
                'subtotal_paise' => $this->variant->price_paise,
                'shipping_paise' => $expectedShipping,
                'is_free_shipping' => false,
                'supply_type' => GstSupplyType::INTRA_STATE->value,
            ],
        ]);

        $data = $response->json('quote');
        $this->assertGreaterThan(0, $data['cgst_paise']);
        $this->assertGreaterThan(0, $data['sgst_paise']);
        $this->assertEquals(0, $data['igst_paise']);
    }

    public function test_checkout_quote_with_inter_state_igst(): void
    {
        // Inter-state to Maharashtra (27)
        $response = $this->withSession([
            'dhansree_cart' => [$this->variant->id => 1],
        ])->postJson('/checkout/quote', [
            'state_code' => '27',
            'postal_code' => '400001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'quote' => [
                'supply_type' => GstSupplyType::INTER_STATE->value,
                'cgst_paise' => 0,
                'sgst_paise' => 0,
            ],
        ]);

        $data = $response->json('quote');
        $this->assertGreaterThan(0, $data['igst_paise']);
    }

    public function test_checkout_quote_with_valid_and_invalid_coupons(): void
    {
        // Create active coupon: 10% off (1000 basis points)
        $coupon = Coupon::create([
            'code' => 'TEST10',
            'type' => CouponType::PERCENTAGE,
            'value' => 1000, // 10%
            'is_active' => true,
            'usage_limit' => 50,
            'times_redeemed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionCart = [
            'dhansree_cart' => [$this->variant->id => 2],
        ];

        // Valid coupon
        $validResponse = $this->withSession($sessionCart)->postJson('/checkout/quote', [
            'state_code' => '08',
            'postal_code' => '302001',
            'coupon_code' => 'TEST10',
        ]);

        $validResponse->assertStatus(200);
        $validData = $validResponse->json('quote');
        $this->assertGreaterThan(0, $validData['discount_paise']);
        $this->assertEquals('TEST10', $validData['coupon']['code']);

        // Invalid coupon
        $invalidResponse = $this->withSession($sessionCart)->postJson('/checkout/quote', [
            'state_code' => '08',
            'postal_code' => '302001',
            'coupon_code' => 'EXPIRED99',
        ]);

        $invalidResponse->assertStatus(422);
        $invalidResponse->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('invalid', $invalidResponse->json('message'));
    }

    public function test_free_shipping_threshold_evaluated_after_discount(): void
    {
        $store = \App\Models\StoreSettings::current();
        $store->update([
            'free_shipping_threshold_paise' => 99900, // ₹999 threshold
            'shipping_flat_rate_paise' => 5000,       // ₹50 flat rate
        ]);

        // Create 25% coupon
        $coupon = Coupon::create([
            'code' => 'SPCL25',
            'type' => CouponType::PERCENTAGE,
            'value' => 2500, // 25%
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Variant price ₹1199 (119900 paise)
        $this->variant->update(['price_paise' => 119900]);

        // Case 1: Without coupon, gross is ₹1199 (>= ₹999), free shipping applies
        $resNoCoupon = $this->withSession(['dhansree_cart' => [$this->variant->id => 1]])
            ->postJson('/checkout/quote', [
                'state_code' => '08',
                'postal_code' => '302001',
                'coupon_code' => '',
            ]);

        $resNoCoupon->assertStatus(200);
        $quoteNoCoupon = $resNoCoupon->json('quote');
        $this->assertTrue($quoteNoCoupon['is_free_shipping']);
        $this->assertEquals(0, $quoteNoCoupon['shipping_paise']);
        $this->assertEquals(1199.00, $quoteNoCoupon['total_rupees']);

        // Case 2: With 25% coupon, net price is ₹899.25 (< ₹999), free shipping is NOT granted, flat shipping ₹50 applied
        $resWithCoupon = $this->withSession(['dhansree_cart' => [$this->variant->id => 1]])
            ->postJson('/checkout/quote', [
                'state_code' => '08',
                'postal_code' => '302001',
                'coupon_code' => 'SPCL25',
            ]);

        $resWithCoupon->assertStatus(200);
        $quoteWithCoupon = $resWithCoupon->json('quote');
        $this->assertFalse($quoteWithCoupon['is_free_shipping']);
        $this->assertEquals(5000, $quoteWithCoupon['shipping_paise']);
        $this->assertEquals(50.00, $quoteWithCoupon['shipping_rupees']);
        $this->assertEquals(299.75, $quoteWithCoupon['discount_rupees']);
        $this->assertEquals(949.25, $quoteWithCoupon['total_rupees']);
    }

    public function test_cod_order_placement_creates_order_items_and_inventory_reservation(): void
    {
        $sessionCart = [
            'dhansree_cart' => [$this->variant->id => 1],
        ];

        $payload = [
            'name' => 'Aditi Sharma',
            'email' => 'aditi.sharma@example.com',
            'phone' => '9876543210',
            'line1' => 'Flat 402, Royal Palms Residency',
            'line2' => 'Near Central Park',
            'landmark' => 'Opposite Metro Station',
            'city' => 'Jaipur',
            'state_code' => '08',
            'postal_code' => '302015',
            'payment_method' => 'COD',
            'customer_note' => 'Please ring the bell twice.',
        ];

        $response = $this->withSession($sessionCart)
            ->postJson('/checkout/place', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_online' => false,
        ]);

        $redirectUrl = $response->json('redirect_url');
        $this->assertStringContainsString('/order/DS-', $redirectUrl);
        $this->assertStringContainsString('/confirmed', $redirectUrl);

        // Verify order in database
        $order = Order::where('customer_email', 'aditi.sharma@example.com')->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertStringStartsWith('DS-', $order->order_number);
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        $this->assertEquals(PaymentMethod::COD, $order->payment_method);
        $this->assertEquals(PaymentStatus::PENDING, $order->payment_status);
        $this->assertEquals(1, $order->item_count);
        $this->assertEquals('Please ring the bell twice.', $order->customer_note);

        // Verify shipping address
        $address = $order->addresses()->where('type', 'SHIPPING')->first();
        $this->assertNotNull($address);
        $this->assertEquals('Aditi Sharma', $address->recipient_name);
        $this->assertEquals('302015', $address->postal_code);
        $this->assertEquals('Rajasthan', $address->state_name);

        // Verify order item
        $item = $order->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals($this->variant->id, $item->variant_id);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals($this->variant->price_paise, $item->unit_price_paise);

        // Verify inventory reservation
        $reservation = InventoryReservation::where('source_type', 'ORDER')
            ->where('source_id', (string) $order->id)
            ->where('variant_id', $this->variant->id)
            ->first();

        $this->assertNotNull($reservation);
        $this->assertEquals(InventoryReservationStatus::ACTIVE, $reservation->status);
        $this->assertEquals(1, $reservation->quantity);

        // Verify order status history
        $history = OrderStatusHistory::where('order_id', $order->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(OrderStatus::CONFIRMED, $history->to_status);
        $this->assertEquals(\App\Enums\OrderActorType::CUSTOMER, $history->actor_type);

        // Verify session cart cleared
        $this->assertEmpty(session('dhansree_cart'));
    }

    public function test_order_placement_records_coupon_redemption_and_increments_count(): void
    {
        $coupon = Coupon::create([
            'code' => 'WELCOME50',
            'type' => CouponType::FIXED_AMOUNT,
            'value' => 5000, // ₹50
            'is_active' => true,
            'usage_limit' => 10,
            'times_redeemed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionCart = [
            'dhansree_cart' => [$this->variant->id => 1],
        ];

        $payload = [
            'name' => 'Meera Patel',
            'email' => 'meera.patel@example.com',
            'phone' => '9812345678',
            'line1' => '12 MG Road',
            'city' => 'Jaipur',
            'state_code' => '08',
            'postal_code' => '302001',
            'payment_method' => 'COD',
            'coupon_code' => 'WELCOME50',
        ];

        $response = $this->withSession($sessionCart)
            ->postJson('/checkout/place', $payload);

        $response->assertStatus(200);

        $order = Order::where('customer_email', 'meera.patel@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals('WELCOME50', $order->coupon_code);
        $this->assertEquals(5000, $order->discount_paise);

        // Verify coupon redemption table
        $redemption = CouponRedemption::where('order_id', $order->id)->first();
        $this->assertNotNull($redemption);
        $this->assertEquals($coupon->id, $redemption->coupon_id);
        $this->assertEquals('meera.patel@example.com', $redemption->email);
        $this->assertEquals(5000, $redemption->discount_paise);

        // Verify coupon times_redeemed incremented
        $coupon->refresh();
        $this->assertEquals(1, $coupon->times_redeemed);
    }

    public function test_razorpay_order_placement_creates_pending_order(): void
    {
        $sessionCart = [
            'dhansree_cart' => [$this->variant->id => 1],
        ];

        $payload = [
            'name' => 'Rahul Verma',
            'email' => 'rahul.verma@example.com',
            'phone' => '9899887766',
            'line1' => 'Plot 55, Sector 12',
            'city' => 'Jaipur',
            'state_code' => '08',
            'postal_code' => '302020',
            'payment_method' => 'ONLINE',
        ];

        $response = $this->withSession($sessionCart)
            ->postJson('/checkout/place', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_online' => true,
        ]);

        $orderNumber = $response->json('order_number');
        $this->assertNotEmpty($orderNumber);

        $order = Order::where('order_number', $orderNumber)->first();
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::PENDING_PAYMENT, $order->status);
        $this->assertEquals(PaymentMethod::ONLINE, $order->payment_method);
        $this->assertEquals(PaymentStatus::PENDING, $order->payment_status);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentMethod::ONLINE, $payment->method);
        $this->assertEquals('razorpay', $payment->provider);
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
    }

    public function test_razorpay_signature_verification_confirms_order_and_payment(): void
    {
        config(['store.razorpay.key_secret' => 'test_razorpay_secret_12345']);

        $order = $this->createTestOrder();

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::ONLINE,
            'provider' => 'razorpay',
            'status' => PaymentStatus::PENDING,
            'amount_paise' => 34900,
            'currency' => 'INR',
            'provider_reference' => 'order_RzpTest12345',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $razorpayOrderId = 'order_RzpTest12345';
        $razorpayPaymentId = 'pay_RzpPay987654';
        $signature = Signature::checkout($razorpayOrderId, $razorpayPaymentId, 'test_razorpay_secret_12345');

        $response = $this->postJson('/checkout/razorpay/verify', [
            'order_number' => $order->order_number,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $order->refresh();
        $payment->refresh();

        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertEquals($razorpayPaymentId, $payment->provider_payment_id);
        $this->assertNotNull($payment->signature_verified_at);
        $this->assertNotNull($payment->paid_at);

        // Status history recorded
        $history = OrderStatusHistory::where('order_id', $order->id)
            ->where('to_status', OrderStatus::CONFIRMED)
            ->first();
        $this->assertNotNull($history);
    }

    public function test_razorpay_signature_verification_rejects_tampered_signature(): void
    {
        config(['store.razorpay.key_secret' => 'test_razorpay_secret_12345']);

        $order = $this->createTestOrder();

        $response = $this->postJson('/checkout/razorpay/verify', [
            'order_number' => $order->order_number,
            'razorpay_order_id' => 'order_FakeOrderId',
            'razorpay_payment_id' => 'pay_FakePaymentId',
            'razorpay_signature' => 'invalid_tampered_hex_signature',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $order->refresh();
        $this->assertEquals(OrderStatus::PENDING_PAYMENT, $order->status);
    }

    public function test_razorpay_webhook_captures_payment_and_updates_order(): void
    {
        $webhookSecret = 'webhook_secret_xyz123';
        config(['store.razorpay.webhook_secret' => $webhookSecret]);

        $order = $this->createTestOrder();

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::ONLINE,
            'provider' => 'razorpay',
            'status' => PaymentStatus::PENDING,
            'amount_paise' => 34900,
            'currency' => 'INR',
            'provider_reference' => 'order_webhook_rzp_99',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $webhookPayload = json_encode([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_webhook_captured_888',
                        'order_id' => 'order_webhook_rzp_99',
                        'amount' => 34900,
                        'currency' => 'INR',
                        'status' => 'captured',
                    ],
                ],
            ],
        ]);

        $signature = Signature::webhook($webhookPayload, $webhookSecret);

        $response = $this->call(
            'POST',
            '/webhooks/razorpay',
            [],
            [],
            [],
            [
                'HTTP_X-Razorpay-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $webhookPayload
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $order->refresh();
        $payment->refresh();

        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertEquals('pay_webhook_captured_888', $payment->provider_payment_id);
    }

    public function test_order_confirmed_page_renders_receipt(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => PaymentMethod::COD,
            'customer_name' => 'Kavita Joshi',
            'customer_email' => 'kavita@example.com',
            'customer_phone' => '+919822334455',
            'subtotal_paise' => 45000,
            'shipping_paise' => 0,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 42857,
            'cgst_paise' => 1072,
            'sgst_paise' => 1071,
            'igst_paise' => 0,
            'tax_paise' => 2143,
            'total_paise' => 45000,
        ]);

        $order->addresses()->create([
            'type' => 'SHIPPING',
            'recipient_name' => 'Kavita Joshi',
            'phone' => '+919822334455',
            'line1' => 'Plot 88, Civil Lines',
            'city' => 'Jaipur',
            'state_code' => '08',
            'state_name' => 'Rajasthan',
            'postal_code' => '302006',
            'country' => 'IN',
            'created_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'product_name' => $this->variant->product->name,
            'variant_title' => 'Standard',
            'option_values' => [],
            'sku' => $this->variant->sku,
            'hsn_code' => '6204',
            'quantity' => 1,
            'unit_price_paise' => 45000,
            'mrp_paise' => 55000,
            'discount_paise' => 0,
            'gst_rate_bps' => 500,
            'taxable_paise' => 42857,
            'cgst_paise' => 1072,
            'sgst_paise' => 1071,
            'igst_paise' => 0,
            'tax_paise' => 2143,
            'line_total_paise' => 45000,
            'position' => 0,
            'created_at' => now(),
        ]);

        $response = $this->get('/order/' . $order->order_number . '/confirmed');

        $response->assertStatus(200);
        $response->assertSee($order->order_number);
        $response->assertSee('Thank You for Your Order!');
        $response->assertSee('Kavita Joshi');
        $response->assertSee('Plot 88, Civil Lines');
        $response->assertSee('Jaipur');
        $response->assertSee('302006');
        $response->assertSee($this->variant->product->name);
        $response->assertSee('450');
    }

    public function test_dummy_placeholder_keys_are_detected_as_mock_mode(): void
    {
        config([
            'store.razorpay_enabled' => true,
            'store.razorpay.key_id' => 'rzp_test_E2EFakeKey0001',
            'store.razorpay.key_secret' => 'e2e-fake-key-secret-not-real',
        ]);

        $service = app(\App\Services\Payments\RazorpayService::class);
        $this->assertFalse($service->isRealKeyConfigured());
        $this->assertFalse($service->isEnabled());

        $mockOrder = $service->createOrder(5000, 'INR', 'receipt_123');
        $this->assertStringStartsWith('order_mock_', $mockOrder['id']);
        $this->assertEquals(5000, $mockOrder['amount']);
    }

    public function test_runtime_exception_during_order_placement_returns_descriptive_error_json(): void
    {
        $sessionCart = [
            'dhansree_cart' => [$this->variant->id => 1],
        ];

        // Mock RazorpayService to throw a RuntimeException simulating gateway error
        $mockRazorpay = \Mockery::mock(\App\Services\Payments\RazorpayService::class);
        $mockRazorpay->shouldReceive('isEnabled')->andReturn(true);
        $mockRazorpay->shouldReceive('getKeyId')->andReturn('rzp_test_real_key');
        $mockRazorpay->shouldReceive('createOrder')->andThrow(
            new \RuntimeException('Razorpay initialization failed: Authentication failed. Please verify your Razorpay API Key ID and Key Secret.')
        );
        $this->app->instance(\App\Services\Payments\RazorpayService::class, $mockRazorpay);

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '9899887766',
            'line1' => 'Plot 55, Sector 12',
            'city' => 'Jaipur',
            'state_code' => '08',
            'postal_code' => '302020',
            'payment_method' => 'ONLINE',
        ];

        $response = $this->withSession($sessionCart)
            ->postJson('/checkout/place', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Razorpay initialization failed: Authentication failed. Please verify your Razorpay API Key ID and Key Secret.',
        ]);
    }
}
