<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AdminUser;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Email\EmailService;
use App\Services\Orders\OrderFulfillmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailTemplatesAndNotificationsTest extends TestCase
{
    protected EmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailService = app(EmailService::class);
        $this->emailService->ensureDefaultTemplates();
    }

    public function test_all_email_templates_render_both_html_and_text_properly(): void
    {
        foreach (EmailType::cases() as $type) {
            $rendered = $this->emailService->preview($type->value);

            $this->assertNotEmpty($rendered['subject'], "Subject for {$type->value} should not be empty");
            $this->assertNotEmpty($rendered['html'], "HTML for {$type->value} should not be empty");
            $this->assertNotEmpty($rendered['text'], "Text for {$type->value} should not be empty");

            // Verify HTML contains brand styling and structure
            $this->assertStringContainsString('DHANSREE', $rendered['html']);
            $this->assertStringContainsString('Ethnic Elegance', $rendered['html']);
            $this->assertStringContainsString('info@dhansree.com', $rendered['html']);

            // Verify Text contains core facts
            $this->assertStringContainsString('DHANSREE', $rendered['text']);
            $this->assertStringContainsString('info@dhansree.com', $rendered['text']);
        }
    }

    public function test_order_confirmation_email_template_contains_order_facts_and_items(): void
    {
        $rendered = $this->emailService->preview('ORDER_CONFIRMATION');

        $this->assertStringContainsString('Order', $rendered['subject']);
        $this->assertStringContainsString('Items Ordered', $rendered['html']);
        $this->assertStringContainsString('Delivery Address', $rendered['html']);
        $this->assertStringContainsString('Subtotal', $rendered['html']);
        $this->assertStringContainsString('Total Amount', $rendered['html']);
        $this->assertStringContainsString('View Your Order', $rendered['html']);
    }

    public function test_order_cancelled_email_template_contains_cancellation_details(): void
    {
        $rendered = $this->emailService->preview('ORDER_CANCELLED');

        $this->assertStringContainsString('cancelled', strtolower($rendered['subject']));
        $this->assertStringContainsString('Order &amp; Refund Status', $rendered['html']);
        $this->assertStringContainsString('Browse Dhansree Store', $rendered['html']);
    }

    public function test_newsletter_welcome_email_template_contains_coupon_and_unsubscribe_link(): void
    {
        $rendered = $this->emailService->preview('NEWSLETTER_WELCOME');

        $this->assertStringContainsString('Welcome', $rendered['subject']);
        $this->assertStringContainsString('WELCOME50', $rendered['html']);
        $this->assertStringContainsString('Explore New Arrivals', $rendered['html']);
        $this->assertStringContainsString('unsubscribe', strtolower($rendered['html']));
        $this->assertStringContainsString('unsubscribe', strtolower($rendered['text']));
    }

    public function test_newsletter_subscription_flow_dispatches_welcome_email(): void
    {
        $email = 'subscriber_' . Str::random(8) . '@example.com';

        $response = $this->postJson('/newsletter/subscribe', [
            'email' => $email,
            'consent' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'code' => 'WELCOME50',
        ]);

        // Verify outbox record exists
        $subscriber = NewsletterSubscriber::where('email', $email)->first();
        $this->assertNotNull($subscriber);

        $message = EmailMessage::where('recipient', $email)
            ->where('type', EmailType::NEWSLETTER_WELCOME)
            ->first();

        $this->assertNotNull($message, 'Welcome email message should be recorded in email_messages');
        $this->assertEquals(EmailStatus::SENT, $message->status);
        $this->assertStringContainsString('Welcome', $message->subject);

        // Test one-click unsubscribe
        $unsubResponse = $this->get('/newsletter/unsubscribe?token=' . $subscriber->unsubscribe_token);
        $unsubResponse->assertStatus(200);
        $unsubResponse->assertSee('Unsubscribed Successfully');

        $subscriber->refresh();
        $this->assertEquals('UNSUBSCRIBED', $subscriber->status);
        $this->assertNotNull($subscriber->unsubscribed_at);
    }

    public function test_order_cancellation_dispatches_order_cancelled_email(): void
    {
        // 1. Create a dummy order for testing
        $variant = ProductVariant::whereNull('archived_at')->first();
        $this->assertNotNull($variant);

        $order = Order::create([
            'order_number' => \App\Support\Commerce\OrderNumber::generate(),
            'checkout_key_hash' => hash('sha256', uniqid('order_key_', true)),
            'request_hash' => hash('sha256', uniqid('request_key_', true)),
            'customer_name' => 'Aditi Rao',
            'customer_email' => 'aditi.rao_' . Str::random(4) . '@example.com',
            'customer_phone' => '+919876543210',
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::COD,
            'currency' => 'INR',
            'prices_include_tax' => true,
            'gst_registration_type' => \App\Enums\GstRegistrationType::REGULAR,
            'store_state_code' => '08',
            'place_of_supply_state_code' => '08',
            'supply_type' => \App\Enums\GstSupplyType::INTRA_STATE,
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
            'placed_at' => now(),
        ]);

        $fulfillment = app(OrderFulfillmentService::class);
        $fulfillment->cancelOrder($order, 'Customer requested cancellation via phone');

        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // Verify outbox record
        $cancelledMsg = EmailMessage::where('order_id', $order->id)
            ->where('type', EmailType::ORDER_CANCELLED)
            ->first();

        $this->assertNotNull($cancelledMsg, 'Order cancelled email should be logged in email_messages');
        $this->assertEquals(EmailStatus::SENT, $cancelledMsg->status);
        $this->assertStringContainsString($order->order_number, $cancelledMsg->subject);
    }

    public function test_admin_can_access_and_manage_email_templates(): void
    {
        $admin = AdminUser::first();
        $this->assertNotNull($admin);

        // 1. Index page
        $indexResponse = $this->actingAs($admin, 'admin')->get('/admin/emails');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Transactional Email Templates');
        $indexResponse->assertSee('Order Confirmation');
        $indexResponse->assertSee('Order Cancelled');

        // 2. Edit page
        $editResponse = $this->actingAs($admin, 'admin')->get('/admin/emails/ORDER_CONFIRMATION');
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Order Confirmation');
        $editResponse->assertSee('Live Preview');
        $editResponse->assertSee('Subject Line');

        // 3. Update template
        $updateResponse = $this->actingAs($admin, 'admin')->put('/admin/emails/ORDER_CONFIRMATION', [
            'subject' => 'Hooray! Your {{store_name}} order {{order_number}} is confirmed',
            'heading' => 'Your Order Is Confirmed',
            'intro' => 'We are delighted to prepare your order with handcrafted love.',
            'show_item_images' => 1,
        ]);

        $updateResponse->assertRedirect('/admin/emails/ORDER_CONFIRMATION');

        $template = EmailTemplate::where('type', EmailType::ORDER_CONFIRMATION)->first();
        $this->assertStringContainsString('Hooray!', $template->subject);

        // 4. Preview AJAX
        $previewResponse = $this->actingAs($admin, 'admin')->postJson('/admin/emails/ORDER_CONFIRMATION/preview', [
            'subject' => 'Preview Subject',
            'heading' => 'Preview Heading',
            'intro' => 'Preview Intro',
        ]);
        $previewResponse->assertStatus(200);
        $previewResponse->assertJsonStructure(['success', 'subject', 'html', 'text']);

        // 5. Reset to defaults
        $resetResponse = $this->actingAs($admin, 'admin')->post('/admin/emails/ORDER_CONFIRMATION/reset');
        $resetResponse->assertRedirect('/admin/emails/ORDER_CONFIRMATION');

        $template->refresh();
        $this->assertEquals('Order {{order_number}} confirmed', $template->subject);
    }
}
