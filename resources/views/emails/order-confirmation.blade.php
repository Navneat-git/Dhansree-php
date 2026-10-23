@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#5c061c;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;">
    {{ $heading ?? ($awaitingPayment ? 'We have your order' : 'Order confirmed') }}
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;">
    Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},
</p>

<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#44403c;">
    {{ $intro ?? ($awaitingPayment ? 'Complete payment to confirm your order. Nothing has been charged yet, and your items are temporarily held for you.' : 'Thank you for your order! We have received it and will notify you as soon as it is packed and on its way.') }}
</p>

<!-- Order Metadata Card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fbfaf8;border:1px solid #e7e5e4;border-radius:8px;margin-bottom:24px;padding:12px 16px;">
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#78716c;">Order Number:</td>
        <td style="padding:6px 0;font-size:14px;color:#1c1917;font-weight:700;text-align:right;">{{ $order->order_number }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#78716c;">Order Date:</td>
        <td style="padding:6px 0;font-size:13px;color:#1c1917;text-align:right;">{{ $order->placed_at ? $order->placed_at->format('d M Y, h:i A') : now()->format('d M Y') }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0;font-size:13px;color:#78716c;">Payment Method:</td>
        <td style="padding:6px 0;font-size:13px;color:#1c1917;text-align:right;font-weight:600;">
            {{ $order->payment_method->value === 'COD' ? 'Cash on Delivery' : 'Online Payment (Razorpay)' }}
        </td>
    </tr>
</table>

<!-- Items Ordered -->
<h2 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#1c1917;border-bottom:1px solid #e7e5e4;padding-bottom:6px;">
    Items Ordered
</h2>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    @foreach($order->items as $item)
    @php
        $imgUrl = null;
        if (!empty($item->variant->product->primary_image_url)) {
            $imgUrl = $item->variant->product->primary_image_url;
        } elseif (!empty($item->variant->product->images) && $item->variant->product->images->first()) {
            $imgUrl = $item->variant->product->images->first()->url;
        }
        if ($imgUrl && !str_starts_with($imgUrl, 'http')) {
            $imgUrl = url($imgUrl);
        }
    @endphp
    <tr>
        @if(($showItemImages ?? true) && $imgUrl)
        <td width="72" style="padding:10px 12px 10px 0;vertical-align:top;width:72px;">
            <img src="{{ $imgUrl }}"
                 alt="{{ $item->product_name }}"
                 width="64"
                 height="64"
                 style="display:block;width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #e7e5e4;">
        </td>
        @endif
        <td style="padding:10px 0;font-size:14px;line-height:1.5;color:#1c1917;border-bottom:1px solid #f5f5f4;vertical-align:top;">
            <strong>{{ $item->product_name }}</strong>
            @if(!empty($item->variant_title) && $item->variant_title !== 'Default')
                <br><span style="color:#78716c;font-size:13px;">Option: {{ $item->variant_title }}</span>
            @endif
            <br><span style="color:#78716c;font-size:13px;">Qty: {{ $item->quantity }} &times; ₹{{ number_format($item->unit_price_paise / 100, 2) }}</span>
        </td>
        <td style="padding:10px 0;font-size:14px;font-weight:600;color:#1c1917;text-align:right;white-space:nowrap;border-bottom:1px solid #f5f5f4;vertical-align:top;">
            ₹{{ number_format($item->line_total_paise / 100, 2) }}
        </td>
    </tr>
    @endforeach
</table>

<!-- Price Breakdown -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;border-top:1px solid #e7e5e4;">
    <tr>
        <td style="padding:6px 0;font-size:14px;color:#78716c;">Subtotal</td>
        <td style="padding:6px 0;font-size:14px;color:#1c1917;text-align:right;">₹{{ number_format($order->subtotal_paise / 100, 2) }}</td>
    </tr>
    @if($order->discount_paise > 0)
    <tr>
        <td style="padding:6px 0;font-size:14px;color:#16a34a;">
            Discount {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}
        </td>
        <td style="padding:6px 0;font-size:14px;color:#16a34a;text-align:right;">
            -₹{{ number_format($order->discount_paise / 100, 2) }}
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding:6px 0;font-size:14px;color:#78716c;">Delivery</td>
        <td style="padding:6px 0;font-size:14px;color:#1c1917;text-align:right;">
            {{ $order->shipping_paise === 0 ? 'FREE' : '₹' . number_format($order->shipping_paise / 100, 2) }}
        </td>
    </tr>
    @if(!$order->prices_include_tax && $order->tax_paise > 0)
    <tr>
        <td style="padding:6px 0;font-size:14px;color:#78716c;">GST / Tax</td>
        <td style="padding:6px 0;font-size:14px;color:#1c1917;text-align:right;">₹{{ number_format($order->tax_paise / 100, 2) }}</td>
    </tr>
    @endif
    <tr style="border-top:2px solid #5c061c;">
        <td style="padding:10px 0;font-size:16px;font-weight:700;color:#5c061c;">Total Amount</td>
        <td style="padding:10px 0;font-size:18px;font-weight:700;color:#5c061c;text-align:right;">₹{{ number_format($order->total_paise / 100, 2) }}</td>
    </tr>
    @if($order->prices_include_tax && $order->tax_paise > 0)
    <tr>
        <td colspan="2" style="padding:2px 0;font-size:11px;color:#a8a29e;text-align:right;">
            (Includes GST of ₹{{ number_format($order->tax_paise / 100, 2) }})
        </td>
    </tr>
    @endif
</table>

<!-- Delivery Address -->
@php
    $shipping = $order->shippingAddress ?? $order->addresses()->where('type', 'SHIPPING')->first();
@endphp
@if($shipping)
<div style="background-color:#fbfaf8;border:1px solid #e7e5e4;border-radius:8px;padding:16px;margin-bottom:28px;">
    <h3 style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1c1917;text-transform:uppercase;letter-spacing:0.5px;">
        Delivery Address
    </h3>
    <p style="margin:0;font-size:13px;line-height:1.6;color:#57534e;">
        <strong>{{ $shipping->recipient_name }}</strong><br>
        {{ $shipping->line1 }}<br>
        @if($shipping->line2){{ $shipping->line2 }}<br>@endif
        @if($shipping->landmark)Landmark: {{ $shipping->landmark }}<br>@endif
        {{ $shipping->city }}, {{ $shipping->state_name ?? $shipping->state_code }} - {{ $shipping->postal_code }}<br>
        Phone: {{ $shipping->phone }}
    </p>
</div>
@endif

<!-- Action CTA Button -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
    <tr>
        <td align="center">
            <a href="{{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                {{ $actionLabel ?? ($awaitingPayment ? 'Complete Payment' : 'View Your Order') }}
            </a>
        </td>
    </tr>
</table>
@endsection
