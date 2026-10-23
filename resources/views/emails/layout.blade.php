<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? ($store->store_name ?? 'Dhansree') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#fbfaf8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;color:#1c1917;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fbfaf8;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    
                    <!-- Brand Header -->
                    <tr>
                        <td style="background-color:#5c061c;padding:24px 28px;text-align:center;border-bottom:3px solid #d4af37;">
                            <a href="{{ url('/') }}" style="text-decoration:none;display:inline-block;">
                                <p style="margin:0;font-size:24px;font-weight:700;letter-spacing:3px;color:#ffffff;font-family:'Cormorant Garamond',Georgia,serif;text-transform:uppercase;">
                                    {{ strtoupper($store->store_name ?? 'DHANSREE') }}
                                </p>
                                <p style="margin:4px 0 0;font-size:9px;letter-spacing:2px;color:#f3e8b5;text-transform:uppercase;font-weight:600;">
                                    Ethnic Elegance &bull; Timeless You
                                </p>
                            </a>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:32px 28px 24px;font-size:15px;line-height:1.6;color:#1c1917;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Brand Footer -->
                    <tr>
                        <td style="padding:20px 28px 24px;background-color:#fbfaf8;border-top:1px solid #e7e5e4;font-size:12px;line-height:1.7;color:#78716c;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin:0 0 6px;font-weight:600;color:#1c1917;">
                                            {{ $store->legal_name ?? 'Dhanshree Trends' }}
                                        </p>
                                        <p style="margin:0 0 6px;">
                                            Customer Care: <a href="mailto:{{ $store->support_email ?? 'info@dhansree.com' }}" style="color:#5c061c;text-decoration:underline;">{{ $store->support_email ?? 'info@dhansree.com' }}</a>
                                            @if(!empty($store->support_phone))
                                                &bull; Phone/WhatsApp: <a href="tel:{{ $store->support_phone }}" style="color:#5c061c;text-decoration:none;">{{ $store->support_phone }}</a>
                                            @endif
                                        </p>
                                        <p style="margin:0;color:#a8a29e;font-size:11px;">
                                            {{ $footerReason ?? 'You received this transactional email in relation to your activity on Dhansree.' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
