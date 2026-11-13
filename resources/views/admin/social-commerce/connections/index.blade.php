@extends('layouts.admin')

@section('title', 'Social Media Connections')
@section('page_title', 'Social Media Connections (Meta Graph API)')

@section('header_actions')
<a href="{{ route('admin.social-commerce.settings.index') }}"
   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.15.894c-.09.542-.559.94-1.109.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.11v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>
    Configure Meta Credentials
</a>
@endsection

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Credentials Status Banner -->
    @if(!$isConfigured)
    <div class="rounded-2xl border border-amber-300 bg-amber-50/80 p-5 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-xl bg-amber-500 text-slate-950 shrink-0">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-amber-950">
                <p class="font-bold">Meta App Credentials Required</p>
                <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                    To connect your official Instagram Professional Account and Dhansree Facebook Page, please enter your <strong>Meta App ID</strong> and <strong>Meta App Secret</strong> in Studio Settings.
                </p>
                <a href="{{ route('admin.social-commerce.settings.index') }}" class="inline-flex items-center gap-1 font-bold text-xs text-amber-950 underline mt-2">
                    Enter Meta App Credentials in Settings &rarr;
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Connected Channels Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Instagram Professional Account Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="size-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                            IG
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Instagram Professional</h3>
                            <p class="text-[11px] text-slate-500">Reels &amp; Posts Publishing</p>
                        </div>
                    </div>

                    @if($instagramAccount && $instagramAccount->is_connected)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Connected
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            Not Connected
                        </span>
                    @endif
                </div>

                @if($instagramAccount && $instagramAccount->is_connected)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Account:</span>
                            <span class="font-semibold text-slate-900">{{ $instagramAccount->account_name }}</span>
                        </div>
                        @if($instagramAccount->username)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Username:</span>
                            <span class="font-mono text-slate-700">@<span>{{ $instagramAccount->username }}</span></span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-slate-500">Token Status:</span>
                            <span class="text-emerald-600 font-medium">Valid &bull; Encrypted</span>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Connect your Instagram Business or Creator account to publish fashion reels with captions and hashtags after your explicit approval.
                    </p>
                @endif
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                @if($instagramAccount && $instagramAccount->is_connected)
                    <form method="POST" action="{{ route('admin.social-commerce.connections.disconnect', $instagramAccount) }}" onsubmit="return confirm('Disconnect Instagram account?');">
                        @csrf
                        <button type="submit" class="w-full py-2 px-3 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 text-xs font-semibold transition-colors">
                            Disconnect Instagram Account
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.social-commerce.connections.connect') }}"
                       class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs text-center shadow-xs block transition-colors">
                        Connect Instagram Account
                    </a>
                @endif
            </div>
        </div>

        <!-- Facebook Page Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="size-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            FB
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Facebook Page</h3>
                            <p class="text-[11px] text-slate-500">Dhansree Official Page</p>
                        </div>
                    </div>

                    @if($facebookAccount && $facebookAccount->is_connected)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Connected
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            Not Connected
                        </span>
                    @endif
                </div>

                @if($facebookAccount && $facebookAccount->is_connected)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Page Name:</span>
                            <span class="font-semibold text-slate-900">{{ $facebookAccount->account_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Page ID:</span>
                            <span class="font-mono text-slate-700">{{ $facebookAccount->account_id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Token Status:</span>
                            <span class="text-emerald-600 font-medium">Valid &bull; Encrypted</span>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Connect the Dhansree Facebook Page to publish reels and product showcases simultaneously with Instagram.
                    </p>
                @endif
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                @if($facebookAccount && $facebookAccount->is_connected)
                    <form method="POST" action="{{ route('admin.social-commerce.connections.disconnect', $facebookAccount) }}" onsubmit="return confirm('Disconnect Facebook Page?');">
                        @csrf
                        <button type="submit" class="w-full py-2 px-3 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 text-xs font-semibold transition-colors">
                            Disconnect Facebook Page
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.social-commerce.connections.connect') }}"
                       class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs text-center shadow-xs block transition-colors">
                        Connect Facebook Page
                    </a>
                @endif
            </div>
        </div>

    </div>

    <!-- Alternative: Manual Connection via Page Access Token -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4" x-data="{ openManual: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="openManual = !openManual">
            <div class="flex items-center gap-2.5">
                <span class="size-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </span>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">Alternative: Connect via Page Access Token</h4>
                    <p class="text-xs text-slate-500">Bypass OAuth popup entirely by pasting a Page Access Token or System User Token.</p>
                </div>
            </div>
            <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" x-text="openManual ? 'Hide' : 'Show Form'"></button>
        </div>

        <div x-show="openManual" x-cloak class="pt-3 border-t border-slate-100 space-y-4">
            <form method="POST" action="{{ route('admin.social-commerce.connections.manual') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Facebook Page ID</label>
                        <input type="text" name="page_id" required placeholder="e.g. 102938475610293"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <p class="text-[11px] text-slate-400 mt-1">Found in Facebook Page &rarr; About &rarr; Page Transparency &rarr; Page ID.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Page Access Token</label>
                        <input type="password" name="access_token" required placeholder="EAAB..."
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2 font-mono">
                        <p class="text-[11px] text-slate-400 mt-1">Generated from Graph API Explorer or Meta Business Settings &rarr; System Users.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs text-slate-500">System will automatically detect and link the associated Instagram Business Account.</span>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-colors">
                        Verify &amp; Connect
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Troubleshooting Guide for '0 Pages Selected' -->
    <div class="bg-amber-50/80 rounded-2xl border border-amber-200 p-6 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold text-amber-950 flex items-center gap-2">
                <svg class="size-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                Why does Meta show "0 Pages were selected or found"?
            </h4>
            <form method="POST" action="{{ route('admin.social-commerce.connections.reset-auth') }}" onsubmit="return confirm('Reset Meta session cache?');">
                @csrf
                <button type="submit" class="text-xs text-amber-800 hover:text-amber-950 font-semibold underline">
                    Clear OAuth Cache
                </button>
            </form>
        </div>

        <p class="text-xs text-amber-900 leading-relaxed">
            When you authorized Meta, Facebook cached an empty selection. Follow these 3 quick checks:
        </p>

        <ol class="list-decimal list-inside space-y-2 text-xs text-amber-900 leading-relaxed font-medium">
            <li>
                <strong>Clear Facebook's cached app grant:</strong> Go to
                <a href="https://www.facebook.com/settings?tab=applications" target="_blank" class="underline text-blue-700 font-bold">Facebook &rarr; Settings &rarr; Apps and Websites</a>,
                find your app, and click <strong>Remove</strong>. This forces Facebook to show the full selection dialog next time.
            </li>
            <li>
                <strong>Page Admin Permissions:</strong> Confirm that your personal Facebook account (<span class="font-bold">Navneet Bhardwaj</span>) has <strong>Full Control / Admin access</strong> to the Dhansree Facebook Page.
            </li>
            <li>
                <strong>Instagram Connected to Facebook Page:</strong> Open Instagram on your mobile device &rarr; <em>Edit Profile</em> &rarr; <em>Public Business Information</em> &rarr; <em>Page</em> &rarr; ensure the <strong>Dhansree Facebook Page</strong> is selected, and your account type is <strong>Professional (Business/Creator)</strong>.
            </li>
        </ol>
    </div>

    <!-- Meta Developer Instructions Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-3">
        <h4 class="text-sm font-semibold text-slate-900">Meta Developer App Setup Details</h4>
        <p class="text-xs text-slate-500 leading-relaxed">
            In your Meta Developer Dashboard (<a href="https://developers.facebook.com" target="_blank" class="text-blue-600 underline">developers.facebook.com</a>), configure your app with these exact values:
        </p>

        <div class="space-y-2 text-xs">
            <div>
                <span class="font-semibold text-slate-700 block mb-0.5">Valid OAuth Redirect URI:</span>
                <input type="text" readonly value="{{ $redirectUri }}"
                       class="w-full font-mono text-xs bg-slate-50 rounded-lg border-slate-300 py-1.5 px-2.5 text-slate-700">
            </div>
            <div>
                <span class="font-semibold text-slate-700 block mb-0.5">Required Permissions / Scopes:</span>
                <p class="font-mono text-[11px] text-slate-600 bg-slate-50 rounded-lg p-2 border border-slate-200">
                    instagram_basic, instagram_content_publish, pages_show_list, pages_read_engagement, pages_manage_posts
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
