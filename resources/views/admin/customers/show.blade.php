@extends('layouts.admin')

@section('title', 'Customer: ' . $customer->name)

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                <a href="{{ route('admin.customers.index') }}" class="hover:text-amber-400">&larr; Back to Customers</a>
                <span>/</span>
                <span class="text-zinc-400">#{{ $customer->id }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-white">{{ $customer->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                    @if($customer->status->value === 'ACTIVE') bg-emerald-950/60 text-emerald-400 border-emerald-800
                    @else bg-rose-950/60 text-rose-400 border-rose-800 @endif">
                    {{ $customer->status->value }}
                </span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">
                Account created on {{ $customer->created_at?->format('d M Y, h:i A') }}
            </p>
        </div>

        @if($canUpdate)
            <div>
                <form action="{{ route('admin.customers.status', $customer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to change this customer account status?')">
                    @csrf
                    @if($customer->status->value === 'ACTIVE')
                        <input type="hidden" name="status" value="DISABLED">
                        <button type="submit" class="px-4 py-2 bg-rose-950/60 hover:bg-rose-900 border border-rose-800 text-rose-200 text-xs font-semibold rounded-xl transition-colors">
                            Deactivate Account
                        </button>
                    @else
                        <input type="hidden" name="status" value="ACTIVE">
                        <button type="submit" class="px-4 py-2 bg-emerald-950/60 hover:bg-emerald-900 border border-emerald-800 text-emerald-200 text-xs font-semibold rounded-xl transition-colors">
                            Reactivate Account
                        </button>
                    @endif
                </form>
            </div>
        @endif
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-200 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Key Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800/80">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 block">Total Orders</span>
            <span class="text-2xl font-bold text-white tabular-nums mt-1 block">{{ $orderCount }}</span>
        </div>
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800/80">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 block">Total Spent</span>
            <span class="text-2xl font-bold text-white tabular-nums mt-1 block">₹{{ number_format($totalSpentPaise / 100, 2) }}</span>
        </div>
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800/80">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 block">Reviews Written</span>
            <span class="text-2xl font-bold text-white tabular-nums mt-1 block">{{ $reviewCount }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main: Recent Orders & Addresses -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Orders Card -->
            <div class="rounded-2xl bg-zinc-900/80 border border-zinc-800/80 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-zinc-800/80 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Recent Orders</h2>
                        <p class="text-xs text-zinc-400 mt-0.5">Orders placed by this registered customer.</p>
                    </div>
                </div>

                @if(empty($orders))
                    <div class="p-8 text-center text-zinc-500 text-xs">
                        No orders recorded for this customer yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-zinc-300">
                            <thead class="bg-zinc-950/60 text-zinc-500 uppercase tracking-wider font-semibold border-b border-zinc-800/80">
                                <tr>
                                    <th class="px-5 py-3">Order #</th>
                                    <th class="px-5 py-3">Placed Date</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Total</th>
                                    <th class="px-5 py-3 text-right"><span class="sr-only">Link</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/60">
                                @foreach($orders as $o)
                                    <tr class="hover:bg-zinc-800/40">
                                        <td class="px-5 py-3 font-mono font-bold text-white">
                                            <a href="{{ $o['adminPath'] }}" class="hover:text-amber-400">
                                                {{ $o['orderNumber'] }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-zinc-400">
                                            {{ \Carbon\Carbon::parse($o['placedAt'])->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-[0.65rem] font-semibold border
                                                @if($o['status'] === 'DELIVERED') bg-emerald-950/60 text-emerald-400 border-emerald-800
                                                @elseif($o['status'] === 'CANCELLED') bg-rose-950/60 text-rose-400 border-rose-800
                                                @else bg-amber-950/60 text-amber-400 border-amber-800 @endif">
                                                {{ $o['statusLabel'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right font-medium text-white tabular-nums">
                                            ₹{{ number_format($o['totalPaise'] / 100, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ $o['adminPath'] }}" class="text-amber-400 hover:text-amber-300 font-semibold">
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
            <div class="rounded-2xl bg-zinc-900/80 border border-zinc-800/80 p-5 space-y-4">
                <h2 class="text-base font-semibold text-white">Saved Addresses</h2>

                @if(empty($addresses))
                    <p class="text-xs text-zinc-500">No saved addresses on file.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($addresses as $addr)
                            <div class="p-4 rounded-xl bg-zinc-950/60 border border-zinc-800 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded-md font-bold text-[0.65rem] uppercase tracking-wider bg-zinc-800 text-zinc-300">
                                        {{ $addr['label'] }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        @if($addr['isDefaultShipping'])
                                            <span class="text-[0.65rem] text-emerald-400 font-semibold bg-emerald-950/50 px-1.5 py-0.5 rounded border border-emerald-800/60">
                                                Default Ship
                                            </span>
                                        @endif
                                        @if($addr['isDefaultBilling'])
                                            <span class="text-[0.65rem] text-zinc-400 font-semibold bg-zinc-800 px-1.5 py-0.5 rounded">
                                                Default Bill
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-zinc-300 space-y-0.5">
                                    <p class="font-bold text-white text-sm">{{ $addr['recipientName'] }}</p>
                                    <p>{{ $addr['line1'] }}</p>
                                    @if($addr['line2'])<p>{{ $addr['line2'] }}</p>@endif
                                    @if($addr['landmark'])<p class="text-zinc-500">Near: {{ $addr['landmark'] }}</p>@endif
                                    <p>{{ $addr['city'] }}, {{ $addr['stateName'] }} - <strong class="font-mono text-zinc-200">{{ $addr['postalCode'] }}</strong></p>
                                    <p class="pt-1 text-zinc-400 font-mono">Mobile: {{ $addr['phone'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Customer Reviews Card -->
            <div class="rounded-2xl bg-zinc-900/80 border border-zinc-800/80 p-5 space-y-4">
                <h2 class="text-base font-semibold text-white">Submitted Reviews</h2>
                @if(empty($reviews))
                    <p class="text-xs text-zinc-500">This customer has not posted any reviews yet.</p>
                @else
                    <div class="divide-y divide-zinc-800/60">
                        @foreach($reviews as $rev)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-medium text-white">{{ $rev['productName'] }}</p>
                                    <p class="text-zinc-500 mt-0.5">{{ $rev['rating'] }} / 5 stars · {{ \Carbon\Carbon::parse($rev['createdAt'])->format('d M Y') }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[0.65rem] font-medium bg-zinc-800 text-zinc-300">
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
            <div class="rounded-2xl bg-zinc-900/80 border border-zinc-800/80 p-5 space-y-4">
                <h2 class="text-base font-semibold text-white">Contact & Identity</h2>

                <dl class="space-y-3 text-xs">
                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Email Address</dt>
                        <dd class="mt-1 text-white break-words">
                            <a href="mailto:{{ $customer->email }}" class="hover:text-amber-400 underline underline-offset-2">
                                {{ $customer->email }}
                            </a>
                            <span class="block text-[0.6875rem] text-zinc-500 mt-0.5">
                                @if($customer->email_verified_at)
                                    Verified on {{ $customer->email_verified_at->format('d M Y') }}
                                @else
                                    Unverified
                                @endif
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Phone Number</dt>
                        <dd class="mt-1 text-white font-mono">
                            {{ $customer->phone ?: 'Not provided' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Account Registered</dt>
                        <dd class="mt-1 text-zinc-300">
                            {{ $customer->created_at?->format('d M Y, h:i A') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Last Updated</dt>
                        <dd class="mt-1 text-zinc-300">
                            {{ $customer->updated_at?->format('d M Y, h:i A') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl bg-zinc-900/80 border border-zinc-800/80 p-5 space-y-4">
                <h2 class="text-base font-semibold text-white">Security & Sessions</h2>

                <dl class="space-y-3 text-xs">
                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Authentication Mechanism</dt>
                        <dd class="mt-1 text-zinc-300">
                            Passwordless CSPRNG 6-digit OTP (Argon2id hashed)
                        </dd>
                    </div>

                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Last Sign In</dt>
                        <dd class="mt-1 text-white">
                            {{ $customer->last_login_at ? $customer->last_login_at->format('d M Y, h:i A') : 'Never' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-zinc-500 font-semibold uppercase tracking-wider text-[0.6875rem]">Active Browser Sessions</dt>
                        <dd class="mt-1 text-amber-400 font-bold tabular-nums">
                            {{ $activeSessions }} {{ $activeSessions === 1 ? 'device' : 'devices' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
