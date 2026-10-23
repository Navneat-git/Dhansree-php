@extends('emails.text.layout')

@section('content')
Sign-in Verification Code

Your Dhansree sign-in verification code is:
{{ $code }}

It is valid for {{ $minutes ?? 10 }} minutes and can be used once.

If you did not request this sign-in code, you can safely ignore this email. Nobody can sign in without this code.
@endsection
