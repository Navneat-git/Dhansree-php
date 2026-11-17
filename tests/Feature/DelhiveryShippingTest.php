<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\InventoryLocation;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Services\Shipping\DelhiveryService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DelhiveryShippingTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $admin;
    protected string $rawAdminToken;
    protected Customer $customer;
    protected ProductVariant $variant;
    protected InventoryLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'delhivery_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Delhivery Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawAdminToken = $sessionData['token'];

        $category = \App\Models\Category::create([
            'name' => 'Ethnic Kurties',
            'slug' => 'ethnic-kurties-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Banarasi Pure Silk Saree',
            'slug' => 'banarasi-pure-silk-saree-' . uniqid(),
            'brand' => 'Dhansree',
            'status' => 'ACTIVE',
            'published_at' => now(),
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BANARASI-RED-' . uniqid(),
            'price_paise' => 249900,
            'mrp_paise' => 349900,
            'cost_paise' => 150000,
            'option_signature' => 'Red / Free Size',
            'weight_grams' => 600,
            'position' => 1,
        ]);

        \App\Models\InventoryItem::create([
            'variant_id' => $this->variant->id,
            'backorder_policy' => \App\Enums\BackorderPolicy::STOP_SALE,
            'low_stock_threshold' => 5,
        ]);

        $this->location = InventoryLocation::first() ?? InventoryLocation::create([
            'code' => 'JAIPUR-WH',
            'name' => 'Jaipur Main Warehouse',
            'is_active' => true,
        ]);

        \App\Models\InventoryLevel::updateOrCreate(
            ['variant_id' => $this->variant->id, 'location_id' => $this->location->id],
            ['on_hand' => 50, 'reserved' => 5]
        );
    }

    protected function createOrder(array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'order_number' => \App\Support\Commerce\OrderNumber::generate(),
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'customer_name' => 'Meera Sharma',
            'customer_email' => 'meera_' . uniqid() . '@example.com',
            'customer_phone' => '+919876543210',
            'currency' => 'INR',
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => \App\Enums\PaymentMethod::ONLINE,
            'payment_status' => \App\Enums\PaymentStatus::PAID,
            'payment_due_at' => now()->addMinutes(30),
            'prices_include_tax' => true,
            'gst_registration_type' => \App\Enums\GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => \App\Enums\GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 249900,
            'shipping_paise' => 0,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 211780,
            'cgst_paise' => 19060,
            'sgst_paise' => 19060,
            'igst_paise' => 0,
            'tax_paise' => 38120,
            'discount_paise' => 0,
            'total_paise' => 249900,
            'placed_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => \App\Enums\OrderAddressType::SHIPPING,
            'recipient_name' => $order->customer_name,
            'phone' => $order->customer_phone,
            'line1' => 'Flat 204, Royal Palms Apartment',
            'city' => 'Jaipur',
            'state_code' => '08',
            'state_name' => 'Rajasthan',
            'postal_code' => '302001',
            'country' => 'IN',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'product_name' => 'Banarasi Pure Silk Saree',
            'variant_title' => 'Red / Free Size',
            'option_values' => ['Color' => 'Red', 'Size' => 'Free Size'],
            'sku' => $this->variant->sku,
            'hsn_code' => '5007',
            'quantity' => 1,
            'unit_price_paise' => 249900,
            'mrp_paise' => 349900,
            'discount_paise' => 0,
            'gst_rate_bps' => 1800,
            'taxable_paise' => 211780,
            'cgst_paise' => 19060,
            'sgst_paise' => 19060,
            'igst_paise' => 0,
            'tax_paise' => 38120,
            'line_total_paise' => 249900,
            'position' => 1,
        ]);

        \App\Models\Payment::create([
            'order_id' => $order->id,
            'provider_reference' => 'PAY-' . strtoupper(uniqid()),
            'status' => \App\Enums\PaymentStatus::PENDING,
            'method' => $order->payment_method,
            'provider' => 'razorpay',
            'amount_paise' => 249900,
            'currency' => 'INR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $order;
    }

    private function asAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawAdminToken);
    }

    public function test_delhivery_service_enabled_and_pincode_check(): void
    {
        $service = app(DelhiveryService::class);
        $this->assertTrue($service->isEnabled());

        $pinCheck = $service->checkPincode('302001');
        $this->assertTrue($pinCheck['serviceable']);
        $this->assertTrue($pinCheck['prepaid']);
        $this->assertTrue($pinCheck['cod']);
    }

    public function test_delhivery_check_serviceability_returns_surface_and_express_rates(): void
    {
        $service = app(DelhiveryService::class);
        $couriers = $service->checkServiceability('302039', '302001', 500, false);

        $this->assertCount(2, $couriers);
        $courierIds = array_column($couriers, 'courier_id');
        $this->assertContains('delhivery_surface', $courierIds);
        $this->assertContains('delhivery_express', $courierIds);
    }

    public function test_admin_can_book_delhivery_shipment_and_generate_awb(): void
    {
        $order = $this->createOrder();

        $response = $this->asAdmin()->post("/admin/orders/{$order->id}/shipment", [
            'courier_id' => 'delhivery_surface',
            'provider' => 'delhivery',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'provider' => 'delhivery',
            'courier_name' => 'Delhivery Surface',
            'status' => ShipmentStatus::AWB_ASSIGNED->value,
        ]);

        $shipment = $order->shipments()->where('active_order_id', $order->id)->first();
        $this->assertNotNull($shipment);
        $this->assertStringStartsWith('DEL', (string) $shipment->awb);
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);
    }

    public function test_admin_can_schedule_delhivery_pickup_agent(): void
    {
        $order = $this->createOrder();

        $this->asAdmin()->post("/admin/orders/{$order->id}/shipment", [
            'courier_id' => 'delhivery_surface',
            'provider' => 'delhivery',
        ]);

        $shipment = $order->shipments()->where('active_order_id', $order->id)->firstOrFail();

        $response = $this->asAdmin()->post("/admin/orders/{$order->id}/shipments/{$shipment->id}/delhivery-pickup", [
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'pickup_time' => '14:00:00',
        ]);

        $response->assertRedirect();
        $shipment->refresh();

        $this->assertNotNull($shipment->pickup_scheduled_at);
        $this->assertStringContainsString('Pickup scheduled', (string) $shipment->provider_status);
    }

    public function test_admin_can_download_delhivery_shipping_label_pdf(): void
    {
        $order = $this->createOrder();

        $this->asAdmin()->post("/admin/orders/{$order->id}/shipment", [
            'courier_id' => 'delhivery_surface',
            'provider' => 'delhivery',
        ]);

        $shipment = $order->shipments()->where('active_order_id', $order->id)->firstOrFail();

        $response = $this->asAdmin()->get("/admin/orders/{$order->id}/shipments/{$shipment->id}/label");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
