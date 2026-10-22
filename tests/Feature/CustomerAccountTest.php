<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AddressLabel;
use App\Enums\CustomerStatus;
use App\Enums\GstRegistrationType;
use App\Enums\GstSupplyType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Auth\CustomerSessionManager;
use App\Support\Commerce\OrderNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use DatabaseTransactions;

    protected Customer $customer;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();

        $this->customer = Customer::create([
            'name' => 'Kavita Roy',
            'email' => 'kavita_' . uniqid() . '@example.com',
            'phone' => '+919876543210',
            'status' => CustomerStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $session = CustomerSessionManager::createSession($this->customer);
        $this->token = $session['token'];
    }

    public function test_unauthenticated_visitor_is_redirected_to_login(): void
    {
        $this->get('/account')->assertRedirect('/account/login?next=%2Faccount');
        $this->get('/account/orders')->assertRedirect('/account/login?next=%2Faccount%2Forders');
        $this->get('/account/addresses')->assertRedirect('/account/login?next=%2Faccount%2Faddresses');
        $this->get('/account/profile')->assertRedirect('/account/login?next=%2Faccount%2Fprofile');
    }

    public function test_customer_can_view_dashboard_with_counts(): void
    {
        $response = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->get('/account');

        $response->assertStatus(200);
        $response->assertSee('Hello, Kavita');
        $response->assertSee('Total Orders');
        $response->assertSee('Saved Addresses');
    }

    public function test_customer_can_update_profile_and_unique_phone_is_enforced(): void
    {
        // Another customer with a phone number
        $otherPhone = '+919811122233';
        Customer::create([
            'name' => 'Other User',
            'email' => 'other_' . uniqid() . '@example.com',
            'phone' => $otherPhone,
            'status' => CustomerStatus::ACTIVE,
        ]);

        // Attempt to update to the other user's phone -> 409 Conflict
        $conflictRes = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->putJson('/store/account/profile', [
                'name' => 'Kavita Sharma',
                'phone' => $otherPhone,
            ]);
        $conflictRes->assertStatus(409);

        // Update with valid new phone
        $newPhone = '+919988776655';
        $updateRes = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->putJson('/store/account/profile', [
                'name' => 'Kavita Sharma',
                'phone' => $newPhone,
            ]);

        $updateRes->assertStatus(200);

        $this->customer->refresh();
        $this->assertEquals('Kavita Sharma', $this->customer->name);
        $this->assertEquals($newPhone, $this->customer->phone);
    }

    public function test_customer_address_book_lifecycle(): void
    {
        // 1. Create first address -> should automatically be default shipping & billing
        $address1Res = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->postJson('/store/account/addresses', [
                'label' => 'HOME',
                'recipient_name' => 'Kavita Roy',
                'phone' => '+919876543210',
                'line1' => 'Flat 402, Royal Palms',
                'line2' => 'MG Road',
                'landmark' => 'Near City Mall',
                'city' => 'Jaipur',
                'state_code' => '08',
                'postal_code' => '302001',
                'is_default_shipping' => false, // First address forces true
                'is_default_billing' => false,
            ]);

        $address1Res->assertStatus(201);
        $addr1Id = $address1Res->json('data.id');

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr1Id,
            'customer_id' => $this->customer->id,
            'is_default_shipping' => 1,
            'is_default_billing' => 1,
        ]);

        // 2. Create second address as default shipping -> should unset default shipping from addr1
        $address2Res = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->postJson('/store/account/addresses', [
                'label' => 'WORK',
                'recipient_name' => 'Kavita Office',
                'phone' => '+919876543210',
                'line1' => 'Tower B, Tech Park',
                'city' => 'Jaipur',
                'state_code' => '08',
                'postal_code' => '302015',
                'is_default_shipping' => true,
                'is_default_billing' => false,
            ]);

        $address2Res->assertStatus(201);
        $addr2Id = $address2Res->json('data.id');

        // Address 2 is default shipping, Address 1 is not default shipping
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr2Id,
            'is_default_shipping' => 1,
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr1Id,
            'is_default_shipping' => 0,
            'is_default_billing' => 1, // still default billing
        ]);

        // 3. Update Address 1
        $updateRes = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->putJson("/store/account/addresses/{$addr1Id}", [
                'label' => 'HOME',
                'recipient_name' => 'Kavita Roy (Updated)',
                'phone' => '+919876543210',
                'line1' => 'Flat 402, Block A, Royal Palms',
                'city' => 'Jaipur',
                'state_code' => '08',
                'postal_code' => '302001',
                'is_default_shipping' => false,
                'is_default_billing' => true,
            ]);
        $updateRes->assertStatus(200);

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr1Id,
            'recipient_name' => 'Kavita Roy (Updated)',
        ]);

        // 4. Delete Address 2 -> Remaining address (Addr 1) reclaims default shipping
        $deleteRes = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->deleteJson("/store/account/addresses/{$addr2Id}");
        $deleteRes->assertStatus(204);

        $this->assertDatabaseMissing('customer_addresses', ['id' => $addr2Id]);
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr1Id,
            'is_default_shipping' => 1,
        ]);
    }

    public function test_customer_can_view_order_history_and_detail(): void
    {
        $order = $this->createOrderForCustomer($this->customer);

        // View orders list
        $listRes = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->get('/account/orders');

        $listRes->assertStatus(200);
        $listRes->assertSee($order->order_number);

        // View single order detail
        $detailRes = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->get("/account/orders/{$order->order_number}");

        $detailRes->assertStatus(200);
        $detailRes->assertSee($order->order_number);
        $detailRes->assertSee('Chanderi Silk Kurta');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $otherCustomer = Customer::create([
            'name' => 'Sneha Patel',
            'email' => 'sneha_' . uniqid() . '@example.com',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $otherOrder = $this->createOrderForCustomer($otherCustomer);

        // Accessing other customer's order returns 404
        $res = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->token)
            ->get("/account/orders/{$otherOrder->order_number}");

        $res->assertStatus(404);
    }

    private function createOrderForCustomer(Customer $cust): Order
    {
        $order = Order::create([
            'order_number' => OrderNumber::generate(),
            'customer_id' => $cust->id,
            'customer_name' => $cust->name,
            'customer_email' => $cust->email,
            'customer_phone' => '+919876543210',
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::ONLINE,
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 249900,
            'discount_paise' => 0,
            'shipping_paise' => 0,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 211780,
            'cgst_paise' => 19060,
            'sgst_paise' => 19060,
            'igst_paise' => 0,
            'tax_paise' => 38120,
            'total_paise' => 249900,
            'payment_due_at' => now()->addMinutes(30),
            'placed_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::first();
        $variant = ProductVariant::first();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product?->id ?? 1,
            'variant_id' => $variant?->id ?? 1,
            'product_name' => 'Chanderi Silk Kurta',
            'variant_title' => 'Maroon / L',
            'option_values' => ['Size' => 'L', 'Color' => 'Maroon'],
            'sku' => $variant?->sku ?? 'DS-SKU-1',
            'hsn_code' => '6204',
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

        return $order;
    }
}
