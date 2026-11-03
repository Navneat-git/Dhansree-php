<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\StoreSettings;
use App\Services\Checkout\CheckoutService;
use App\Services\Email\EmailService;
use App\Services\Payments\RazorpayService;
use App\Services\Storefront\CartService;
use App\Support\Commerce\IndianStates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected CartService $cartService,
        protected RazorpayService $razorpayService,
        protected EmailService $emailService,
    ) {}

    /**
     * Show Checkout Page
     */
    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            return redirect('/cart')->with('error', 'Your shopping bag is empty.');
        }

        $store = StoreSettings::current();
        $states = IndianStates::all();

        // Prepare line items for quote
        $cartLines = array_map(fn($item) => [
            'variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
        ], $cart['items']);

        $quote = $this->checkoutService->quote(
            $cartLines,
            null,
            $store->state_code ?? '08',
            null,
            'ONLINE'
        );

        return view('store.checkout', [
            'cart' => $cart,
            'states' => $states,
            'store' => $store,
            'quote' => $quote,
            'razorpayEnabled' => $this->razorpayService->isEnabled(),
            'razorpayKey' => $this->razorpayService->getKeyId(),
            'seoTitle' => 'Secure Checkout | Dhansree',
        ]);
    }

    /**
     * AJAX quote endpoint when postal code, state, or coupon changes.
     */
    public function quote(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            return response()->json(['error' => 'Shopping bag is empty.'], 422);
        }

        $cartLines = array_map(fn($item) => [
            'variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
        ], $cart['items']);

        $couponCode = $request->input('coupon_code');
        if ($request->has('coupon_code') && ($couponCode === null || trim((string) $couponCode) === '')) {
            $couponCode = '';
        }

        try {
            $quote = $this->checkoutService->quote(
                $cartLines,
                $request->input('postal_code'),
                $request->input('state_code'),
                $couponCode,
                $request->input('payment_method', 'ONLINE')
            );

            return response()->json([
                'success' => true,
                'quote' => $quote,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Place order form submission.
     */
    public function placeOrder(Request $request): JsonResponse|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Shopping bag is empty.'], 422);
            }
            return redirect('/cart')->with('error', 'Your shopping bag is empty.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state_code' => ['required', 'string', 'size:2'],
            'postal_code' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'payment_method' => ['required', 'in:COD,ONLINE'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'postal_code.regex' => 'Please enter a valid 6-digit Indian PIN code.',
        ]);

        $cartLines = array_map(fn($item) => [
            'variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
        ], $cart['items']);

        $payload = [
            'contact' => [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ],
            'shipping_address' => [
                'recipient_name' => $validated['name'],
                'phone' => $validated['phone'],
                'line1' => $validated['line1'],
                'line2' => $validated['line2'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
                'city' => $validated['city'],
                'state_code' => $validated['state_code'],
                'postal_code' => $validated['postal_code'],
            ],
            'payment_method' => $validated['payment_method'],
            'coupon_code' => $validated['coupon_code'] ?? null,
            'customer_note' => $validated['customer_note'] ?? null,
            'cart_lines' => $cartLines,
        ];

        try {
            $result = $this->checkoutService->placeOrder($payload);
            $order = $result['order'];

            // Clear session cart upon successful creation
            $this->cartService->clear();

            $confirmedUrl = url('/order/' . $order->order_number . '/confirmed');

            if ($validated['payment_method'] === 'COD') {
                // Send Order Confirmation Email asynchronously/safely
                try {
                    $this->emailService->sendOrderConfirmation($order, false);
                } catch (\Throwable $e) {
                    Log::warning("Could not send COD order confirmation email: {$e->getMessage()}");
                }

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'is_online' => false,
                        'redirect_url' => $confirmedUrl,
                    ]);
                }

                return redirect($confirmedUrl);
            }

            // Online Razorpay Payment flow
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_online' => true,
                    'order_number' => $order->order_number,
                    'razorpay_order' => $result['razorpay_order'],
                    'key_id' => $this->razorpayService->getKeyId(),
                    'amount_paise' => $order->total_paise,
                    'currency' => $order->currency,
                    'customer' => [
                        'name' => $order->customer_name,
                        'email' => $order->customer_email,
                        'phone' => $order->customer_phone,
                    ],
                    'redirect_url' => $confirmedUrl,
                ]);
            }

            return redirect($confirmedUrl);
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Order placement failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to process payment or place your order. Please try again or select Cash on Delivery.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->withInput()->with('error', $message);
        }
    }

    /**
     * Razorpay checkout signature verification callback.
     */
    public function razorpayVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])->firstOrFail();

        $verified = $this->razorpayService->verifyCheckoutSignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'Payment signature verification failed. Please contact support.',
            ], 422);
        }

        DB::transaction(function () use ($order, $validated) {
            $now = now();

            $order->update([
                'status' => OrderStatus::CONFIRMED,
                'payment_status' => PaymentStatus::PAID,
            ]);

            Payment::where('order_id', $order->id)->update([
                'status' => PaymentStatus::PAID,
                'provider_payment_id' => $validated['razorpay_payment_id'],
                'signature_verified_at' => $now,
                'paid_at' => $now,
                'confirmed_via' => 'CHECKOUT',
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => OrderStatus::PENDING_PAYMENT,
                'to_status' => OrderStatus::CONFIRMED,
                'actor_type' => 'CUSTOMER',
                'note' => 'Payment received via Razorpay (' . $validated['razorpay_payment_id'] . ')',
                'created_at' => $now,
            ]);
        });

        // Send Payment & Order Confirmation Emails
        try {
            $this->emailService->sendPaymentConfirmation($order);
            $this->emailService->sendOrderConfirmation($order, false);
        } catch (\Throwable $e) {
            Log::warning("Could not send online payment/order confirmation emails: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'redirect_url' => url('/order/' . $order->order_number . '/confirmed'),
        ]);
    }

    /**
     * Razorpay Webhook listener for asynchronous payment capture.
     */
    public function razorpayWebhook(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if (!$this->razorpayService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Razorpay webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($rawPayload, true);
        $eventName = $event['event'] ?? '';

        if ($eventName === 'payment.captured' || $eventName === 'order.paid') {
            $entity = $event['payload']['payment']['entity'] ?? [];
            $razorpayOrderId = $entity['order_id'] ?? null;
            $paymentId = $entity['id'] ?? null;

            if ($razorpayOrderId) {
                $payment = Payment::where('provider_reference', $razorpayOrderId)->first();
                if ($payment && $payment->status !== PaymentStatus::PAID) {
                    $order = $payment->order;
                    $now = now();

                    $payment->update([
                        'status' => PaymentStatus::PAID,
                        'provider_payment_id' => $paymentId,
                        'paid_at' => $now,
                        'confirmed_via' => 'WEBHOOK',
                    ]);

                    $order->update([
                        'status' => OrderStatus::CONFIRMED,
                        'payment_status' => PaymentStatus::PAID,
                    ]);

                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'from_status' => OrderStatus::PENDING_PAYMENT,
                        'to_status' => OrderStatus::CONFIRMED,
                        'actor_type' => 'SYSTEM',
                        'note' => "Payment confirmed via Razorpay webhook ({$paymentId})",
                        'created_at' => $now,
                    ]);

                    // Send Payment & Order Confirmation Emails
                    try {
                        $this->emailService->sendPaymentConfirmation($order);
                        $this->emailService->sendOrderConfirmation($order, false);
                    } catch (\Throwable $e) {
                        Log::warning("Could not send webhook payment confirmation email: {$e->getMessage()}");
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Order Confirmed Page
     */
    public function confirmation(string $orderNumber): View
    {
        $order = Order::with(['items', 'addresses', 'payments'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $shippingAddress = $order->shippingAddress ?? $order->addresses->first();

        return view('store.order-confirmed', [
            'order' => $order,
            'address' => $shippingAddress,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => "Order {$order->order_number} Confirmed | Dhansree",
        ]);
    }
}
