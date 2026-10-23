@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#5c061c;font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;text-align:center;">
    Confirm Your Subscription
</h1>

<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1c1917;text-align:center;">
    Please confirm that you would like to receive updates, style guides, and exclusive offers from Dhansree Trends.
</p>

<p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#78716c;text-align:center;">
    This confirmation link is valid for {{ $hours ?? 24 }} hours.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td align="center">
            <a href="{{ $confirmUrl ?? url('/') }}"
               style="display:inline-block;background-color:#5c061c;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:0.5px;">
                Yes, Subscribe Me
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:12px;line-height:1.6;color:#a8a29e;text-align:center;">
    If you did not enter your email on our website, please ignore this email &bull; you will not be added to any mailing list.
</p>
@endsection
