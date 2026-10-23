@extends('emails.text.layout')

@section('content')
{{ $heading ?? ('Order ' . $order->order_number . ' is on its way') }}

Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},

{{ $intro ?? ('Your order ' . $order->order_number . ' has been packed and handed over to our delivery partner.') }}

TRACKING DETAILS
--------------------------------------------------
Order:      {{ $order->order_number }}
Courier:    {{ $shipment->courier_name ?? 'Courier Partner' }}
AWB:        {{ $shipment->awb ?? 'Assigned soon' }}
@if($order->payment_method->value === 'COD')
COD Amount: Rs. {{ number_format($order->total_paise / 100, 2) }}
@endif

Track parcel: {{ !empty($shipment->tracking_url) ? $shipment->tracking_url : url('/tracking?order_number=' . $order->order_number) }}
@endsection
