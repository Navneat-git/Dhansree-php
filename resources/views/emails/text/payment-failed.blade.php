@extends('emails.text.layout')

@section('content')
{{ $heading ?? ('Payment not completed for ' . $order->order_number) }}

Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},

{{ $intro ?? ('We could not take the payment for order ' . $order->order_number . '.') }}

Nothing has been charged. Your order is still held for you and you can try paying again from your order page.

Try paying again: {{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}
@endsection
