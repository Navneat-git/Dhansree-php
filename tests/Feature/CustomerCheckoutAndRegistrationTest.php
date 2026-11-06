<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BackorderPolicy;
use App\Enums\CustomerStatus;
use App\Enums\GstRegistrationType;
use App\Enums\GstSupplyType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Services\Storefront\CartService;
use App\Support\Commerce\OrderNumber;
use Tests\TestCase;

class CustomerCheckoutAndRegistrationTest extends TestCase
{
    private function createTestProductAndVariant(): array
    {
        $category = Category::first() ?? Category::create([
            'name' => 'Festive Wear',
            'slug' => 'festive-wear-' . uniqid(),
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Royal Festive Kurti',
            'slug' => 'royal-festive-kurti-' . uniqid(),
            'brand' => 'Dhansree',
            'status' => 'ACTIVE',
            'published_at' => now(),
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-' . strtoupper(uniqid()),
            'price_paise' => 49900,
            'mrp_paise' => 99900,
            'cost_paise' => 25000,
            'option_signature' => 'Gold / M',
            'position' => 1,
        ]);

        InventoryItem::create([
            'variant_id' => $variant->id,
            'backorder_policy' => BackorderPolicy::STOP_SALE,
            'low_stock_threshold' => 5,
        ]);

        $location = InventoryLocation::first() ?? InventoryLocation::create([
            'code' => 'JAIPUR-WH',
            'name' => 'Jaipur Main Warehouse',
            'is_active' => true,
        ]);

        InventoryLevel::updateOrCreate(
            ['variant_id' => $variant->id, 'location_id' => $location->id],
            ['on_hand' => 50, 'reserved' => 0]
        );

        return [$product, $variant];
    }

    private function createSampleOrder(array $overrides = []): Order
    {
        $orderNum = OrderNumber::generate();
        $randomPhone = '9' . str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $defaults = [
            'order_number' => $orderNum,
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::COD,
            'customer_name' => 'Rohit Verma',
            'customer_email' => 'rohit.verma.' . uniqid() . '@example.com',
            'customer_phone' => '+91' . $randomPhone,
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 49900,
            'discount_paise' => 0,
            'shipping_paise' => 0,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 44554,
            'cgst_paise' => 2673,
            'sgst_paise' => 2673,
            'igst_paise' => 0,
            'tax_paise' => 5346,
            'total_paise' => 49900,
            'placed_at' => now(),
        ];

        return Order::create(array_merge($defaults, $overrides));
    }

    public function test_guest_checkout_creates_customer_account_and_links_order(): void
    {
        [$product, $variant] = $this->createTestProductAndVariant();
        $testEmail = 'aditi.sharma.' . uniqid() . '@example.com';
        $testPhone = '9' . str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        $payload = [
            'name' => 'Aditi Sharma',
            'email' => $testEmail,
            'phone' => $testPhone,
            'line1' => 'Flat 204, Lotus Heights',
            'line2' => 'Civil Lines',
            'landmark' => 'Near Metro',
            'city' => 'Jaipur',
            'state_code' => '08',
            'postal_code' => '302006',
            'payment_method' => 'COD',
        ];

        // Put item in session cart using CartService format
        $this->withSession([
            CartService::SESSION_KEY => [
                $variant->id => ['quantity' => 1],
            ],
        ]);

        $response = $this->postJson('/checkout/place', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Customer was created in database
        $customer = Customer::where('email', $testEmail)->first();
        $this->assertNotNull($customer, 'Customer record was not created on guest checkout');
        $this->assertEquals('Aditi Sharma', $customer->name);
        $this->assertEquals('+91' . $testPhone, $customer->phone);
        $this->assertEquals(CustomerStatus::ACTIVE, $customer->status);

        // Verify Order is linked to customer
        $order = Order::where('customer_email', $testEmail)->first();
        $this->assertNotNull($order);
        $this->assertEquals($customer->id, $order->customer_id);

        // Verify CustomerAddress was created
        $this->assertGreaterThanOrEqual(1, $customer->addresses()->count());
        $address = $customer->addresses()->first();
        $this->assertEquals('Flat 204, Lotus Heights', $address->line1);
        $this->assertEquals('Jaipur', $address->city);
        $this->assertEquals('08', $address->state_code);
    }

    public function test_customer_registration_notifies_when_account_exists_from_order(): void
    {
        $testEmail = 'rohit.verma.' . uniqid() . '@example.com';
        $testPhone = '9' . str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        // 1. Create a customer with past orders (as created from checkout)
        $customer = Customer::create([
            'name' => 'Rohit Verma',
            'email' => $testEmail,
            'phone' => '+91' . $testPhone,
            'status' => CustomerStatus::ACTIVE,
        ]);

        $this->createSampleOrder([
            'customer_id' => $customer->id,
            'customer_name' => 'Rohit Verma',
            'customer_email' => $testEmail,
            'customer_phone' => '+91' . $testPhone,
        ]);

        // 2. Customer visits registration and submits same email
        $response = $this->post('/account/register', [
            'name' => 'Rohit Verma',
            'email' => $testEmail,
            'phone' => $testPhone,
        ]);

        // Should redirect to verify page with notice that account is already available from previous order
        $response->assertRedirect();
        $response->assertSessionHas('notice', function ($notice) {
            return str_contains($notice, 'already available from your previous order');
        });
    }

    public function test_new_customer_registration_creates_account_and_sends_verification(): void
    {
        $testEmail = 'neha.gupta.' . uniqid() . '@example.com';
        $testPhone = '9' . str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        $response = $this->post('/account/register', [
            'name' => 'Neha Gupta',
            'email' => $testEmail,
            'phone' => $testPhone,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('notice', function ($notice) {
            return str_contains($notice, 'Welcome to Dhansree');
        });

        $customer = Customer::where('email', $testEmail)->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Neha Gupta', $customer->name);
        $this->assertEquals('+91' . $testPhone, $customer->phone);
    }

    public function test_backfill_command_links_guest_orders(): void
    {
        $testEmail = 'balendu.guest.' . uniqid() . '@example.com';
        $testPhone = '9' . str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        $order = $this->createSampleOrder([
            'customer_id' => null,
            'customer_name' => 'Balendu Guest',
            'customer_email' => $testEmail,
            'customer_phone' => '+91' . $testPhone,
        ]);

        $this->artisan('customers:backfill-from-orders')
            ->assertSuccessful();

        $order->refresh();
        $this->assertNotNull($order->customer_id);

        $customer = Customer::find($order->customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals($testEmail, $customer->email);
        $this->assertEquals('+91' . $testPhone, $customer->phone);
    }

    public function test_checkout_page_renders_google_and_facebook_login_options(): void
    {
        [$product, $variant] = $this->createTestProductAndVariant();

        $this->withSession([
            CartService::SESSION_KEY => [
                $variant->id => ['quantity' => 1],
            ],
        ]);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
        $response->assertSee('Google');
        $response->assertSee('Facebook');
        $response->assertSee('Already have an account? Sign in for faster checkout');
    }
}
