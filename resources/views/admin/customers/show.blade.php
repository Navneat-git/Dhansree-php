@extends('layouts.admin')

@section('title', 'Customer: ' . $customer->name)

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.customers.index') }}" class="hover:text-amber-700">&larr; Back to Customers</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">#{{ $customer->id }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $customer->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                    @if($customer->status->value === 'ACTIVE') bg-emerald-50 text-emerald-700 border-emerald-200
                    @else bg-rose-50 text-rose-700 border-rose-200 @endif">
                    {{ $customer->status->value }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Account created on {{ $customer->created_at?->format('d M Y, h:i A') }}
            </p>
        </div>

        @if($canUpdate)
            <div>
                <form action="{{ route('admin.customers.status', $customer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to change this customer account status?')">
                    @csrf
                    @if($customer->status->value === 'ACTIVE')
                        <input type="hidden" name="status" value="DISABLED">
                        <button type="submit" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 text-xs font-semibold rounded-lg transition-colors shadow-2xs">
                            Deactivate Account
                        </button>
                    @else
                        <input type="hidden" name="status" value="ACTIVE">
                        <button type="submit" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-700 text-xs font-semibold rounded-lg transition-colors shadow-2xs">
                            Reactivate Account
                        </button>
                    @endif
                </form>
            </div>
        @endif
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Key Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Total Orders</span>
            <span class="text-2xl font-bold text-slate-900 tabular-nums mt-1 block">{{ $orderCount }}</span>
        </div>
        <div class="p-5 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Total Spent</span>
            <span class="text-2xl font-bold text-slate-900 tabular-nums mt-1 block">₹{{ number_format($totalSpentPaise / 100, 2) }}</span>
        </div>
        <div class="p-5 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Reviews Written</span>
            <span class="text-2xl font-bold text-slate-900 tabular-nums mt-1 block">{{ $reviewCount }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main: Recent Orders & Addresses -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Orders Card -->
            <div class="rounded-xl bg-white border border-slate-200/80 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Recent Orders</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Orders placed by this registered customer.</p>
                    </div>
                </div>

                @if(empty($orders))
                    <div class="p-8 text-center text-slate-400 text-xs">
                        No orders recorded for this customer yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3">Order #</th>
                                    <th class="px-5 py-3">Placed Date</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Total</th>
                                    <th class="px-5 py-3 text-right"><span class="sr-only">Link</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($orders as $o)
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-5 py-3 font-mono font-bold text-amber-700">
                                            <a href="{{ $o['adminPath'] }}" class="hover:underline">
                                                {{ $o['orderNumber'] }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-slate-500">
                                            {{ \Carbon\Carbon::parse($o['placedAt'])->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-[0.65rem] font-semibold border
                                                @if($o['status'] === 'DELIVERED') bg-emerald-50 text-emerald-700 border-emerald-200
                                                @elseif($o['status'] === 'CANCELLED') bg-rose-50 text-rose-700 border-rose-200
                                                @else bg-amber-50 text-amber-800 border-amber-200 @endif">
                                                {{ $o['statusLabel'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right font-bold text-slate-900 tabular-nums">
                                            ₹{{ number_format($o['totalPaise'] / 100, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ $o['adminPath'] }}" class="text-amber-700 hover:text-amber-800 font-semibold">
                                                Open &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Saved Addresses Card -->
            <div class="rounded-xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Saved Addresses</h2>

                @if(empty($addresses))
                    <p class="text-xs text-slate-400">No saved addresses on file.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($addresses as $addr)
                            <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded-md font-bold text-[0.65rem] uppercase tracking-wider bg-slate-200 text-slate-700">
                                        {{ $addr['label'] }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        @if($addr['isDefaultShipping'])
                                            <span class="text-[0.65rem] text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                Default Ship
                                            </span>
                                        @endif
                                        @if($addr['isDefaultBilling'])
                                            <span class="text-[0.65rem] text-slate-600 font-semibold bg-slate-200 px-1.5 py-0.5 rounded">
                                                Default Bill
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-slate-700 space-y-0.5">
                                    <p class="font-bold text-slate-900 text-sm">{{ $addr['recipientName'] }}</p>
                                    <p>{{ $addr['line1'] }}</p>
                                    @if($addr['line2'])<p>{{ $addr['line2'] }}</p>@endif
                                    @if($addr['landmark'])<p class="text-slate-500">Near: {{ $addr['landmark'] }}</p>@endif
                                    <p>{{ $addr['city'] }}, {{ $addr['stateName'] }} - <strong class="font-mono text-slate-800">{{ $addr['postalCode'] }}</strong></p>
                                    <p class="pt-1 text-slate-500 font-mono">Mobile: {{ $addr['phone'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Customer Reviews Card -->
            <div class="rounded-xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Submitted Reviews</h2>
                @if(empty($reviews))
                    <p class="text-xs text-slate-400">This customer has not posted any reviews yet.</p>
                @else
                    <div class="divide-y divide-slate-200">
                        @foreach($reviews as $rev)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $rev['productName'] }}</p>
                                    <p class="text-slate-500 mt-0.5">{{ $rev['rating'] }} / 5 stars · {{ \Carbon\Carbon::parse($rev['createdAt'])->format('d M Y') }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[0.65rem] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $rev['status'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar: Contact & Auth Details -->
        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Contact & Identity</h2>

                <dl class="space-y-3 text-xs">
                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Email Address</dt>
                        <dd class="mt-1 text-slate-900 break-words">
                            <a href="mailto:{{ $customer->email }}" class="hover:text-amber-700 underline underline-offset-2">
                                {{ $customer->email }}
                            </a>
                            <span class="block text-[0.6875rem] text-slate-400 mt-0.5">
                                @if($customer->email_verified_at)
                                    Verified on {{ $customer->email_verified_at->format('d M Y') }}
                                @else
                                    Unverified
                                @endif
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Phone Number</dt>
                        <dd class="mt-1 text-slate-900 font-mono">
                            {{ $customer->phone ?: 'Not provided' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Account Registered</dt>
                        <dd class="mt-1 text-slate-700">
                            {{ $customer->created_at?->format('d M Y, h:i A') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Last Updated</dt>
                        <dd class="mt-1 text-slate-700">
                            {{ $customer->updated_at?->format('d M Y, h:i A') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Security & Sessions</h2>

                <dl class="space-y-3 text-xs">
                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Authentication Mechanism</dt>
                        <dd class="mt-1 text-slate-700">
                            Passwordless CSPRNG 6-digit OTP (Argon2id hashed)
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Last Sign In</dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $customer->last_login_at ? $customer->last_login_at->format('d M Y, h:i A') : 'Never' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">Active Browser Sessions</dt>
                        <dd class="mt-1 text-amber-700 font-bold tabular-nums">
                            {{ $activeSessions }} {{ $activeSessions === 1 ? 'device' : 'devices' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
