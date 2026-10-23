@extends('emails.text.layout')

@section('content')
{{ $heading ?? 'Payment Received' }}

Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},

{{ $intro ?? ('We have received your payment of Rs. ' . number_format($order->total_paise / 100, 2) . ' for order ' . $order->order_number . '. Your order is confirmed.') }}

Order:  {{ $order->order_number }}
Amount: Rs. {{ number_format($order->total_paise / 100, 2) }}
Status: PAID

View your order: {{ $actionUrl ?? url('/account/orders/' . $order->order_number) }}
@endsection
