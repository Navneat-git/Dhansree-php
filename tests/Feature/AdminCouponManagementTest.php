<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\CouponType;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Auth\AdminSessionManager;
use App\Support\Commerce\OrderNumber;
use App\Support\Rbac\Permissions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCouponManagementTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $adminToken;
    private AdminUser $readOnlyAdmin;
    private string $readOnlyAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();

        // 1. Super Admin with all permissions
        $superRole = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'coupon_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Coupon Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($superRole->id);

        $superSession = AdminSessionManager::createSession($this->admin);
        $this->adminToken = $superSession['token'];

        // 2. Restricted Admin without coupon permissions
        $limitedRole = AdminRole::create([
            'code' => 'ORDER_VIEWER_' . uniqid(),
            'name' => 'Order Viewer',
            'grants_all_permissions' => false,
        ]);
        $orderPerm = \App\Models\AdminPermission::firstOrCreate(
            ['code' => Permissions::ORDER_VIEW],
            ['description' => 'View orders']
        );
        $limitedRole->permissions()->attach($orderPerm->id);

        $this->readOnlyAdmin = AdminUser::create([
            'email' => 'ro_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Read Only Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->readOnlyAdmin->roles()->attach($limitedRole->id);

        $roSession = AdminSessionManager::createSession($this->readOnlyAdmin);
        $this->readOnlyAdminToken = $roSession['token'];
    }

    private function asAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->adminToken);
    }

    private function asReadOnlyAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->readOnlyAdminToken);
    }

    public function test_admin_can_view_coupons_list(): void
    {
        Coupon::create([
            'code' => 'LIST-TEST-' . strtoupper(uniqid()),
            'description' => 'Test coupon description',
            'type' => CouponType::PERCENTAGE,
            'value' => 1500, // 15%
            'is_active' => true,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $response = $this->asAdmin()->get('/admin/coupons');

        $response->assertStatus(200);
        $response->assertViewIs('admin.coupons.index');
        $response->assertViewHas('coupons');
        $response->assertViewHas('stats');
    }

    public function test_admin_can_create_percentage_coupon(): void
    {
        $code = 'DIWALI-' . strtoupper(uniqid());

        $payload = [
            'code' => $code,
            'description' => 'Diwali festive special discount',
            'type' => CouponType::PERCENTAGE->value,
            'value' => 20, // 20% -> 2000 bps
            'min_order_rupees' => 1499,
            'max_discount_rupees' => 500, // ₹500 cap
            'usage_limit' => 500,
            'per_customer_limit' => 1,
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'is_active' => '1',
        ];

        $response = $this->asAdmin()->post('/admin/coupons', $payload);

        $response->assertRedirect('/admin/coupons');
        $response->assertSessionHas('success');

        $coupon = Coupon::where('code', $code)->first();
        $this->assertNotNull($coupon);
        $this->assertEquals(CouponType::PERCENTAGE, $coupon->type);
        $this->assertEquals(2000, $coupon->value); // 20% in bps
        $this->assertEquals(149900, $coupon->min_order_paise);
        $this->assertEquals(50000, $coupon->max_discount_paise);
        $this->assertEquals(500, $coupon->usage_limit);
        $this->assertEquals(1, $coupon->per_customer_limit);
        $this->assertTrue($coupon->is_active);
    }

    public function test_admin_can_create_fixed_amount_coupon(): void
    {
        $code = 'FLAT-' . strtoupper(uniqid());

        $payload = [
            'code' => $code,
            'description' => 'Flat ₹250 off on orders over ₹1299',
            'type' => CouponType::FIXED_AMOUNT->value,
            'value' => 250, // ₹250 -> 25000 paise
            'min_order_rupees' => 1299,
            'max_discount_rupees' => '', // Not applicable for fixed amount
            'usage_limit' => 200,
            'per_customer_limit' => 2,
            'is_active' => '1',
        ];

        $response = $this->asAdmin()->post('/admin/coupons', $payload);

        $response->assertRedirect('/admin/coupons');

        $coupon = Coupon::where('code', $code)->first();
        $this->assertNotNull($coupon);
        $this->assertEquals(CouponType::FIXED_AMOUNT, $coupon->type);
        $this->assertEquals(25000, $coupon->value);
        $this->assertEquals(129900, $coupon->min_order_paise);
        $this->assertNull($coupon->max_discount_paise);
    }

    public function test_coupon_validation_rejects_invalid_inputs(): void
    {
        // 1. Invalid code format with lowercase and special characters
        $res = $this->asAdmin()->post('/admin/coupons', [
            'code' => 'invalid code @#',
            'type' => CouponType::PERCENTAGE->value,
            'value' => 10,
        ]);
        $res->assertSessionHasErrors('code');

        // 2. Negative or 0 value
        $res = $this->asAdmin()->post('/admin/coupons', [
            'code' => 'VALID-CODE',
            'type' => CouponType::PERCENTAGE->value,
            'value' => 0,
        ]);
        $res->assertSessionHasErrors('value');

        // 3. Percentage exceeds 100%
        $res = $this->asAdmin()->post('/admin/coupons', [
            'code' => 'EXCESS-PCT',
            'type' => CouponType::PERCENTAGE->value,
            'value' => 150, // 15000 bps > 10000 bps
        ]);
        // The service or controller will reject percentage > 10000 bps
        $res->assertStatus(422);

        // 4. Expiry before start date
        $res = $this->asAdmin()->post('/admin/coupons', [
            'code' => 'BAD-DATE',
            'type' => CouponType::FIXED_AMOUNT->value,
            'value' => 100,
            'starts_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'expires_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);
        $res->assertSessionHasErrors('expires_at');
    }

    public function test_admin_can_update_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'UP-TEST-' . strtoupper(uniqid()),
            'description' => 'Original description',
            'type' => CouponType::FIXED_AMOUNT,
            'value' => 20000, // ₹200
            'is_active' => true,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $updatePayload = [
            'description' => 'Updated festive description',
            'type' => CouponType::FIXED_AMOUNT->value,
            'value' => 300, // ₹300 -> 30000 paise
            'min_order_rupees' => 1500,
            'max_discount_rupees' => '',
            'usage_limit' => 150,
            'per_customer_limit' => 3,
            'is_active' => '1',
        ];

        $response = $this->asAdmin()->put('/admin/coupons/' . $coupon->id, $updatePayload);

        $response->assertRedirect('/admin/coupons');
        $coupon->refresh();
        $this->assertEquals('Updated festive description', $coupon->description);
        $this->assertEquals(30000, $coupon->value);
        $this->assertEquals(150000, $coupon->min_order_paise);
        $this->assertEquals(150, $coupon->usage_limit);
        $this->assertEquals(3, $coupon->per_customer_limit);
    }

    public function test_admin_can_toggle_coupon_active_state(): void
    {
        $coupon = Coupon::create([
            'code' => 'TOGGLE-' . strtoupper(uniqid()),
            'type' => CouponType::PERCENTAGE,
            'value' => 1000,
            'is_active' => true,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        // Toggle off
        $response = $this->asAdmin()->post("/admin/coupons/{$coupon->id}/toggle");
        $response->assertRedirect();
        $coupon->refresh();
        $this->assertFalse($coupon->is_active);

        // Toggle back on
        $response = $this->asAdmin()->post("/admin/coupons/{$coupon->id}/toggle");
        $response->assertRedirect();
        $coupon->refresh();
        $this->assertTrue($coupon->is_active);
    }

    public function test_admin_can_start_scheduled_coupon_now(): void
    {
        $coupon = Coupon::create([
            'code' => 'SCHED-' . strtoupper(uniqid()),
            'type' => CouponType::PERCENTAGE,
            'value' => 2500,
            'is_active' => true,
            'starts_at' => now()->addDays(5),
            'expires_at' => now()->addDays(30),
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $this->assertTrue($coupon->starts_at->isFuture());

        $response = $this->asAdmin()->post("/admin/coupons/{$coupon->id}/start-now");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $coupon->refresh();
        $this->assertTrue($coupon->is_active);
        $this->assertTrue($coupon->starts_at->isPast());
    }

    public function test_admin_can_delete_unused_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'UNUSED-' . strtoupper(uniqid()),
            'type' => CouponType::FIXED_AMOUNT,
            'value' => 10000,
            'times_redeemed' => 0,
            'is_active' => true,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $response = $this->asAdmin()->delete("/admin/coupons/{$coupon->id}");

        $response->assertRedirect('/admin/coupons');
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_admin_cannot_delete_coupon_with_redemptions(): void
    {
        $coupon = Coupon::create([
            'code' => 'USED-' . strtoupper(uniqid()),
            'type' => CouponType::FIXED_AMOUNT,
            'value' => 10000,
            'times_redeemed' => 3, // Already redeemed
            'is_active' => true,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $response = $this->asAdmin()->delete("/admin/coupons/{$coupon->id}");

        // Conflict 409 should be thrown
        $response->assertStatus(409);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    public function test_unauthorized_admin_cannot_manage_coupons(): void
    {
        // View coupon listing -> 403
        $res = $this->asReadOnlyAdmin()->get('/admin/coupons');
        $res->assertStatus(403);

        // Create coupon -> 403
        $res = $this->asReadOnlyAdmin()->post('/admin/coupons', [
            'code' => 'NOAUTH-100',
            'type' => CouponType::FIXED_AMOUNT->value,
            'value' => 100,
        ]);
        $res->assertStatus(403);
    }

    public function test_admin_can_set_featured_top_bar_coupon(): void
    {
        $code = 'FESTIVE' . rand(10, 99);
        $coupon = Coupon::create([
            'code' => $code,
            'type' => CouponType::PERCENTAGE,
            'value' => 1500,
            'is_active' => true,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $res = $this->asAdmin()->from('/admin/coupons')->post("/admin/coupons/{$coupon->id}/featured");
        $res->assertRedirect('/admin/coupons');
        $res->assertSessionHas('success');

        $this->assertEquals($code, \App\Models\StoreSettings::current()->featured_coupon_code);

        // Check coupon listing shows the badge
        $listRes = $this->asAdmin()->get('/admin/coupons');
        $listRes->assertOk();
        $listRes->assertSee('★ Top Bar Default');
        $listRes->assertSee($code);

        // Check storefront announcement bar renders the new code and discount
        $storeRes = $this->get('/');
        $storeRes->assertOk();
        $storeRes->assertSee($code);
        $storeRes->assertSee('Extra 15% OFF');
    }

    public function test_admin_can_update_featured_coupon_via_store_settings(): void
    {
        $res = $this->asAdmin()->from('/admin/settings')->put('/admin/settings', [
            'section' => 'general',
            'store_name' => 'Dhansree Trends',
            'support_email' => 'support@dhansree.com',
            'support_phone' => '+91 9876543210',
            'featured_coupon_code' => 'FESTIVE10',
        ]);

        $res->assertRedirect('/admin/settings');
        $this->assertEquals('FESTIVE10', \App\Models\StoreSettings::current()->featured_coupon_code);
    }
}
