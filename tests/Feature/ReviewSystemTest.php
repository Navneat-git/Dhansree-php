<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\CustomerStatus;
use App\Enums\GstRegistrationType;
use App\Enums\GstSupplyType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Support\Auth\AdminSessionManager;
use App\Support\Auth\CustomerSessionManager;
use App\Support\Commerce\OrderNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use DatabaseTransactions;

    private Customer $customer;
    private string $customerToken;
    private AdminUser $admin;
    private string $adminToken;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();

        // 1. Setup Customer
        $this->customer = Customer::create([
            'name' => 'Ananya Sen',
            'email' => 'ananya_' . uniqid() . '@example.com',
            'phone' => '+919876543211',
            'status' => CustomerStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $custSession = CustomerSessionManager::createSession($this->customer);
        $this->customerToken = $custSession['token'];

        // 2. Setup Super Admin
        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );
        $this->admin = AdminUser::create([
            'email' => 'rev_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Review Moderator',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);
        $adminSession = AdminSessionManager::createSession($this->admin);
        $this->adminToken = $adminSession['token'];

        // 3. Setup Catalog
        $cat = Category::create([
            'name' => 'Silk Kurties',
            'slug' => 'silk-kurties-' . uniqid(),
            'is_visible' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Chanderi Pure Silk Kurti',
            'slug' => 'chanderi-pure-silk-' . uniqid(),
            'brand' => 'Dhansree',
            'status' => 'ACTIVE',
            'published_at' => now(),
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'DS-SLK-' . strtoupper(uniqid()),
            'price_paise' => 299900,
            'mrp_paise' => 399900,
            'cost_paise' => 150000,
            'option_signature' => 'Maroon / M',
            'weight_grams' => 350,
            'position' => 1,
        ]);
    }

    private function asCustomer()
    {
        return $this->withUnencryptedCookie(CustomerSessionManager::getCookieName(), $this->customerToken);
    }

    private function asAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->adminToken);
    }

    private function createOrderWithItem(OrderStatus $status): OrderItem
    {
        $order = Order::create([
            'order_number' => OrderNumber::generate(),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'customer_phone' => $this->customer->phone,
            'checkout_key_hash' => hash('sha256', uniqid('', true)),
            'request_hash' => hash('sha256', uniqid('', true)),
            'status' => $status,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::ONLINE,
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => GstSupplyType::INTRA_STATE,
            'item_count' => 1,
            'subtotal_paise' => 299900,
            'discount_paise' => 0,
            'shipping_paise' => 0,
            'shipping_gst_rate_bps' => 1800,
            'shipping_taxable_paise' => 0,
            'shipping_tax_paise' => 0,
            'taxable_paise' => 254152,
            'cgst_paise' => 22874,
            'sgst_paise' => 22874,
            'igst_paise' => 0,
            'tax_paise' => 45748,
            'total_paise' => 299900,
            'payment_due_at' => now()->addMinutes(30),
            'placed_at' => now(),
            'updated_at' => now(),
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_title' => $this->variant->option_signature,
            'option_values' => ['Size' => 'M', 'Color' => 'Maroon'],
            'sku' => $this->variant->sku,
            'hsn_code' => '6204',
            'quantity' => 1,
            'unit_price_paise' => 299900,
            'mrp_paise' => 399900,
            'discount_paise' => 0,
            'gst_rate_bps' => 1800,
            'taxable_paise' => 254152,
            'cgst_paise' => 22874,
            'sgst_paise' => 22874,
            'igst_paise' => 0,
            'tax_paise' => 45748,
            'line_total_paise' => 299900,
            'position' => 1,
        ]);
    }

    public function test_customer_cannot_review_undelivered_item(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::CONFIRMED);

        $res = $this->asCustomer()->postJson('/account/reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'title' => 'Lovely item',
            'body' => 'I love the quality and smooth silk finish of this dress.',
        ]);

        $res->assertStatus(409);
        $res->assertSee('You can write a review once this order has been delivered');
    }

    public function test_customer_can_submit_review_for_delivered_item(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::DELIVERED);

        $res = $this->asCustomer()->postJson('/account/reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'title' => 'Exquisite Craftsmanship',
            'body' => 'Fabric quality is top notch and the fitting is completely true to size.',
        ]);

        $res->assertStatus(201);
        $reviewId = $res->json('data.id');

        $this->assertDatabaseHas('reviews', [
            'id' => $reviewId,
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'title' => 'Exquisite Craftsmanship',
            'status' => 'PENDING',
            'published_at' => null,
            'moderated_at' => null,
        ]);

        // Audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'REVIEW_SUBMITTED',
            'actor_type' => 'CUSTOMER',
            'actor_customer_id' => $this->customer->id,
            'entity_type' => 'review',
            'entity_id' => $reviewId,
        ]);
    }

    public function test_customer_cannot_submit_duplicate_review_for_same_item(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::DELIVERED);

        // First review
        $res1 = $this->asCustomer()->postJson('/account/reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'body' => 'Excellent quality and stitching.',
        ]);
        $res1->assertStatus(201);

        // Second review attempt on same item -> 409
        $res2 = $this->asCustomer()->postJson('/account/reviews', [
            'order_item_id' => $item->id,
            'rating' => 4,
            'body' => 'Trying to submit a second review for the same purchase.',
        ]);
        $res2->assertStatus(409);
        $res2->assertSee('already reviewed');
    }

    public function test_review_validation_rules(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::DELIVERED);

        // Rating too high (6)
        $res1 = $this->asCustomer()->postJson('/account/reviews', [
            'order_item_id' => $item->id,
            'rating' => 6,
            'body' => 'This body is sufficiently long.',
        ]);
        $res1->assertStatus(422);

        // Body too short (< 10 chars)
        $res2 = $this->asCustomer()->postJson('/account/reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'body' => 'Short',
        ]);
        $res2->assertStatus(422);
    }

    public function test_public_product_page_only_shows_approved_reviews(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::DELIVERED);

        // Create pending review
        $review = Review::create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'title' => 'Pending Review Headline',
            'body' => 'This is an awesome kurta awaiting review.',
            'status' => ReviewStatus::PENDING,
            'published_at' => null,
            'moderated_at' => null,
        ]);

        // Public page must NOT display pending review
        $resPublic = $this->get('/product/' . $this->product->slug);
        $resPublic->assertStatus(200);
        $resPublic->assertDontSee('Pending Review Headline');
        $resPublic->assertSee('No Reviews Yet');

        // Admin approves review
        $approveRes = $this->asAdmin()->post("/admin/reviews/{$review->id}/moderate", [
            'action' => 'APPROVE',
            'note' => 'Authentic customer feedback',
        ]);
        $approveRes->assertRedirect();

        $review->refresh();
        $this->assertEquals(ReviewStatus::APPROVED, $review->status);
        $this->assertNotNull($review->published_at);
        $this->assertNotNull($review->moderated_at);

        // Public page NOW displays approved review and rating summary
        $resPublic2 = $this->get('/product/' . $this->product->slug);
        $resPublic2->assertStatus(200);
        $resPublic2->assertSee('Pending Review Headline');
        $resPublic2->assertSee('Ananya S.');
        $resPublic2->assertSee('Verified Purchase');
        $resPublic2->assertSee('5.0');
    }

    public function test_admin_can_reject_and_hide_reviews(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::DELIVERED);

        $review = Review::create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'rating' => 1,
            'title' => 'Abusive Post',
            'body' => 'Spam content that should be rejected by moderators.',
            'status' => ReviewStatus::PENDING,
            'published_at' => null,
            'moderated_at' => null,
        ]);

        // Reject
        $rejectRes = $this->asAdmin()->post("/admin/reviews/{$review->id}/moderate", [
            'action' => 'REJECT',
            'note' => 'Inappropriate content',
        ]);
        $rejectRes->assertRedirect();

        $review->refresh();
        $this->assertEquals(ReviewStatus::REJECTED, $review->status);
        $this->assertNull($review->published_at);
        $this->assertEquals('Inappropriate content', $review->moderation_note);

        // Audit log for rejection
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'REVIEW_REJECTED',
            'actor_type' => 'ADMIN',
            'actor_admin_user_id' => $this->admin->id,
            'entity_id' => $review->id,
        ]);
    }

    public function test_customer_can_edit_review_within_30_days(): void
    {
        $item = $this->createOrderWithItem(OrderStatus::DELIVERED);

        $review = Review::create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'rating' => 4,
            'title' => 'Good Fit',
            'body' => 'Initial review text that is sufficiently long.',
            'status' => ReviewStatus::APPROVED,
            'published_at' => now(),
            'moderated_at' => now(),
            'moderated_by_admin_user_id' => $this->admin->id,
        ]);

        // Customer edits review
        $editRes = $this->asCustomer()->putJson("/account/reviews/{$review->id}", [
            'rating' => 5,
            'title' => 'Updated Fit - Amazing!',
            'body' => 'After washing and wearing, this kurta fits even better.',
        ]);

        $editRes->assertStatus(200);

        $review->refresh();
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Updated Fit - Amazing!', $review->title);
        // Resets to PENDING and clears published_at
        $this->assertEquals(ReviewStatus::PENDING, $review->status);
        $this->assertNull($review->published_at);
        $this->assertNull($review->moderated_at);
    }
}
