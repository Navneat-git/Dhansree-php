<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailService
{
    /**
     * Default definitions for editable email templates.
     */
    public const DEFAULT_TEMPLATES = [
        'ORDER_CONFIRMATION' => [
            'subject' => 'Order {{order_number}} confirmed',
            'heading' => 'Order confirmed',
            'intro' => 'Thank you for your order! We have received it and will notify you as soon as it is packed and on its way.',
            'description' => 'Sent the moment an order is confirmed (COD or online payment verified).',
        ],
        'PAYMENT_CONFIRMATION' => [
            'subject' => 'Payment received for order {{order_number}}',
            'heading' => 'Payment received',
            'intro' => 'We have received your payment of ₹{{total}} for order {{order_number}}. Your order is confirmed.',
            'description' => 'Sent when an online payment goes through via payment gateway.',
        ],
        'PAYMENT_FAILED' => [
            'subject' => 'Payment not completed for {{order_number}}',
            'heading' => 'Payment not completed for {{order_number}}',
            'intro' => 'We could not complete your payment for order {{order_number}}. Nothing has been charged, and your items are held for you.',
            'description' => 'Sent when an online payment attempt does not go through.',
        ],
        'ORDER_CANCELLED' => [
            'subject' => 'Order {{order_number}} has been cancelled',
            'heading' => 'Order {{order_number}} has been cancelled',
            'intro' => 'Order {{order_number}} has been cancelled and nothing will be sent.',
            'description' => 'Sent when an order is cancelled by customer or store staff.',
        ],
        'SHIPMENT_DISPATCHED' => [
            'subject' => 'Order {{order_number}} is on its way',
            'heading' => 'Order {{order_number}} is on its way',
            'intro' => 'Your order {{order_number}} has been packed and handed over to our delivery partner.',
            'description' => 'Sent when an order parcel leaves and tracking AWB is generated.',
        ],
        'SHIPMENT_DELIVERED' => [
            'subject' => 'Order {{order_number}} has been delivered',
            'heading' => 'Order {{order_number}} has been delivered',
            'intro' => 'Your order {{order_number}} has been successfully delivered. We hope you love it!',
            'description' => 'Sent when courier confirms parcel delivery to the customer.',
        ],
    ];

    /**
     * Ensure standard default templates exist in database.
     */
    public function ensureDefaultTemplates(): void
    {
        foreach (self::DEFAULT_TEMPLATES as $typeName => $def) {
            $typeEnum = EmailType::tryFrom($typeName);
            if (!$typeEnum) {
                continue;
            }

            EmailTemplate::firstOrCreate(
                ['type' => $typeEnum],
                [
                    'subject' => $def['subject'],
                    'heading' => $def['heading'],
                    'intro' => $def['intro'],
                    'body_html' => null,
                    'show_item_images' => true,
                    'version' => 1,
                ]
            );
        }
    }

    /**
     * Send Order Confirmation Email.
     */
    public function sendOrderConfirmation(Order $order, bool $awaitingPayment = false): ?EmailMessage
    {
        $order->loadMissing(['items.variant.product.images', 'shippingAddress']);
        $recipient = $order->customer_email;
        if (empty($recipient)) {
            return null;
        }

        $store = StoreSettings::current();
        $dedupeKey = "ORDER_CONFIRMATION:{$order->id}:" . ($awaitingPayment ? '0' : '1');

        $data = [
            'order' => $order,
            'store' => $store,
            'awaitingPayment' => $awaitingPayment,
            'customerFirstName' => $this->extractFirstName($order->customer_name),
            'actionUrl' => url('/account/orders/' . $order->order_number),
            'actionLabel' => $awaitingPayment ? 'Complete Payment' : 'View Your Order',
            'footerReason' => "You are receiving this confirmation because order #{$order->order_number} was placed at {$store->store_name}.",
        ];

        return $this->send(
            EmailType::ORDER_CONFIRMATION,
            $recipient,
            $data,
            $order->id,
            $order->customer_id,
            $dedupeKey
        );
    }

    /**
     * Send Order Cancelled Email.
     */
    public function sendOrderCancelled(Order $order, bool $refundDue = false, ?string $reason = null): ?EmailMessage
    {
        $order->loadMissing(['items', 'shippingAddress']);
        $recipient = $order->customer_email;
        if (empty($recipient)) {
            return null;
        }

        $store = StoreSettings::current();
        $dedupeKey = "ORDER_CANCELLED:{$order->id}";

        $data = [
            'order' => $order,
            'store' => $store,
            'refundDue' => $refundDue,
            'reason' => $reason,
            'customerFirstName' => $this->extractFirstName($order->customer_name),
            'actionUrl' => url('/'),
            'actionLabel' => 'Browse Store',
            'footerReason' => "You are receiving this update because order #{$order->order_number} was cancelled at {$store->store_name}.",
        ];

        return $this->send(
            EmailType::ORDER_CANCELLED,
            $recipient,
            $data,
            $order->id,
            $order->customer_id,
            $dedupeKey
        );
    }

    /**
     * Send Payment Confirmation Email.
     */
    public function sendPaymentConfirmation(Order $order): ?EmailMessage
    {
        $recipient = $order->customer_email;
        if (empty($recipient)) {
            return null;
        }

        $store = StoreSettings::current();
        $dedupeKey = "PAYMENT_CONFIRMATION:{$order->id}";

        $data = [
            'order' => $order,
            'store' => $store,
            'customerFirstName' => $this->extractFirstName($order->customer_name),
            'actionUrl' => url('/account/orders/' . $order->order_number),
            'footerReason' => "You are receiving this receipt because payment for order #{$order->order_number} was processed at {$store->store_name}.",
        ];

        return $this->send(
            EmailType::PAYMENT_CONFIRMATION,
            $recipient,
            $data,
            $order->id,
            $order->customer_id,
            $dedupeKey
        );
    }

    /**
     * Send Payment Failed Email.
     */
    public function sendPaymentFailed(Order $order, int $amountPaise): ?EmailMessage
    {
        $recipient = $order->customer_email;
        if (empty($recipient)) {
            return null;
        }

        $store = StoreSettings::current();
        $dedupeKey = "PAYMENT_FAILED:{$order->id}:" . time();

        $data = [
            'order' => $order,
            'store' => $store,
            'amountPaise' => $amountPaise,
            'customerFirstName' => $this->extractFirstName($order->customer_name),
            'actionUrl' => url('/account/orders/' . $order->order_number),
            'footerReason' => "You are receiving this notification because an online payment attempt failed for order #{$order->order_number}.",
        ];

        return $this->send(
            EmailType::PAYMENT_FAILED,
            $recipient,
            $data,
            $order->id,
            $order->customer_id,
            $dedupeKey
        );
    }

    /**
     * Send Shipment Dispatched Email.
     */
    public function sendShipmentDispatched(Shipment $shipment): ?EmailMessage
    {
        $order = $shipment->order ?? Order::find($shipment->order_id);
        if (!$order || empty($order->customer_email)) {
            return null;
        }

        $store = StoreSettings::current();
        $dedupeKey = "SHIPMENT_DISPATCHED:{$shipment->id}";

        $data = [
            'order' => $order,
            'shipment' => $shipment,
            'store' => $store,
            'customerFirstName' => $this->extractFirstName($order->customer_name),
            'footerReason' => "You are receiving this tracking update for your dispatched parcel from {$store->store_name}.",
        ];

        return $this->send(
            EmailType::SHIPMENT_DISPATCHED,
            $order->customer_email,
            $data,
            $order->id,
            $order->customer_id,
            $dedupeKey
        );
    }

    /**
     * Send Shipment Delivered Email.
     */
    public function sendShipmentDelivered(Shipment $shipment): ?EmailMessage
    {
        $order = $shipment->order ?? Order::find($shipment->order_id);
        if (!$order || empty($order->customer_email)) {
            return null;
        }

        $store = StoreSettings::current();
        $dedupeKey = "SHIPMENT_DELIVERED:{$shipment->id}";

        $data = [
            'order' => $order,
            'shipment' => $shipment,
            'store' => $store,
            'customerFirstName' => $this->extractFirstName($order->customer_name),
            'actionUrl' => url('/account/orders/' . $order->order_number),
            'footerReason' => "You are receiving this notification because delivery was confirmed for order #{$order->order_number}.",
        ];

        return $this->send(
            EmailType::SHIPMENT_DELIVERED,
            $order->customer_email,
            $data,
            $order->id,
            $order->customer_id,
            $dedupeKey
        );
    }

    /**
     * Send Newsletter Welcome Email.
     */
    public function sendNewsletterWelcome(
        NewsletterSubscriber $subscriber,
        ?string $couponCode = 'WELCOME50',
        ?string $reward = '₹50 off on orders above ₹799'
    ): ?EmailMessage {
        $store = StoreSettings::current();
        $dedupeKey = "NEWSLETTER_WELCOME:{$subscriber->id}";

        $unsubscribeUrl = url('/newsletter/unsubscribe?token=' . ($subscriber->unsubscribe_token ?? ''));

        $data = [
            'subscriber' => $subscriber,
            'store' => $store,
            'couponCode' => $couponCode,
            'reward' => $reward,
            'unsubscribeUrl' => $unsubscribeUrl,
            'footerReason' => "You are receiving this because you subscribed to updates at {$store->store_name}.",
        ];

        return $this->send(
            EmailType::NEWSLETTER_WELCOME,
            $subscriber->email,
            $data,
            null,
            null,
            $dedupeKey
        );
    }

    /**
     * Send Newsletter Subscription Confirmation.
     */
    public function sendNewsletterConfirm(NewsletterSubscriber $subscriber, int $hours = 24): ?EmailMessage
    {
        $store = StoreSettings::current();
        $dedupeKey = "NEWSLETTER_CONFIRM:{$subscriber->id}";
        $confirmUrl = url('/newsletter/confirm?token=' . ($subscriber->unsubscribe_token ?? ''));

        $data = [
            'subscriber' => $subscriber,
            'store' => $store,
            'confirmUrl' => $confirmUrl,
            'hours' => $hours,
            'footerReason' => "You are receiving this verification because your address was submitted on the newsletter form at {$store->store_name}.",
        ];

        return $this->send(
            EmailType::NEWSLETTER_CONFIRM,
            $subscriber->email,
            $data,
            null,
            null,
            $dedupeKey
        );
    }

    /**
     * Send Customer Sign-in Login Code / OTP.
     */
    public function sendLoginCode(string $email, string $code, int $minutes = 10): ?EmailMessage
    {
        $store = StoreSettings::current();
        $dedupeKey = "LOGIN_CODE:" . md5($email . $code . time());

        $data = [
            'code' => $code,
            'minutes' => $minutes,
            'store' => $store,
            'footerReason' => "You are receiving this single-use code to sign in to {$store->store_name}. Do not share this code.",
        ];

        return $this->send(
            EmailType::LOGIN_CODE,
            $email,
            $data,
            null,
            null,
            $dedupeKey
        );
    }

    /**
     * Generic sender: renders message, creates outbox record, and dispatches email safely.
     */
    public function send(
        EmailType|string $type,
        string $recipient,
        array $data = [],
        ?int $orderId = null,
        ?int $customerId = null,
        ?string $dedupeKey = null
    ): ?EmailMessage {
        $typeEnum = is_string($type) ? EmailType::from($type) : $type;
        $recipient = strtolower(trim($recipient));

        // 1. Idempotency check: if already sent with this dedupe key, skip sending duplicate
        if ($dedupeKey) {
            $existing = EmailMessage::where('dedupe_key', $dedupeKey)
                ->where('status', EmailStatus::SENT)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        // 2. Render HTML & Text
        $rendered = $this->render($typeEnum, $data);

        // 3. Persist email outbox record
        $emailMessage = EmailMessage::create([
            'type' => $typeEnum,
            'dedupe_key' => $dedupeKey ?? "{$typeEnum->value}:" . Str::random(16),
            'status' => EmailStatus::PENDING,
            'recipient' => $recipient,
            'subject' => $rendered['subject'],
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'attempts' => 1,
            'provider' => config('mail.default', 'smtp'),
        ]);

        // 4. Dispatch Email
        try {
            $fromAddress = config('mail.from.address', 'info@dhansree.com');
            $fromName = config('mail.from.name', 'Dhansree');

            Mail::send([], [], function ($message) use ($recipient, $rendered, $fromAddress, $fromName) {
                $message->to($recipient)
                    ->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($rendered['subject'])
                    ->html($rendered['html'])
                    ->text($rendered['text']);
            });

            $emailMessage->update([
                'status' => EmailStatus::SENT,
                'sent_at' => now(),
                'last_error' => null,
            ]);

            Log::info("Email [{$typeEnum->value}] sent to [{$recipient}] with subject: {$rendered['subject']}");
        } catch (Throwable $e) {
            Log::warning("Failed to dispatch email [{$typeEnum->value}] to [{$recipient}]: {$e->getMessage()}");

            $emailMessage->update([
                'status' => EmailStatus::FAILED,
                'last_error' => $e->getMessage(),
                'next_attempt_at' => now()->addMinutes(15),
            ]);
        }

        return $emailMessage;
    }

    /**
     * Render subject, HTML body, and plain-text body for an email type.
     */
    public function render(EmailType $type, array $data = [], ?EmailTemplate $custom = null): array
    {
        $store = $data['store'] ?? StoreSettings::current();
        $data['store'] = $store;

        // Check if database template exists
        $template = $custom ?? EmailTemplate::where('type', $type)->first();

        // Calculate placeholder replacements
        $replacements = $this->buildReplacements($type, $data);

        // 1. Determine Subject
        $rawSubject = $template?->subject ?? (self::DEFAULT_TEMPLATES[$type->value]['subject'] ?? $this->fallbackSubject($type));
        $subject = $this->fillPlaceholders($rawSubject, $replacements);

        // 2. Determine Heading & Intro
        $rawHeading = $template?->heading ?? (self::DEFAULT_TEMPLATES[$type->value]['heading'] ?? $subject);
        $rawIntro = $template?->intro ?? (self::DEFAULT_TEMPLATES[$type->value]['intro'] ?? '');

        $data['heading'] = $this->fillPlaceholders($rawHeading, $replacements);
        $data['intro'] = $this->fillPlaceholders($rawIntro, $replacements);
        $data['subject'] = $subject;
        $data['showItemImages'] = $template ? $template->show_item_images : true;

        // 3. Render HTML
        if (!empty($template?->body_html)) {
            // Owner's custom HTML
            $customHtml = $this->fillPlaceholders($template->body_html, $replacements);
            // If it starts with full doctype or html tag, send as complete document; otherwise embed in master layout
            if (preg_match('/^\s*(<!doctype|<html)/i', $customHtml)) {
                $html = $customHtml;
            } else {
                $html = view('emails.layout', array_merge($data, ['content' => $customHtml]))->render();
            }
        } else {
            $viewName = $this->getViewNameForType($type);
            $html = view($viewName, $data)->render();
        }

        // 4. Render Plain Text
        $textViewName = $this->getTextViewNameForType($type);
        $text = view()->exists($textViewName)
            ? view($textViewName, $data)->render()
            : strip_tags($html);

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => trim($text),
        ];
    }

    /**
     * Preview an email template with mock/sample data.
     */
    public function preview(string $typeName, array $customInput = []): array
    {
        $type = EmailType::from($typeName);
        $mockData = $this->getMockDataForType($type);

        $custom = null;
        if (!empty($customInput)) {
            $custom = new EmailTemplate([
                'type' => $type,
                'subject' => $customInput['subject'] ?? null,
                'heading' => $customInput['heading'] ?? null,
                'intro' => $customInput['intro'] ?? null,
                'body_html' => $customInput['body_html'] ?? null,
                'show_item_images' => (bool) ($customInput['show_item_images'] ?? true),
            ]);
        }

        return $this->render($type, $mockData, $custom);
    }

    /**
     * Send a test email to a given recipient.
     */
    public function sendTestEmail(string $typeName, string $recipient): bool
    {
        $type = EmailType::from($typeName);
        $mockData = $this->getMockDataForType($type);
        $rendered = $this->render($type, $mockData);

        try {
            $fromAddress = config('mail.from.address', 'info@dhansree.com');
            $fromName = config('mail.from.name', 'Dhansree');
            $subject = '[TEST] ' . $rendered['subject'];

            Mail::send([], [], function ($message) use ($recipient, $rendered, $subject, $fromAddress, $fromName) {
                $message->to($recipient)
                    ->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($subject)
                    ->html($rendered['html'])
                    ->text($rendered['text']);
            });

            return true;
        } catch (Throwable $e) {
            Log::error("Failed to send test email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Replace {{placeholder}} tokens.
     */
    protected function fillPlaceholders(string $text, array $values): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function ($matches) use ($values) {
            $token = strtolower($matches[1]);
            return isset($values[$token]) ? (string) $values[$token] : '';
        }, $text);
    }

    /**
     * Build dictionary of available placeholder tokens from data.
     */
    protected function buildReplacements(EmailType $type, array $data): array
    {
        $store = $data['store'] ?? StoreSettings::current();
        $order = $data['order'] ?? null;
        $subscriber = $data['subscriber'] ?? null;

        $customerName = $order?->customer_name ?? $subscriber?->email ?? 'Valued Customer';
        $firstName = $this->extractFirstName($customerName);

        $replacements = [
            'store_name' => $store->store_name ?? 'Dhansree',
            'legal_name' => $store->legal_name ?? 'Dhanshree Trends',
            'support_email' => $store->support_email ?? 'info@dhansree.com',
            'support_phone' => $store->support_phone ?? '+919887776777',
            'site_url' => url('/'),
            'orders_url' => url('/account/orders'),
            'customer_name' => $customerName,
            'customer_first_name' => $firstName,
        ];

        if ($order instanceof Order) {
            $replacements['order_number'] = $order->order_number;
            $replacements['order_date'] = $order->placed_at ? $order->placed_at->format('d M Y') : now()->format('d M Y');
            $replacements['payment_method'] = $order->payment_method->value === 'COD' ? 'Cash on Delivery' : 'Online Payment';
            $replacements['subtotal'] = '₹' . number_format($order->subtotal_paise / 100, 2);
            $replacements['discount'] = $order->discount_paise > 0 ? '₹' . number_format($order->discount_paise / 100, 2) : '₹0.00';
            $replacements['coupon_code'] = $order->coupon_code ?? '';
            $replacements['delivery'] = $order->shipping_paise === 0 ? 'FREE' : '₹' . number_format($order->shipping_paise / 100, 2);
            $replacements['gst'] = '₹' . number_format($order->tax_paise / 100, 2);
            $replacements['total'] = '₹' . number_format($order->total_paise / 100, 2);
        }

        if (isset($data['shipment'])) {
            $replacements['courier_name'] = $data['shipment']->courier_name ?? 'Courier Partner';
            $replacements['tracking_number'] = $data['shipment']->awb ?? '';
            $replacements['tracking_url'] = $data['shipment']->tracking_url ?? '';
        }

        return $replacements;
    }

    protected function getViewNameForType(EmailType $type): string
    {
        return match ($type) {
            EmailType::ORDER_CONFIRMATION => 'emails.order-confirmation',
            EmailType::ORDER_CANCELLED => 'emails.order-cancelled',
            EmailType::PAYMENT_CONFIRMATION => 'emails.payment-confirmation',
            EmailType::PAYMENT_FAILED => 'emails.payment-failed',
            EmailType::SHIPMENT_DISPATCHED => 'emails.shipment-dispatched',
            EmailType::SHIPMENT_DELIVERED => 'emails.shipment-delivered',
            EmailType::NEWSLETTER_WELCOME => 'emails.newsletter-welcome',
            EmailType::NEWSLETTER_CONFIRM => 'emails.newsletter-confirm',
            EmailType::LOGIN_CODE => 'emails.login-code',
        };
    }

    protected function getTextViewNameForType(EmailType $type): string
    {
        return match ($type) {
            EmailType::ORDER_CONFIRMATION => 'emails.text.order-confirmation',
            EmailType::ORDER_CANCELLED => 'emails.text.order-cancelled',
            EmailType::PAYMENT_CONFIRMATION => 'emails.text.payment-confirmation',
            EmailType::PAYMENT_FAILED => 'emails.text.payment-failed',
            EmailType::SHIPMENT_DISPATCHED => 'emails.text.shipment-dispatched',
            EmailType::SHIPMENT_DELIVERED => 'emails.text.shipment-delivered',
            EmailType::NEWSLETTER_WELCOME => 'emails.text.newsletter-welcome',
            EmailType::NEWSLETTER_CONFIRM => 'emails.text.newsletter-confirm',
            EmailType::LOGIN_CODE => 'emails.text.login-code',
        };
    }

    protected function fallbackSubject(EmailType $type): string
    {
        return match ($type) {
            EmailType::ORDER_CONFIRMATION => 'Order confirmed',
            EmailType::PAYMENT_CONFIRMATION => 'Payment received',
            EmailType::PAYMENT_FAILED => 'Payment not completed',
            EmailType::ORDER_CANCELLED => 'Order cancelled',
            EmailType::SHIPMENT_DISPATCHED => 'Your order is on its way',
            EmailType::SHIPMENT_DELIVERED => 'Your order has been delivered',
            EmailType::NEWSLETTER_WELCOME => 'Welcome to Dhansree',
            EmailType::NEWSLETTER_CONFIRM => 'Confirm your subscription',
            EmailType::LOGIN_CODE => 'Your Dhansree sign-in code',
        };
    }

    protected function extractFirstName(?string $name): string
    {
        if (empty($name)) {
            return 'there';
        }
        $parts = preg_split('/\s+/', trim($name));
        return !empty($parts[0]) ? $parts[0] : 'there';
    }

    /**
     * Provide realistic mock data for admin previews and tests.
     */
    protected function getMockDataForType(EmailType $type): array
    {
        $store = StoreSettings::current();

        // Try to fetch latest real order or create mock object
        $order = Order::with(['items.variant.product.images', 'shippingAddress'])->latest()->first();

        if (!$order) {
            $order = new Order([
                'id' => 101,
                'order_number' => 'DS-SAMPLE-8821',
                'customer_name' => 'Priya Sharma',
                'customer_email' => 'priya.sharma@example.com',
                'customer_phone' => '+919876543210',
                'subtotal_paise' => 189900,
                'discount_paise' => 10000,
                'coupon_code' => 'FESTIVE10',
                'shipping_paise' => 0,
                'tax_paise' => 9000,
                'total_paise' => 179900,
                'payment_method' => \App\Enums\PaymentMethod::COD,
                'payment_status' => \App\Enums\PaymentStatus::PENDING,
                'placed_at' => now(),
                'prices_include_tax' => true,
            ]);
        }

        $subscriber = NewsletterSubscriber::first() ?? new NewsletterSubscriber([
            'id' => 1,
            'email' => 'customer@example.com',
            'unsubscribe_token' => 'sample-test-unsubscribe-token-12345',
        ]);

        $shipment = Shipment::where('order_id', $order->id)->first() ?? new Shipment([
            'id' => 1,
            'order_id' => $order->id,
            'courier_name' => 'Blue Dart Express',
            'awb' => 'BLUEDART-8829102910',
            'tracking_url' => 'https://track.bluedart.com',
        ]);

        return [
            'order' => $order,
            'shipment' => $shipment,
            'subscriber' => $subscriber,
            'store' => $store,
            'customerFirstName' => 'Priya',
            'awaitingPayment' => false,
            'refundDue' => true,
            'reason' => 'Customer requested cancellation prior to dispatch.',
            'amountPaise' => $order->total_paise,
            'couponCode' => 'WELCOME50',
            'reward' => '₹50 off on orders above ₹799',
            'code' => '482910',
            'minutes' => 10,
            'hours' => 24,
            'confirmUrl' => url('/newsletter/confirm?token=sample-test-token'),
            'unsubscribeUrl' => url('/newsletter/unsubscribe?token=' . ($subscriber->unsubscribe_token ?? '')),
            'actionUrl' => $type === EmailType::ORDER_CANCELLED ? url('/') : url('/account/orders/' . $order->order_number),
            'actionLabel' => $type === EmailType::ORDER_CANCELLED ? 'Browse Dhansree Store' : 'View Your Order',
        ];
    }
}
