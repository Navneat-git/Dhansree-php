@extends('layouts.store')

@section('title', 'Saved Addresses - Dhansree')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
                Saved Addresses
            </h1>
            <p class="text-xs sm:text-sm text-rich-black/60 mt-1">
                Manage your shipping and billing destinations for rapid 1-click checkout.
            </p>
        </div>

        @if(count($addresses) < $maxLimit)
            <button
                type="button"
                onclick="document.getElementById('address-modal-new').classList.remove('hidden')"
                class="px-5 py-2.5 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors flex items-center gap-2 shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add New Address</span>
            </button>
        @endif
    </div>

    @include('store.account.partials.nav')

    <!-- Flash messages -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm space-y-1">
            <p class="font-semibold">Please fix the following errors:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(empty($addresses))
        <div class="p-8 sm:p-16 text-center rounded-3xl bg-white border border-light-gold shadow-xs space-y-4">
            <svg class="w-14 h-14 text-gold-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <div class="space-y-1">
                <p class="font-medium text-base text-rich-black">No addresses saved yet</p>
                <p class="text-xs text-rich-black/60 max-w-sm mx-auto">
                    Save your delivery location now so you won't have to retype it every time you checkout.
                </p>
            </div>
            <button
                type="button"
                onclick="document.getElementById('address-modal-new').classList.remove('hidden')"
                class="px-6 py-3 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors"
            >
                Add Your First Address
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($addresses as $addr)
                <div class="p-5 sm:p-6 rounded-3xl bg-white border {{ $addr['isDefaultShipping'] ? 'border-maroon-700 ring-2 ring-maroon-700/10' : 'border-light-gold' }} shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[0.6875rem] font-bold tracking-wider uppercase bg-cream border border-light-gold text-maroon-900">
                                {{ $addr['label'] }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                @if($addr['isDefaultShipping'])
                                    <span class="px-2 py-0.5 rounded-full text-[0.65rem] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Default Shipping
                                    </span>
                                @endif
                                @if($addr['isDefaultBilling'])
                                    <span class="px-2 py-0.5 rounded-full text-[0.65rem] font-semibold bg-slate-50 text-slate-800 border border-slate-200">
                                        Default Billing
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-rich-black">{{ $addr['recipientName'] }}</h3>
                            <p class="text-xs text-rich-black/70 mt-1 leading-relaxed">
                                {{ $addr['line1'] }}<br>
                                @if($addr['line2']) {{ $addr['line2'] }}<br> @endif
                                @if($addr['landmark']) Near {{ $addr['landmark'] }}<br> @endif
                                {{ $addr['city'] }}, {{ $addr['stateName'] }} - <strong class="font-mono text-rich-black">{{ $addr['postalCode'] }}</strong>
                            </p>
                            <p class="text-xs text-rich-black/70 mt-2 font-mono">
                                Mobile: <strong class="text-rich-black">{{ $addr['phone'] }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-light-gold/60 flex items-center justify-between text-xs">
                        <button
                            type="button"
                            onclick="openEditModal({{ json_encode($addr) }})"
                            class="font-semibold text-maroon-800 hover:text-maroon-900 underline underline-offset-4"
                        >
                            Edit
                        </button>

                        @if(count($addresses) > 1)
                            <form action="{{ route('storefront.account.addresses.delete', $addr['id']) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this address?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-medium">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Add Address Modal -->
<div id="address-modal-new" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-light-gold shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-light-gold">
            <h3 class="font-serif text-xl text-maroon-800 font-semibold">Add New Address</h3>
            <button type="button" onclick="document.getElementById('address-modal-new').classList.add('hidden')" class="text-rich-black/40 hover:text-rich-black">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('storefront.account.addresses.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-3 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="label" value="HOME" class="sr-only peer" checked>
                    <div class="p-2.5 rounded-xl border border-light-gold text-center text-xs font-semibold peer-checked:bg-maroon-700 peer-checked:text-white peer-checked:border-maroon-700 transition-all">
                        HOME
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="label" value="WORK" class="sr-only peer">
                    <div class="p-2.5 rounded-xl border border-light-gold text-center text-xs font-semibold peer-checked:bg-maroon-700 peer-checked:text-white peer-checked:border-maroon-700 transition-all">
                        WORK
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="label" value="OTHER" class="sr-only peer">
                    <div class="p-2.5 rounded-xl border border-light-gold text-center text-xs font-semibold peer-checked:bg-maroon-700 peer-checked:text-white peer-checked:border-maroon-700 transition-all">
                        OTHER
                    </div>
                </label>
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Recipient Name *</label>
                <input type="text" name="recipient_name" required placeholder="Full Name" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">10-Digit Mobile Number *</label>
                <input type="tel" name="phone" required placeholder="+919876543210" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Address Line 1 (Flat, House, Building) *</label>
                <input type="text" name="line1" required placeholder="House/Flat No., Road Name" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Address Line 2 (Area, Colony, Sector)</label>
                <input type="text" name="line2" placeholder="Locality / Sector" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Landmark</label>
                    <input type="text" name="landmark" placeholder="Near Temple / Park" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">City *</label>
                    <input type="text" name="city" required placeholder="City / Town" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">State *</label>
                    <select name="state_code" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none bg-white">
                        <option value="">Select State</option>
                        @foreach(\App\Support\Commerce\IndianStates::all() as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">PIN Code *</label>
                    <input type="text" name="postal_code" maxlength="6" pattern="[1-9][0-9]{5}" required placeholder="6-digit PIN" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none font-mono">
                </div>
            </div>

            <div class="pt-2 space-y-2">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-rich-black">
                    <input type="checkbox" name="is_default_shipping" value="1" class="rounded border-rich-black/30 text-maroon-700 focus:ring-maroon-700">
                    <span>Set as default shipping address</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs text-rich-black">
                    <input type="checkbox" name="is_default_billing" value="1" class="rounded border-rich-black/30 text-maroon-700 focus:ring-maroon-700">
                    <span>Set as default billing address</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('address-modal-new').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-light-gold text-xs font-semibold text-rich-black hover:bg-cream">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide">
                    Save Address
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Address Modal -->
<div id="address-modal-edit" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-light-gold shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-light-gold">
            <h3 class="font-serif text-xl text-maroon-800 font-semibold">Edit Address</h3>
            <button type="button" onclick="document.getElementById('address-modal-edit').classList.add('hidden')" class="text-rich-black/40 hover:text-rich-black">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="edit-address-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-3 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="label" value="HOME" id="edit-label-home" class="sr-only peer">
                    <div class="p-2.5 rounded-xl border border-light-gold text-center text-xs font-semibold peer-checked:bg-maroon-700 peer-checked:text-white peer-checked:border-maroon-700 transition-all">
                        HOME
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="label" value="WORK" id="edit-label-work" class="sr-only peer">
                    <div class="p-2.5 rounded-xl border border-light-gold text-center text-xs font-semibold peer-checked:bg-maroon-700 peer-checked:text-white peer-checked:border-maroon-700 transition-all">
                        WORK
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="label" value="OTHER" id="edit-label-other" class="sr-only peer">
                    <div class="p-2.5 rounded-xl border border-light-gold text-center text-xs font-semibold peer-checked:bg-maroon-700 peer-checked:text-white peer-checked:border-maroon-700 transition-all">
                        OTHER
                    </div>
                </label>
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Recipient Name *</label>
                <input type="text" name="recipient_name" id="edit-recipient-name" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Mobile Number *</label>
                <input type="tel" name="phone" id="edit-phone" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Address Line 1 *</label>
                <input type="text" name="line1" id="edit-line1" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Address Line 2</label>
                <input type="text" name="line2" id="edit-line2" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">Landmark</label>
                    <input type="text" name="landmark" id="edit-landmark" class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">City *</label>
                    <input type="text" name="city" id="edit-city" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">State *</label>
                    <select name="state_code" id="edit-state-code" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none bg-white">
                        @foreach(\App\Support\Commerce\IndianStates::all() as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">PIN Code *</label>
                    <input type="text" name="postal_code" id="edit-postal-code" maxlength="6" pattern="[1-9][0-9]{5}" required class="w-full px-3.5 py-2.5 rounded-xl border border-rich-black/20 text-xs focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 outline-none font-mono">
                </div>
            </div>

            <div class="pt-2 space-y-2">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-rich-black">
                    <input type="checkbox" name="is_default_shipping" value="1" id="edit-default-shipping" class="rounded border-rich-black/30 text-maroon-700 focus:ring-maroon-700">
                    <span>Default shipping address</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs text-rich-black">
                    <input type="checkbox" name="is_default_billing" value="1" id="edit-default-billing" class="rounded border-rich-black/30 text-maroon-700 focus:ring-maroon-700">
                    <span>Default billing address</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('address-modal-edit').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-light-gold text-xs font-semibold text-rich-black hover:bg-cream">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide">
                    Update Address
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(addr) {
    const form = document.getElementById('edit-address-form');
    form.action = '/account/addresses/' + addr.id;

    if (addr.label === 'HOME') document.getElementById('edit-label-home').checked = true;
    else if (addr.label === 'WORK') document.getElementById('edit-label-work').checked = true;
    else document.getElementById('edit-label-other').checked = true;

    document.getElementById('edit-recipient-name').value = addr.recipientName || '';
    document.getElementById('edit-phone').value = addr.phone || '';
    document.getElementById('edit-line1').value = addr.line1 || '';
    document.getElementById('edit-line2').value = addr.line2 || '';
    document.getElementById('edit-landmark').value = addr.landmark || '';
    document.getElementById('edit-city').value = addr.city || '';
    document.getElementById('edit-state-code').value = addr.stateCode || '';
    document.getElementById('edit-postal-code').value = addr.postalCode || '';

    document.getElementById('edit-default-shipping').checked = Boolean(addr.isDefaultShipping);
    document.getElementById('edit-default-billing').checked = Boolean(addr.isDefaultBilling);

    document.getElementById('address-modal-edit').classList.remove('hidden');
}
</script>
@endsection
