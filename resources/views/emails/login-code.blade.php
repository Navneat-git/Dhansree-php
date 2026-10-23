@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#5c061c;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;text-align:center;">
    Sign-in Verification Code
</h1>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#1c1917;text-align:center;">
    Use the 6-digit one-time password below to verify your account and sign in to Dhansree:
</p>

<!-- OTP Code Box -->
<div style="background-color:#fbf8f2;border:2px solid #5c061c;border-radius:12px;padding:20px;text-align:center;margin:0 auto 24px;max-width:320px;">
    <span style="font-size:36px;font-weight:800;letter-spacing:10px;color:#5c061c;font-family:monospace;display:inline-block;padding-left:10px;">
        {{ $code }}
    </span>
</div>

<p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#78716c;text-align:center;">
    This code is valid for <strong>{{ $minutes ?? 10 }} minutes</strong> and can only be used once.
</p>

<p style="margin:0;font-size:12px;line-height:1.6;color:#a8a29e;text-align:center;">
    If you did not request this verification code, please ignore this email. Your account remains secure and no one can sign in without this code.
</p>
@endsection
