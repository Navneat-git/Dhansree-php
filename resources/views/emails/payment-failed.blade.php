@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#991b1b;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;">
    {{ $heading ?? ('Payment not completed for ' . $order->order_number) }}
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;">
    Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},
</p>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#44403c;">
    {{ $intro ?? ('We could not process the payment of ₹' . number_format(($amountPaise ?? $order->total_paise) / 100, 2) . ' for order ' . $order->order_number . '.') }}
</p>

<div style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 6px;font-size:13px;color:#991b1b;font-weight:700;">No Money Has Been Charged</p>
    <p style="margin:0;font-size:13px;color:#7f1d1d;line-height:1.5;">
        Your selected items are still reserved for you in your account. You can easily complete payment or choose another payment method from your order page.
    </p>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
    <tr>
        <td align="center">
            <a href="{{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                {{ $actionLabel ?? 'Try Paying Again' }}
            </a>
        </td>
    </tr>
</table>
@endsection
