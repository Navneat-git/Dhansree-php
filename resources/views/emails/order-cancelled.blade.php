@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#991b1b;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;">
    {{ $heading ?? ('Order ' . $order->order_number . ' has been cancelled') }}
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;">
    Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},
</p>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#44403c;">
    {{ $intro ?? ('Order ' . $order->order_number . ' has been cancelled and nothing will be sent.') }}
</p>

<!-- Cancellation Notice Card -->
<div style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#991b1b;">
        Order &amp; Refund Status
    </p>
    <p style="margin:0;font-size:13px;line-height:1.6;color:#7f1d1d;">
        @if($refundDue ?? ($order->payment_status->value === 'PAID'))
            Any payment already received (₹{{ number_format($order->total_paise / 100, 2) }}) is being refunded to your original payment method. Depending on your bank or card issuer, refunds typically appear within 5 to 7 business days.
        @else
            No payment was collected for this order as it was placed under Cash on Delivery.
        @endif
    </p>
    @if(!empty($reason))
        <p style="margin:10px 0 0;font-size:13px;color:#991b1b;">
            <strong>Reason:</strong> {{ $reason }}
        </p>
    @endif
</div>

<p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#78716c;">
    If you did not request this cancellation or have any questions about this order, please feel free to reply directly to this email or contact us at <a href="mailto:{{ $store->support_email ?? 'info@dhansree.com' }}" style="color:#5c061c;text-decoration:underline;">{{ $store->support_email ?? 'info@dhansree.com' }}</a>.
</p>

<!-- CTA Button -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
    <tr>
        <td align="center">
            <a href="{{ $actionUrl ?? url('/') }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                {{ $actionLabel ?? 'Browse Dhansree Store' }}
            </a>
        </td>
    </tr>
</table>
@endsection
