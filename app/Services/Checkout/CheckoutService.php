<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\GstRegistrationType;
use App\Enums\GstSupplyType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Payments\RazorpayService;
use App\Support\Commerce\IndianStates;
use App\Support\Commerce\OrderNumber;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        protected TaxService $taxService,
        protected CouponService $couponService,
        protected InventoryReservationService $reservationService,
        protected RazorpayService $razorpayService,
    ) {}

    /**
     * Compute a verified quotation for a checkout cart.
     *
     * @param array<int, array{variant_id: int, quantity: int}> $cartLines
     * @return array{
     *     items: array<int, array{
     *         variant: ProductVariant,
     *         quantity: int,
     *         unit_price_paise: int,
     *         line_subtotal_paise: int,
     *         gst_rate_bps: int,
     *         taxable_paise: int,
     *         tax_paise: int
     *     }>,
     *     item_count: int,
     *     subtotal_paise: int,
     *     subtotal_rupees: float,
     *     discount_paise: int,
     *     discount_rupees: float,
     *     coupon: \App\Models\Coupon|null,
     *     shipping_paise: int,
     *     shipping_rupees: float,
     *     is_free_shipping: bool,
     *     supply_type: GstSupplyType,
     *     taxable_paise: int,
     *     cgst_paise: int,
     *     sgst_paise: int,
     *     igst_paise: int,
     *     tax_paise: int,
     *     total_paise: int,
     *     total_rupees: float
     * }
     */
    public function quote(
        array $cartLines,
        ?string $shippingPostalCode = null,
        ?string $shippingStateCode = null,
        ?string $couponCode = null,
        string $paymentMethod = 'COD',
        ?Customer $customer = null
    ): array {
        if (empty($cartLines)) {
            throw new InvalidArgumentException('Shopping bag is empty.');
        }

        $variantIds = array_column($cartLines, 'variant_id');
        $variants = ProductVariant::with(['product.images', 'product.primaryImage', 'optionValues', 'inventoryLevels'])
            ->whereIn('id', $variantIds)
            ->whereNull('archived_at')
            ->get()
            ->keyBy('id');

        $store = StoreSettings::current();
        $supplyType = $this->taxService->resolveSupplyType($store, $shippingStateCode);

        $items = [];
        $subtotalPaise = 0;
        $itemCount = 0;

        foreach ($cartLines as $line) {
            $variantId = (int) $line['variant_id'];
            $qty = max(1, (int) $line['quantity']);

            $variant = $variants->get($variantId);
            if (!$variant || !$variant->product || $variant->product->status->value !== 'ACTIVE') {
                throw new InvalidArgumentException('One or more products in your bag are no longer available.');
            }

            $availableStock = $variant->stock_on_hand;
            if ($availableStock < $qty) {
                throw new InvalidArgumentException("Insufficient stock for '{$variant->product->name}'. Only {$availableStock} left.");
            }

            $pricePaise = (int) $variant->price_paise;
            $lineSubtotal = $pricePaise * $qty;
            $subtotalPaise += $lineSubtotal;
            $itemCount += $qty;

            $items[] = [
                'variant' => $variant,
                'quantity' => $qty,
                'image_url' => $variant->product->primary_image_url ?? $variant->product->images->first()?->url,
                'unit_price_paise' => $pricePaise,
                'line_subtotal_paise' => $lineSubtotal,
                'gst_rate_bps' => (int) ($variant->product->gst_rate_bps ?? 500),
            ];
        }

        // Coupon resolution: auto-apply default coupon if no code explicitly specified
        $coupon = null;
        $discountPaise = 0;
        $isAutoCoupon = false;

        if ($couponCode === null) {
            $defaultCoupon = $store->getDefaultCoupon();
            if ($defaultCoupon) {
                $couponCode = $defaultCoupon->code;
                $isAutoCoupon = true;
            }
        }

        if (!empty($couponCode)) {
            try {
                $couponResult = $this->couponService->validateAndCalculate(
                    $couponCode,
                    $subtotalPaise,
                    $customer?->id,
                    $customer?->email
                );
                $coupon = $couponResult['coupon'];
                $discountPaise = $couponResult['discount_paise'];
            } catch (InvalidArgumentException $e) {
                if (!$isAutoCoupon) {
                    throw $e;
                }
                $coupon = null;
                $discountPaise = 0;
            }
        }

        // Shipping resolution
        $freeShippingThreshold = $store->free_shipping_threshold_paise !== null ? (int) $store->free_shipping_threshold_paise : null;
        $flatShipping = (int) ($store->shipping_flat_rate_paise ?? 5000);
        $isFreeShipping = ($freeShippingThreshold !== null && $subtotalPaise >= $freeShippingThreshold);
        $shippingPaise = $isFreeShipping ? 0 : $flatShipping;

        // Discounted taxable calculations per line
        $itemsTaxable = 0;
        $itemsCgst = 0;
        $itemsSgst = 0;
        $itemsIgst = 0;
        $itemsTax = 0;

        $processedItems = [];
        foreach ($items as $entry) {
            // Allocate discount proportionally across lines
            $lineShare = $subtotalPaise > 0 ? ($entry['line_subtotal_paise'] / $subtotalPaise) : 0;
            $lineDiscount = (int) round($discountPaise * $lineShare);
            $lineDiscountedAmount = max(0, $entry['line_subtotal_paise'] - $lineDiscount);

            $taxRes = $this->taxService->calculateLineTax(
                $lineDiscountedAmount,
                $entry['gst_rate_bps'],
                $supplyType,
                $store->prices_include_tax ?? true
            );

            $itemsTaxable += $taxRes['taxable_paise'];
            $itemsCgst += $taxRes['cgst_paise'];
            $itemsSgst += $taxRes['sgst_paise'];
            $itemsIgst += $taxRes['igst_paise'];
            $itemsTax += $taxRes['tax_paise'];

            $processedItems[] = array_merge($entry, [
                'discount_paise' => $lineDiscount,
                'taxable_paise' => $taxRes['taxable_paise'],
                'cgst_paise' => $taxRes['cgst_paise'],
                'sgst_paise' => $taxRes['sgst_paise'],
                'igst_paise' => $taxRes['igst_paise'],
                'tax_paise' => $taxRes['tax_paise'],
                'line_total_paise' => $lineDiscountedAmount,
            ]);
        }

        // Composite supply tax for delivery follows the highest item GST rate
        $shippingRateBps = !empty($items) ? max(array_column($items, 'gst_rate_bps')) : 1800;
        $shippingTax = $this->taxService->calculateLineTax(
            $shippingPaise,
            $shippingRateBps,
            $supplyType,
            $store->prices_include_tax ?? true
        );

        $finalTaxablePaise = $itemsTaxable + $shippingTax['taxable_paise'];
        $finalCgstPaise = $itemsCgst + $shippingTax['cgst_paise'];
        $finalSgstPaise = $itemsSgst + $shippingTax['sgst_paise'];
        $finalIgstPaise = $itemsIgst + $shippingTax['igst_paise'];
        $finalTaxPaise = $finalCgstPaise + $finalSgstPaise + $finalIgstPaise;
        $totalPaise = $finalTaxablePaise + $finalTaxPaise;

        return [
            'items' => $processedItems,
            'item_count' => $itemCount,
            'subtotal_paise' => $subtotalPaise,
            'subtotal_rupees' => round($subtotalPaise / 100, 2),
            'discount_paise' => $discountPaise,
            'discount_rupees' => round($discountPaise / 100, 2),
            'coupon' => $coupon,
            'shipping_paise' => $shippingPaise,
            'shipping_rupees' => round($shippingPaise / 100, 2),
            'shipping_gst_rate_bps' => $shippingRateBps,
            'shipping_taxable_paise' => $shippingTax['taxable_paise'],
            'shipping_tax_paise' => $shippingTax['tax_paise'],
            'is_free_shipping' => $isFreeShipping,
            'supply_type' => $supplyType,
            'taxable_paise' => $finalTaxablePaise,
            'cgst_paise' => $finalCgstPaise,
            'sgst_paise' => $finalSgstPaise,
            'igst_paise' => $finalIgstPaise,
            'tax_paise' => $finalTaxPaise,
            'total_paise' => $totalPaise,
            'total_rupees' => round($totalPaise / 100, 2),
        ];
    }

    /**
     * Place an order atomically inside a database transaction.
     *
     * @param array{
     *     contact: array{name: string, email: string, phone: string},
     *     shipping_address: array{
     *         recipient_name: string,
     *         phone: string,
     *         line1: string,
     *         line2?: string|null,
     *         landmark?: string|null,
     *         city: string,
     *         state_code: string,
     *         postal_code: string
     *     },
     *     payment_method: string,
     *     coupon_code?: string|null,
     *     customer_note?: string|null,
     *     cart_lines: array<int, array{variant_id: int, quantity: int}>
     * } $data
     * @return array{
     *     order: Order,
     *     razorpay_order: array|null
     * }
     */
    public function placeOrder(array $data, ?Customer $customer = null): array
    {
        return DB::transaction(function () use ($data, $customer) {
            $store = StoreSettings::current();
            $quote = $this->quote(
                $data['cart_lines'],
                $data['shipping_address']['postal_code'] ?? null,
                $data['shipping_address']['state_code'] ?? null,
                $data['coupon_code'] ?? null,
                $data['payment_method'],
                $customer
            );

            $orderNumber = OrderNumber::generate();
            $isCod = strtoupper($data['payment_method']) === 'COD';
            $paymentMethodEnum = $isCod ? PaymentMethod::COD : PaymentMethod::ONLINE;
            $initialOrderStatus = $isCod ? OrderStatus::CONFIRMED : OrderStatus::PENDING_PAYMENT;
            $initialPaymentStatus = PaymentStatus::PENDING;

            $now = now();
            $stateCode = $data['shipping_address']['state_code'];
            $stateName = IndianStates::getName($stateCode) ?? $stateCode;

            $coupon = $quote['coupon'];

            // Normalize customer phone to +91XXXXXXXXXX
            $rawPhone = preg_replace('/\D/', '', $data['contact']['phone']);
            $normalizedPhone = '+91' . substr($rawPhone, -10);

            $customerEmail = strtolower(trim($data['contact']['email']));
            $customer = $customer ?? \App\Models\Customer::where('email', $customerEmail)->first();

            // 1. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'checkout_key_hash' => hash('sha256', $orderNumber . microtime(true)),
                'request_hash' => hash('sha256', json_encode($data)),
                'customer_id' => $customer?->id,
                'status' => $initialOrderStatus,
                'payment_status' => $initialPaymentStatus,
                'payment_method' => $paymentMethodEnum,
                'customer_name' => $data['contact']['name'],
                'customer_email' => strtolower($data['contact']['email']),
                'customer_phone' => $normalizedPhone,
                'customer_note' => $data['customer_note'] ?? null,
                'currency' => $store->currency ?? 'INR',
                'prices_include_tax' => $store->prices_include_tax ?? true,
                'gst_registration_type' => $store->gst_registration_type ?? GstRegistrationType::REGULAR,
                'store_state_code' => $store->state_code ?? '08',
                'place_of_supply_state_code' => $stateCode,
                'supply_type' => $quote['supply_type'],
                'item_count' => $quote['item_count'],
                'subtotal_paise' => $quote['subtotal_paise'],
                'discount_paise' => $quote['discount_paise'],
                'shipping_paise' => $quote['shipping_paise'],
                'shipping_gst_rate_bps' => $quote['shipping_gst_rate_bps'],
                'shipping_taxable_paise' => $quote['shipping_taxable_paise'],
                'shipping_tax_paise' => $quote['shipping_tax_paise'],
                'taxable_paise' => $quote['taxable_paise'],
                'cgst_paise' => $quote['cgst_paise'],
                'sgst_paise' => $quote['sgst_paise'],
                'igst_paise' => $quote['igst_paise'],
                'tax_paise' => $quote['tax_paise'],
                'total_paise' => $quote['total_paise'],
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon ? $coupon->code : null,
                'coupon_type' => $coupon ? $coupon->type : null,
                'coupon_value' => $coupon ? (int) $coupon->value : null,
                'placed_at' => $now,
                'updated_at' => $now,
                'payment_due_at' => $isCod ? null : $now->copy()->addMinutes(30),
            ]);

            // 2. Create Order Address
            $rawShippingPhone = preg_replace('/\D/', '', $data['shipping_address']['phone']);
            $normalizedShippingPhone = '+91' . substr($rawShippingPhone, -10);

            OrderAddress::create([
                'order_id' => $order->id,
                'type' => 'SHIPPING',
                'recipient_name' => $data['shipping_address']['recipient_name'],
                'phone' => $normalizedShippingPhone,
                'line1' => $data['shipping_address']['line1'],
                'line2' => $data['shipping_address']['line2'] ?? null,
                'landmark' => $data['shipping_address']['landmark'] ?? null,
                'city' => $data['shipping_address']['city'],
                'state_code' => $stateCode,
                'state_name' => $stateName,
                'postal_code' => $data['shipping_address']['postal_code'],
                'country' => 'IN',
                'created_at' => $now,
            ]);

            // 3. Create Order Items
            foreach ($quote['items'] as $pos => $item) {
                /** @var ProductVariant $variant */
                $variant = $item['variant'];
                $optionNames = $variant->optionValues->pluck('value')->all();
                $variantTitle = !empty($optionNames) ? implode(' / ', $optionNames) : 'Standard';

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_title' => $variantTitle,
                    'option_values' => $variant->optionValues->pluck('value', 'id')->all(),
                    'sku' => $variant->sku,
                    'hsn_code' => $variant->product->hsn_code ?? '6204',
                    'quantity' => $item['quantity'],
                    'unit_price_paise' => $item['unit_price_paise'],
                    'mrp_paise' => (int) $variant->mrp_paise,
                    'discount_paise' => $item['discount_paise'],
                    'gst_rate_bps' => $item['gst_rate_bps'],
                    'taxable_paise' => $item['taxable_paise'],
                    'cgst_paise' => $item['cgst_paise'],
                    'sgst_paise' => $item['sgst_paise'],
                    'igst_paise' => $item['igst_paise'],
                    'tax_paise' => $item['tax_paise'],
                    'line_total_paise' => $item['line_total_paise'],
                    'position' => $pos,
                    'created_at' => $now,
                ]);
            }

            // 4. Reserve stock under inventory locks
            $this->reservationService->reserveForOrder($order, $data['cart_lines']);

            // 5. Redeem coupon if applied
            if ($coupon && $quote['discount_paise'] > 0) {
                $this->couponService->redeem(
                    $coupon,
                    $order->id,
                    $quote['discount_paise'],
                    $customer?->id,
                    $data['contact']['email']
                );
            }

            // 6. Record Order Status History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => $initialOrderStatus,
                'actor_type' => 'CUSTOMER',
                'created_at' => $now,
            ]);

            // 7. Create Payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethodEnum,
                'provider' => $isCod ? 'cod' : 'razorpay',
                'status' => $initialPaymentStatus,
                'amount_paise' => $quote['total_paise'],
                'currency' => 'INR',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 8. Online Payment (Razorpay Order creation)
            $razorpayOrder = null;
            if (!$isCod) {
                $razorpayOrder = $this->razorpayService->createOrder(
                    $quote['total_paise'],
                    $store->currency ?? 'INR',
                    $order->order_number,
                    ['order_id' => (string) $order->id]
                );

                $payment->update([
                    'provider_reference' => $razorpayOrder['id'],
                ]);
            }

            return [
                'order' => $order,
                'razorpay_order' => $razorpayOrder,
            ];
        });
    }
}
