@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#15803d;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;">
    {{ $heading ?? ('Order ' . $order->order_number . ' has been delivered') }}
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;">
    Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},
</p>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#44403c;">
    {{ $intro ?? ('Your order ' . $order->order_number . ' has been successfully delivered. We hope you love what you received!') }}
</p>

<div style="background-color:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#15803d;">
        Delivery Completed
    </p>
    <p style="margin:0;font-size:13px;color:#166534;line-height:1.5;">
        Delivered on {{ now()->format('d M Y') }}. If anything is not as expected or if you have any questions, simply reply to this email or contact us at <a href="mailto:{{ $store->support_email ?? 'info@dhansree.com' }}" style="color:#15803d;text-decoration:underline;">{{ $store->support_email ?? 'info@dhansree.com' }}</a>.
    </p>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
    <tr>
        <td align="center">
            <a href="{{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                View Order &amp; Leave a Review
            </a>
        </td>
    </tr>
</table>
@endsection
