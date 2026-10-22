<div class="border-b border-light-gold/80 mb-8">
    <nav class="flex items-center gap-2 sm:gap-6 overflow-x-auto scrollbar-thin text-sm font-medium">
        <a href="{{ route('storefront.account.dashboard') }}"
           class="py-3 px-1 border-b-2 whitespace-nowrap transition-colors {{ request()->routeIs('storefront.account.dashboard') ? 'border-maroon-700 text-maroon-800 font-semibold' : 'border-transparent text-rich-black/60 hover:text-rich-black hover:border-rich-black/30' }}">
            Overview
        </a>
        <a href="{{ route('storefront.account.orders') }}"
           class="py-3 px-1 border-b-2 whitespace-nowrap transition-colors {{ request()->routeIs('storefront.account.orders*') ? 'border-maroon-700 text-maroon-800 font-semibold' : 'border-transparent text-rich-black/60 hover:text-rich-black hover:border-rich-black/30' }}">
            My Orders
        </a>
        <a href="{{ route('storefront.account.addresses') }}"
           class="py-3 px-1 border-b-2 whitespace-nowrap transition-colors {{ request()->routeIs('storefront.account.addresses*') ? 'border-maroon-700 text-maroon-800 font-semibold' : 'border-transparent text-rich-black/60 hover:text-rich-black hover:border-rich-black/30' }}">
            Saved Addresses
        </a>
        <a href="{{ route('storefront.account.profile') }}"
           class="py-3 px-1 border-b-2 whitespace-nowrap transition-colors {{ request()->routeIs('storefront.account.profile*') ? 'border-maroon-700 text-maroon-800 font-semibold' : 'border-transparent text-rich-black/60 hover:text-rich-black hover:border-rich-black/30' }}">
            Profile
        </a>
        <form action="{{ route('storefront.account.logout') }}" method="POST" class="ml-auto shrink-0 py-2">
            @csrf
            <button type="submit" class="text-xs font-semibold text-rich-black/50 hover:text-rose-600 transition-colors py-1 px-2.5 rounded-lg border border-rich-black/10 hover:border-rose-200">
                Sign out
            </button>
        </form>
    </nav>
</div>
