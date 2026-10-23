{{ strtoupper($store->store_name ?? 'DHANSREE') }}
Ethnic Elegance. Timeless You.
==================================================

@yield('content')

==================================================
{{ $store->legal_name ?? 'Dhanshree Trends' }}
Customer Care: {{ $store->support_email ?? 'info@dhansree.com' }}{{ !empty($store->support_phone) ? ' | Phone/WhatsApp: ' . $store->support_phone : '' }}
Website: {{ url('/') }}
{{ $footerReason ?? 'You received this transactional email in relation to your activity on Dhansree.' }}
