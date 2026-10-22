<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GstSupplyType;
use App\Enums\OrderAddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Support\Commerce\OrderNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use DatabaseTransactions;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Smart Gadgets',
            'slug' => 'smart-gadgets-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Fast Wireless Charger',
            'slug' => 'fast-wireless-charger-' . uniqid(),
            'brand' => 'Dhansree Smart',
            'status' => 'ACTIVE',
            'published_at' => now(),
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CHG-' . strtoupper(uniqid()),
            'price_paise' => 149900,
            'mrp_paise' => 199900,
            'cost_paise' => 80000,
            'option_signature' => 'Black / Standard',
            'weight_grams' => 200,
            'position' => 1,
        ]);
    }

    private function createOrderForCustomer(string $phone = '+919876543210', OrderStatus $status = OrderStatus::CONFIRMED): Order
    {
        $orderNum = OrderNumber::generate();

        $order = Order::create([
            'order_number' => $orderNum,
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'status' => $status,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::ONLINE,
            'customer_name' => 'Pooja Verma',
            'customer_email' => 'pooja.verma@example.com',
            'customer_phone' => $phone,
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => \App\Enums\GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 149900,
            'discount_paise' => 0,
            'shipping_paise' => 0,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 127034,
            'cgst_paise' => 11433,
            'sgst_paise' => 11433,
            'igst_paise' => 0,
            'tax_paise' => 22866,
            'total_paise' => 149900,
            'payment_due_at' => now()->addMinutes(30),
            'placed_at' => now(),
            'updated_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'product_name' => 'Fast Wireless Charger',
            'variant_title' => 'Black / Standard',
            'option_values' => ['Color' => 'Black'],
            'sku' => $this->variant->sku,
            'hsn_code' => '850440',
            'quantity' => 1,
            'unit_price_paise' => 149900,
            'mrp_paise' => 199900,
            'discount_paise' => 0,
            'gst_rate_bps' => 1800,
            'taxable_paise' => 127034,
            'cgst_paise' => 11433,
            'sgst_paise' => 11433,
            'igst_paise' => 0,
            'tax_paise' => 22866,
            'line_total_paise' => 149900,
            'position' => 1,
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => OrderAddressType::SHIPPING,
            'recipient_name' => 'Pooja Verma',
            'phone' => $phone,
            'line1' => 'Flat 402, Royal Residency',
            'city' => 'Jaipur',
            'state_code' => '08',
            'state_name' => 'Rajasthan',
            'postal_code' => '302020',
            'country' => 'IN',
            'created_at' => now(),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider_reference' => 'PAY-' . strtoupper(uniqid()),
            'provider_payment_id' => 'pay_' . strtoupper(uniqid()),
            'status' => PaymentStatus::PAID,
            'method' => PaymentMethod::ONLINE,
            'provider' => 'razorpay',
            'amount_paise' => $order->total_paise,
            'currency' => 'INR',
            'confirmed_via' => \App\Enums\PaymentConfirmationSource::CHECKOUT,
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        return $order;
    }

    public function test_tracking_lookup_screen_renders_successfully(): void
    {
        $response = $this->get('/track-order');
        $response->assertStatus(200);
        $response->assertSee('Track Your Order');
        $response->assertSee('Registered Mobile Number');
        $response->assertSee('Track Shipment');
    }

    public function test_tracking_lookup_fails_when_order_number_not_found(): void
    {
        $response = $this->post('/track-order', [
            'order_number' => 'DS-99999999',
            'phone' => '9876543210',
        ]);

        $response->assertSessionHasErrors('order_number');
    }

    public function test_tracking_lookup_fails_when_phone_does_not_match_order(): void
    {
        $order = $this->createOrderForCustomer(phone: '+919876543210');

        $response = $this->post('/track-order', [
            'order_number' => $order->order_number,
            'phone' => '9111111111',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_tracking_lookup_succeeds_with_correct_credentials_and_authorizes_session(): void
    {
        $order = $this->createOrderForCustomer(phone: '+919876543210');

        $response = $this->post('/track-order', [
            'order_number' => $order->order_number,
            'phone' => '9876543210',
        ]);

        $response->assertRedirect(route('store.track-order.show', ['orderNumber' => $order->order_number]));
        $this->assertTrue(session()->get('tracked_order_' . $order->order_number, false));
    }

    public function test_order_tracking_detail_displays_full_stepper_and_shipment_details(): void
    {
        $order = $this->createOrderForCustomer();

        // Create shipment for order
        $now = now();
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'active_order_id' => $order->id,
            'provider' => 'shiprocket',
            'reference' => $order->order_number . '-S1',
            'status' => ShipmentStatus::AWB_ASSIGNED,
            'provider_shipment_id' => 'SR-SHIP-1234',
            'awb' => 'AWB987654321',
            'courier_name' => 'Bluedart Express',
            'tracking_url' => 'https://shiprocket.co/tracking/AWB987654321',
            'payment_mode' => \App\Enums\ShipmentPaymentMode::PREPAID,
            'declared_value_paise' => $order->total_paise,
            'weight_grams' => 300,
            'length_cm' => 20,
            'breadth_cm' => 15,
            'height_cm' => 5,
            'location_id' => 1,
            'pickup_location' => 'Jaipur',
            'destination_postal_code' => '302020',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->withSession(['tracked_order_' . $order->order_number => true])
            ->get("/order/{$order->order_number}/track");

        $response->assertStatus(200);
        $response->assertSee($order->order_number);
        $response->assertSee('Bluedart Express');
        $response->assertSee('AWB987654321');
        $response->assertSee('Fast Wireless Charger');
        $response->assertSee('Royal Residency');
        $response->assertSee('Order Placed');
        $response->assertSee('Packed & Courier Assigned');
    }

    public function test_shiprocket_webhook_rejects_unauthorized_token(): void
    {
        config(['store.shiprocket.webhook_token' => 'secret_token_123']);

        $response = $this->postJson('/webhooks/shiprocket', [
            'awb' => 'AWB987654321',
            'current_status' => 'DELIVERED',
        ], [
            'x-api-key' => 'wrong_token',
        ]);

        $response->assertStatus(401);
    }

    public function test_shiprocket_webhook_updates_shipment_and_delivers_order(): void
    {
        config(['store.shiprocket.webhook_token' => 'secret_token_123']);

        $order = $this->createOrderForCustomer(status: OrderStatus::SHIPPED);

        $now = now();
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'active_order_id' => $order->id,
            'provider' => 'shiprocket',
            'reference' => $order->order_number . '-S1',
            'status' => ShipmentStatus::SHIPPED,
            'provider_shipment_id' => 'SR-SHIP-999',
            'awb' => 'AWB-LIVE-123',
            'courier_name' => 'Delhivery Surface',
            'tracking_url' => 'https://shiprocket.co/tracking/AWB-LIVE-123',
            'payment_mode' => \App\Enums\ShipmentPaymentMode::PREPAID,
            'declared_value_paise' => $order->total_paise,
            'weight_grams' => 300,
            'length_cm' => 20,
            'breadth_cm' => 15,
            'height_cm' => 5,
            'location_id' => 1,
            'pickup_location' => 'Jaipur',
            'destination_postal_code' => '302020',
            'shipped_at' => $now,
            'stock_deducted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->postJson('/webhooks/shiprocket', [
            'awb' => 'AWB-LIVE-123',
            'current_status' => 'DELIVERED',
        ], [
            'x-api-key' => 'secret_token_123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'action' => 'marked_delivered']);

        $order->refresh();
        $shipment->refresh();

        $this->assertEquals(OrderStatus::DELIVERED, $order->status);
        $this->assertEquals(ShipmentStatus::DELIVERED, $shipment->status);
        $this->assertNotNull($shipment->delivered_at);
    }
}
