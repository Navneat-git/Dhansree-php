@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#5c061c;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;">
    {{ $heading ?? ('Order ' . $order->order_number . ' is on its way') }}
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;">
    Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},
</p>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#44403c;">
    {{ $intro ?? ('Great news! Your order ' . $order->order_number . ' has been packed and handed over to our delivery partner.') }}
</p>

<!-- Courier / Tracking Details -->
<div style="background-color:#fbfaf8;border:1px solid #e7e5e4;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1c1917;">
        Shipment &amp; Tracking Information
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        @if(!empty($shipment->courier_name))
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#78716c;">Courier Partner:</td>
            <td style="padding:4px 0;font-size:13px;color:#1c1917;font-weight:600;text-align:right;">{{ $shipment->courier_name }}</td>
        </tr>
        @endif
        @if(!empty($shipment->awb))
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#78716c;">Tracking Number (AWB):</td>
            <td style="padding:4px 0;font-size:13px;color:#1c1917;font-weight:700;text-align:right;font-family:monospace;">{{ $shipment->awb }}</td>
        </tr>
        @endif
        @if($order->payment_method->value === 'COD')
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#78716c;">Cash to Collect on Delivery:</td>
            <td style="padding:4px 0;font-size:13px;color:#991b1b;font-weight:700;text-align:right;">₹{{ number_format($order->total_paise / 100, 2) }}</td>
        </tr>
        @endif
    </table>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
    <tr>
        <td align="center">
            @php
                $trackingUrl = !empty($shipment->tracking_url) ? $shipment->tracking_url : url('/tracking?order_number=' . $order->order_number . '&phone=' . urlencode($order->customer_phone));
            @endphp
            <a href="{{ $trackingUrl }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                Track Your Parcel
            </a>
        </td>
    </tr>
</table>
@endsection
