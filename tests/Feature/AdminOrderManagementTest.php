<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\GstSupplyType;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderAddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationSource;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Support\Auth\AdminSessionManager;
use App\Support\Commerce\OrderNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $rawAdminToken;
    private ProductVariant $variant;
    private InventoryLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Admin with Super Admin role
        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'order_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Order Manager',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawAdminToken = $sessionData['token'];

        // 2. Setup Category, Product & Variant
        $category = Category::create([
            'name' => 'Ethnic Kurties',
            'slug' => 'ethnic-kurties-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Anarkali Kurti Set',
            'slug' => 'anarkali-kurti-' . uniqid(),
            'brand' => 'Dhansree',
            'status' => 'ACTIVE',
            'published_at' => now(),
        ]);



        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KURTI-' . strtoupper(uniqid()),
            'price_paise' => 29900,
            'mrp_paise' => 39900,
            'cost_paise' => 15000,
            'option_signature' => 'Maroon / M',
            'weight_grams' => 350,
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




        InventoryLevel::updateOrCreate(
            ['variant_id' => $this->variant->id, 'location_id' => $this->location->id],
            ['on_hand' => 50, 'reserved' => 5]
        );
    }

    private function createSampleOrder(array $overrides = []): Order
    {
        $orderNum = OrderNumber::generate();
        $isOnline = ($overrides['payment_method'] ?? 'COD') === 'ONLINE';

        $defaults = [
            'order_number' => $orderNum,
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'status' => $isOnline ? OrderStatus::PENDING_PAYMENT : OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => $isOnline ? PaymentMethod::ONLINE : PaymentMethod::COD,
            'customer_name' => 'Riya Sharma',
            'customer_email' => 'riya.sharma@example.com',
            'customer_phone' => '+919876543210',
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

        $order = Order::create(array_merge($defaults, $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'product_name' => 'Anarkali Kurti Set',
            'variant_title' => 'Maroon / M',
            'option_values' => ['Size' => 'M', 'Color' => 'Maroon'],
            'sku' => $this->variant->sku,
            'hsn_code' => '6204',
            'quantity' => 1,
            'unit_price_paise' => 29900,
            'mrp_paise' => 39900,
            'discount_paise' => 0,
            'gst_rate_bps' => 1800,
            'taxable_paise' => 25339,
            'cgst_paise' => 2280,
            'sgst_paise' => 2281,
            'igst_paise' => 0,
            'tax_paise' => 4561,
            'line_total_paise' => 29900,
            'position' => 1,
        ]);




        OrderAddress::create([
            'order_id' => $order->id,
            'type' => OrderAddressType::SHIPPING,
            'recipient_name' => 'Riya Sharma',
            'phone' => '+919876543210',
            'line1' => 'B-45, Malviya Nagar',
            'city' => 'Jaipur',
            'state_code' => '08',
            'state_name' => 'Rajasthan',
            'postal_code' => '302017',
            'country' => 'IN',
            'created_at' => now(),
        ]);


        $isCod = $order->payment_method === PaymentMethod::COD;
        Payment::create([
            'order_id' => $order->id,
            'provider_reference' => $isCod ? null : ('PAY-' . strtoupper(uniqid())),
            'status' => PaymentStatus::PENDING,
            'method' => $order->payment_method,
            'provider' => $isCod ? 'cod' : 'razorpay',
            'amount_paise' => $order->total_paise,
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

    public function test_guest_is_redirected_to_admin_login_when_accessing_orders(): void
    {
        $response = $this->get('/admin/orders');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_orders_index_with_status_filtering(): void
    {
        $order1 = $this->createSampleOrder(['status' => OrderStatus::CONFIRMED]);
        $order2 = $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);

        $response = $this->asAdmin()->get('/admin/orders');
        $response->assertStatus(200);
        $response->assertSee($order1->order_number);
        $response->assertSee($order2->order_number);

        // Filter by CONFIRMED
        $filterRes = $this->asAdmin()->get('/admin/orders?status=CONFIRMED');
        $filterRes->assertStatus(200);
        $filterRes->assertSee($order1->order_number);
    }

    public function test_admin_can_search_orders_by_customer_name_or_order_number(): void
    {
        $order = $this->createSampleOrder(['customer_name' => 'Aarav Singhania']);

        $res = $this->asAdmin()->get('/admin/orders?search=Singhania');
        $res->assertStatus(200);
        $res->assertSee($order->order_number);
        $res->assertSee('Aarav Singhania');
    }

    public function test_admin_can_view_order_details(): void
    {
        $order = $this->createSampleOrder();

        $res = $this->asAdmin()->get("/admin/orders/{$order->id}");
        $res->assertStatus(200);
        $res->assertSee($order->order_number);
        $res->assertSee('Anarkali Kurti Set');
        $res->assertSee('Riya Sharma');
        $res->assertSee('Malviya Nagar');
    }

    public function test_admin_can_add_staff_internal_note(): void
    {
        $order = $this->createSampleOrder();

        $res = $this->asAdmin()->post("/admin/orders/{$order->id}/notes", [
            'body' => 'Customer confirmed delivery address over phone.',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('order_notes', [
            'order_id' => $order->id,
            'body' => 'Customer confirmed delivery address over phone.',
            'author_admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_update_order_status_with_audit_history(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::CONFIRMED]);

        $res = $this->asAdmin()->post("/admin/orders/{$order->id}/status", [
            'status' => 'PROCESSING',
            'note' => 'Packing initiated at warehouse.',
        ]);

        $res->assertRedirect();
        $order->refresh();
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::CONFIRMED->value,
            'to_status' => OrderStatus::PROCESSING->value,
            'actor_type' => 'ADMIN',
            'actor_admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_book_courier_shipment_via_shiprocket(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::CONFIRMED]);

        $res = $this->asAdmin()->post("/admin/orders/{$order->id}/shipment", [
            'courier_id' => 1,
        ]);

        $res->assertRedirect();
        $order->refresh();

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'active_order_id' => $order->id,
            'status' => ShipmentStatus::AWB_ASSIGNED->value,
        ]);

        $shipment = $order->shipments()->where('active_order_id', $order->id)->first();
        $this->assertNotNull($shipment);
        $this->assertNotEmpty($shipment->awb);
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);
    }

    public function test_admin_can_dispatch_parcel_and_deduct_inventory(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::CONFIRMED]);

        // Create active inventory reservation
        InventoryReservation::create([
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity' => 1,
            'status' => InventoryReservationStatus::ACTIVE,
            'source_type' => 'ORDER',
            'source_id' => (string) $order->id,
            'created_at' => now(),
        ]);


        // 1. Book shipment
        $this->asAdmin()->post("/admin/orders/{$order->id}/shipment");
        $shipment = $order->shipments()->where('active_order_id', $order->id)->first();

        // 2. Dispatch parcel
        $dispatchRes = $this->asAdmin()->post("/admin/orders/{$order->id}/shipments/{$shipment->id}/dispatch");
        $dispatchRes->assertRedirect();

        $order->refresh();
        $shipment->refresh();

        $this->assertEquals(OrderStatus::SHIPPED, $order->status);
        $this->assertEquals(ShipmentStatus::SHIPPED, $shipment->status);
        $this->assertNotNull($shipment->stock_deducted_at);

        // Verify inventory movement recorded
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $this->variant->id,
            'type' => 'DEDUCTION',
            'reference' => $shipment->reference,
        ]);
    }

    public function test_admin_can_mark_order_as_delivered_and_reconcile_cod_payment(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::SHIPPED, 'payment_method' => 'COD']);

        $res = $this->asAdmin()->post("/admin/orders/{$order->id}/status", [
            'status' => 'DELIVERED',
        ]);

        $res->assertRedirect();
        $order->refresh();

        $this->assertEquals(OrderStatus::DELIVERED, $order->status);
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID->value,
            'confirmed_via' => PaymentConfirmationSource::RECONCILIATION->value,
        ]);
    }

    public function test_admin_can_cancel_order_and_release_reservations(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::CONFIRMED]);

        $reservation = InventoryReservation::create([
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity' => 1,
            'status' => InventoryReservationStatus::ACTIVE,
            'source_type' => 'ORDER',
            'source_id' => (string) $order->id,
            'created_at' => now(),
        ]);


        $res = $this->asAdmin()->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Customer requested change of shipping address',
        ]);

        $res->assertRedirect();
        $order->refresh();
        $reservation->refresh();

        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertEquals(InventoryReservationStatus::RELEASED, $reservation->status);
    }

    public function test_admin_can_generate_and_view_gst_tax_invoice(): void
    {
        $order = $this->createSampleOrder();

        $res = $this->asAdmin()->get("/admin/orders/{$order->id}/invoice");
        $res->assertStatus(200);
        $res->assertSee('TAX INVOICE');
        $res->assertSee($order->order_number);
        $res->assertSee('Anarkali Kurti Set');
        $res->assertSee('Indian Rupees');

        $order->refresh();
        $this->assertNotNull($order->invoice_number);
        $this->assertNotNull($order->invoice_issued_at);
    }
}
