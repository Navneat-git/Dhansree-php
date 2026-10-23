@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#5c061c;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;text-align:center;">
    Welcome to Dhansree!
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;text-align:center;">
    Thank you for subscribing &bull; you're officially part of the Dhansree circle.
</p>

<p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#57534e;text-align:center;">
    We believe in timeless ethnic elegance, authentic fabrics, and modern smart living. We will only write to share genuine style notes, new collections, and special offers.
</p>

@if(!empty($couponCode))
<!-- Exclusive Voucher Card -->
<div style="background-color:#fbf8f2;border:2px dashed #d4af37;border-radius:12px;padding:24px;text-align:center;margin:0 auto 28px;max-width:440px;">
    <span style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#854d0e;display:block;margin-bottom:6px;">
        Your Welcome Gift
    </span>
    <h3 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#5c061c;">
        {{ $reward ?? '₹50 Off On Your First Order' }}
    </h3>
    <div style="background-color:#ffffff;border:1px solid #d4af37;border-radius:8px;padding:10px 16px;display:inline-block;margin-bottom:10px;">
        <span style="font-size:24px;font-weight:800;letter-spacing:4px;color:#5c061c;font-family:monospace;">
            {{ $couponCode }}
        </span>
    </div>
    <p style="margin:0;font-size:12px;color:#78716c;">
        Apply at checkout on orders above ₹799. Valid for 30 days.
    </p>
</div>
@endif

<!-- CTA Button -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td align="center">
            <a href="{{ url('/') }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                Explore New Arrivals
            </a>
        </td>
    </tr>
</table>

<div style="border-top:1px solid #e7e5e4;padding-top:16px;text-align:center;">
    <p style="margin:0;font-size:12px;line-height:1.6;color:#a8a29e;">
        You received this email because you subscribed to updates at dhansree.com.<br>
        Changed your mind? You can <a href="{{ $unsubscribeUrl ?? url('/newsletter/unsubscribe?token=' . ($subscriber->unsubscribe_token ?? '')) }}" style="color:#78716c;text-decoration:underline;">unsubscribe at any time</a> with one click.
    </p>
</div>
@endsection
