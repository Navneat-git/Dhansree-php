<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\CustomerSessionRevokeReason;
use App\Enums\CustomerStatus;
use App\Enums\GstRegistrationType;
use App\Enums\GstSupplyType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Support\Auth\AdminSessionManager;
use App\Support\Auth\CustomerSessionManager;
use App\Support\Commerce\OrderNumber;
use App\Support\Rbac\Permissions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $rawAdminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'admin_cust_' . uniqid() . '@dhansree.test',
            'name' => 'Customer Administrator',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawAdminToken = $sessionData['token'];
    }

    private function asAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawAdminToken);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin/customers')->assertRedirect('/admin/login');
        $this->get('/admin/customers/1')->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_customers_index(): void
    {
        $customer = Customer::create([
            'name' => 'Pooja Agarwal',
            'email' => 'pooja_' . uniqid() . '@example.com',
            'phone' => '+919876500001',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $response = $this->asAdmin()->get('/admin/customers');

        $response->assertStatus(200);
        $response->assertSee('People with an account on the store');
        $response->assertSee('Pooja Agarwal');
        $response->assertSee($customer->email);
    }

    public function test_admin_can_filter_customers_by_status(): void
    {
        $activeCustomer = Customer::create([
            'name' => 'Active Customer',
            'email' => 'active_' . uniqid() . '@example.com',
            'phone' => '+919876500002',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $disabledCustomer = Customer::create([
            'name' => 'Disabled Customer',
            'email' => 'disabled_' . uniqid() . '@example.com',
            'phone' => '+919876500003',
            'status' => CustomerStatus::DISABLED,
        ]);

        // Filter by ACTIVE
        $resActive = $this->asAdmin()->get('/admin/customers?status=ACTIVE');
        $resActive->assertStatus(200);
        $resActive->assertSee($activeCustomer->name);
        $resActive->assertDontSee($disabledCustomer->name);

        // Filter by DISABLED
        $resDisabled = $this->asAdmin()->get('/admin/customers?status=DISABLED');
        $resDisabled->assertStatus(200);
        $resDisabled->assertSee($disabledCustomer->name);
        $resDisabled->assertDontSee($activeCustomer->name);
    }

    public function test_admin_can_search_customers_by_keyword(): void
    {
        $uniqueName = 'UniqueSearchableCustomer ' . uniqid();
        $targetCustomer = Customer::create([
            'name' => $uniqueName,
            'email' => 'search_target_' . uniqid() . '@example.com',
            'phone' => '+919876500004',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $otherCustomer = Customer::create([
            'name' => 'Other Person',
            'email' => 'other_' . uniqid() . '@example.com',
            'phone' => '+919876500005',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $response = $this->asAdmin()->get('/admin/customers?search=' . urlencode($uniqueName));
        $response->assertStatus(200);
        $response->assertSee($uniqueName);
        $response->assertDontSee($otherCustomer->name);
    }

    public function test_admin_can_view_customer_detail_with_orders_and_addresses(): void
    {
        $customer = Customer::create([
            'name' => 'Meera Patel',
            'email' => 'meera_' . uniqid() . '@example.com',
            'phone' => '+919876500006',
            'status' => CustomerStatus::ACTIVE,
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'HOME',
            'recipient_name' => 'Meera Patel',
            'phone' => '+919876500006',
            'line1' => '22 Heritage Enclave',
            'city' => 'Jaipur',
            'state_code' => '08',
            'postal_code' => '302005',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $order = Order::create([
            'order_number' => OrderNumber::generate(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
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

        $response = $this->asAdmin()->get("/admin/customers/{$customer->id}");

        $response->assertStatus(200);
        $response->assertSee('Meera Patel');
        $response->assertSee($customer->email);
        $response->assertSee('22 Heritage Enclave');
        $response->assertSee($order->order_number);
    }

    public function test_admin_can_deactivate_and_reactivate_customer_status(): void
    {
        $customer = Customer::create([
            'name' => 'Suman Verma',
            'email' => 'suman_' . uniqid() . '@example.com',
            'phone' => '+919876500007',
            'status' => CustomerStatus::ACTIVE,
        ]);

        // Create an active session for the customer
        $session = CustomerSessionManager::createSession($customer);
        $this->assertDatabaseHas('customer_sessions', [
            'id' => $session['session']->id,
            'revoked_at' => null,
        ]);

        // 1. Deactivate customer
        $deactivateRes = $this->asAdmin()->post("/admin/customers/{$customer->id}/status", [
            'status' => CustomerStatus::DISABLED->value,
        ]);
        $deactivateRes->assertRedirect("/admin/customers/{$customer->id}");

        $customer->refresh();
        $this->assertEquals(CustomerStatus::DISABLED, $customer->status);

        // Active session must have been revoked by admin
        $this->assertDatabaseHas('customer_sessions', [
            'id' => $session['session']->id,
            'revoke_reason' => CustomerSessionRevokeReason::REVOKED_BY_ADMIN->value,
        ]);

        // 2. Reactivate customer
        $reactivateRes = $this->asAdmin()->post("/admin/customers/{$customer->id}/status", [
            'status' => CustomerStatus::ACTIVE->value,
        ]);
        $reactivateRes->assertRedirect("/admin/customers/{$customer->id}");

        $customer->refresh();
        $this->assertEquals(CustomerStatus::ACTIVE, $customer->status);
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $restrictedRole = AdminRole::create([
            'name' => 'Restricted Role ' . uniqid(),
            'code' => 'RESTRICTED_' . uniqid(),
            'grants_all_permissions' => false,
        ]);

        $restrictedAdmin = AdminUser::create([
            'email' => 'restricted_' . uniqid() . '@dhansree.test',
            'name' => 'Restricted Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $restrictedAdmin->roles()->attach($restrictedRole->id);

        $sessionData = AdminSessionManager::createSession($restrictedAdmin);
        $restrictedToken = $sessionData['token'];

        $res = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $restrictedToken)
            ->get('/admin/customers');

        $res->assertStatus(403);
    }
}
