@extends('emails.text.layout')

@section('content')
Confirm Your Subscription

Please confirm you would like to receive emails from Dhansree Trends.
This link works for {{ $hours ?? 24 }} hours.

Confirm subscription: {{ $confirmUrl ?? url('/') }}

If that was not you, ignore this email — nothing more will be sent, and nothing has been added to any list.
@endsection
