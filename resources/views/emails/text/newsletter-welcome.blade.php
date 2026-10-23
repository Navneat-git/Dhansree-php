@extends('emails.text.layout')

@section('content')
Welcome to Dhansree!

Thank you for subscribing — you're officially part of the Dhansree circle.

We believe in timeless ethnic elegance, authentic fabrics, and modern smart living. We will only write to share genuine style notes, new collections, and special offers.

@if(!empty($couponCode))
YOUR WELCOME GIFT:
{{ $reward ?? 'Rs. 50 Off On Your First Order' }}
Coupon Code: {{ $couponCode }}
(Apply at checkout on orders above Rs. 799)
@endif

Start Shopping: {{ url('/') }}

--------------------------------------------------
To unsubscribe, click the link below:
{{ $unsubscribeUrl ?? url('/newsletter/unsubscribe?token=' . ($subscriber->unsubscribe_token ?? '')) }}
@endsection
