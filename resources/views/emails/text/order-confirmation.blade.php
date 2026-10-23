@extends('emails.text.layout')

@section('content')
{{ $heading ?? ($awaitingPayment ? 'We have your order' : 'Order confirmed') }}

Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},

{{ $intro ?? ($awaitingPayment ? 'Complete payment to confirm your order. Nothing has been charged yet, and your items are held for you.' : 'Thank you for your order! We have received it and will notify you as soon as it is on its way.') }}

ORDER DETAILS
--------------------------------------------------
Order Number:   {{ $order->order_number }}
Date Placed:    {{ $order->placed_at ? $order->placed_at->format('d M Y, h:i A') : now()->format('d M Y') }}
Payment Method: {{ $order->payment_method->value === 'COD' ? 'Cash on Delivery' : 'Online Payment' }}

ITEMS ORDERED
--------------------------------------------------
@foreach($order->items as $item)
* {{ $item->product_name }}{{ !empty($item->variant_title) && $item->variant_title !== 'Default' ? ' (' . $item->variant_title . ')' : '' }}
  Qty: {{ $item->quantity }} x Rs. {{ number_format($item->unit_price_paise / 100, 2) }} = Rs. {{ number_format($item->line_total_paise / 100, 2) }}
@endforeach

PRICE BREAKDOWN
--------------------------------------------------
Subtotal:       Rs. {{ number_format($order->subtotal_paise / 100, 2) }}
@if($order->discount_paise > 0)
Discount:      -Rs. {{ number_format($order->discount_paise / 100, 2) }}{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}
@endif
Delivery:       {{ $order->shipping_paise === 0 ? 'FREE' : 'Rs. ' . number_format($order->shipping_paise / 100, 2) }}
@if(!$order->prices_include_tax && $order->tax_paise > 0)
GST:            Rs. {{ number_format($order->tax_paise / 100, 2) }}
@endif
Total:          Rs. {{ number_format($order->total_paise / 100, 2) }}
@if($order->prices_include_tax && $order->tax_paise > 0)
(Includes GST of Rs. {{ number_format($order->tax_paise / 100, 2) }})
@endif

@php
    $shipping = $order->shippingAddress ?? $order->addresses()->where('type', 'SHIPPING')->first();
@endphp
@if($shipping)
DELIVERY ADDRESS
--------------------------------------------------
{{ $shipping->recipient_name }}
{{ $shipping->line1 }}
@if($shipping->line2){{ $shipping->line2 }}
@endif
@if($shipping->landmark)Landmark: {{ $shipping->landmark }}
@endif
{{ $shipping->city }}, {{ $shipping->state_name ?? $shipping->state_code }} {{ $shipping->postal_code }}
Phone: {{ $shipping->phone }}
@endif

{{ $actionLabel ?? ($awaitingPayment ? 'Complete Payment' : 'View Your Order') }}:
{{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}
@endsection
