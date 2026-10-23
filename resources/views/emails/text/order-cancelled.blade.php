@extends('emails.text.layout')

@section('content')
{{ $heading ?? ('Order ' . $order->order_number . ' has been cancelled') }}

Hello {{ $customerFirstName ?? ($order->customer_name ? explode(' ', trim($order->customer_name))[0] : 'there') }},

{{ $intro ?? ('Order ' . $order->order_number . ' has been cancelled and nothing will be sent.') }}

ORDER & REFUND STATUS
--------------------------------------------------
Order Number: {{ $order->order_number }}
@if($refundDue ?? ($order->payment_status->value === 'PAID'))
Any payment already received (Rs. {{ number_format($order->total_paise / 100, 2) }}) is being refunded to your original payment method. Depending on your bank, refunds usually reflect within 5-7 working days.
@else
No payment was collected for this order as it was placed under Cash on Delivery.
@endif
@if(!empty($reason))
Reason: {{ $reason }}
@endif

If you have any questions or did not request this, please contact us at {{ $store->support_email ?? 'info@dhansree.com' }}.

Browse our store: {{ $actionUrl ?? url('/') }}
@endsection
