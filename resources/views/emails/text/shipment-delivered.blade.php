@extends('emails.text.layout')

@section('content')
{{ $heading ?? ('Order ' . $order->order_number . ' has been delivered') }}

Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},

{{ $intro ?? ('Your order ' . $order->order_number . ' has been delivered. We hope you like what you received!') }}

Delivered on: {{ now()->format('d M Y') }}

If anything is not right, reply to this email or write to {{ $store->support_email ?? 'info@dhansree.com' }}.

View your order & leave a review: {{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}
@endsection
