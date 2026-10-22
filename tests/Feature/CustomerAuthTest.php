<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerLoginChallenge;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Support\Auth\CustomerSessionManager;
use App\Support\Security\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new PasswordHasher();
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/account/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in');
        $response->assertSee('Send me a code');
    }

    public function test_request_otp_generates_challenge_and_audit(): void
    {
        $email = 'test_' . uniqid() . '@example.com';

        $response = $this->post('/account/login', [
            'email' => $email,
        ]);

        $response->assertRedirect('/account/login/verify?email=' . urlencode($email) . '&next=%2Faccount');
        $response->assertSessionHas('notice');

        $this->assertDatabaseHas('customer_login_challenges', [
            'email' => $email,
            'attempts' => 0,
            'send_count' => 1,
        ]);
    }

    public function test_request_otp_enforces_cooldown_window(): void
    {
        $email = 'test_' . uniqid() . '@example.com';

        // First request
        $this->post('/account/login', ['email' => $email]);

        // Immediate second request (within 60s cooldown)
        $response = $this->postJson('/store/account/login', [
            'email' => $email,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('resendAfterSeconds', $data);
        $this->assertLessThanOrEqual(60, $data['resendAfterSeconds']);
    }

    public function test_wrong_otp_increments_attempts_and_lockout_after_five_failures(): void
    {
        $email = 'test_' . uniqid() . '@example.com';
        $code = '123456';
        $codeHash = $this->hasher->hash($code);

        $challenge = CustomerLoginChallenge::create([
            'email' => $email,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'send_count' => 1,
            'last_sent_at' => now(),
            'window_start' => now(),
        ]);

        // Attempt 1 with wrong code
        $response1 = $this->post('/account/login/verify', [
            'email' => $email,
            'code' => '999999',
        ]);
        $response1->assertStatus(401);

        $challenge->refresh();
        $this->assertEquals(1, $challenge->attempts);

        // Attempt 2, 3, 4
        for ($i = 2; $i <= 4; $i++) {
            $this->post('/account/login/verify', [
                'email' => $email,
                'code' => '999999',
            ])->assertStatus(401);
        }

        $challenge->refresh();
        $this->assertEquals(4, $challenge->attempts);

        // 5th failed attempt should destroy the challenge
        $this->post('/account/login/verify', [
            'email' => $email,
            'code' => '999999',
        ])->assertStatus(401);

        $this->assertDatabaseMissing('customer_login_challenges', [
            'id' => $challenge->id,
        ]);
    }

    public function test_valid_otp_authenticates_customer_sets_session_cookie_and_links_guest_orders(): void
    {
        $email = 'shopper_' . uniqid() . '@example.com';
        $code = '654321';
        $codeHash = $this->hasher->hash($code);

        // Create an existing guest order placed with this email
        $guestOrder = $this->createTestOrder([
            'customer_email' => $email,
            'customer_name' => 'Ananya Sharma',
            'customer_id' => null,
        ]);

        $challenge = CustomerLoginChallenge::create([
            'email' => $email,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'send_count' => 1,
            'last_sent_at' => now(),
            'window_start' => now(),
        ]);

        $response = $this->post('/account/login/verify', [
            'email' => $email,
            'code' => $code,
            'next' => '/account',
        ]);

        $response->assertRedirect('/account');
        $response->assertCookie(CustomerSessionManager::getCookieName());

        // Check that customer account was auto-created and named after past order
        $customer = Customer::where('email', $email)->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Ananya Sharma', $customer->name);
        $this->assertEquals(CustomerStatus::ACTIVE, $customer->status);
        $this->assertNotNull($customer->email_verified_at);

        // Verify guest order was reconciled to this customer
        $guestOrder->refresh();
        $this->assertEquals($customer->id, $guestOrder->customer_id);

        // Verify challenge is marked consumed
        $challenge->refresh();
        $this->assertNotNull($challenge->consumed_at);
    }

    public function test_disabled_customer_cannot_sign_in(): void
    {
        $email = 'disabled_' . uniqid() . '@example.com';
        $code = '112233';

        Customer::create([
            'name' => 'Disabled User',
            'email' => $email,
            'status' => CustomerStatus::DISABLED,
        ]);

        CustomerLoginChallenge::create([
            'email' => $email,
            'code_hash' => $this->hasher->hash($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'send_count' => 1,
            'last_sent_at' => now(),
            'window_start' => now(),
        ]);

        $response = $this->post('/account/login/verify', [
            'email' => $email,
            'code' => $code,
        ]);

        $response->assertStatus(403);
    }

    public function test_customer_logout_revokes_session_and_clears_cookie(): void
    {
        $customer = Customer::create([
            'name' => 'Pooja Verma',
            'email' => 'pooja_' . uniqid() . '@example.com',
            'status' => CustomerStatus::ACTIVE,
        ]);

        $sessionData = CustomerSessionManager::createSession($customer);

        $response = $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $sessionData['token'])
            ->post('/account/logout');

        $response->assertRedirect('/');

        // Verify session is revoked
        $this->assertDatabaseHas('customer_sessions', [
            'token_hash' => CustomerSessionManager::hashToken($sessionData['token']),
            'revoke_reason' => 'LOGOUT',
        ]);
    }

    private function createTestOrder(array $overrides = []): Order
    {
        $defaults = [
            'order_number' => \App\Support\Commerce\OrderNumber::generate(),
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'status' => \App\Enums\OrderStatus::CONFIRMED,
            'payment_status' => \App\Enums\PaymentStatus::PENDING,
            'payment_method' => \App\Enums\PaymentMethod::COD,
            'customer_name' => 'Ananya Sharma',
            'customer_email' => 'ananya@example.com',
            'customer_phone' => '+919876543210',
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => \App\Enums\GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => \App\Enums\GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 199900,
            'discount_paise' => 0,
            'shipping_paise' => 0,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 169407,
            'cgst_paise' => 15246,
            'sgst_paise' => 15247,
            'igst_paise' => 0,
            'tax_paise' => 30493,
            'total_paise' => 199900,
            'placed_at' => now(),
            'updated_at' => now(),
        ];

        return Order::create(array_merge($defaults, $overrides));
    }
}
