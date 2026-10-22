@extends('layouts.admin')

@section('title', 'Change Password')
@section('page_title', 'Account Security')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-900">Change Your Password</h2>
            <p class="text-xs text-slate-500 mt-1">
                Changing your password will sign out all your other active browser sessions for security.
            </p>
        </div>

        <form method="POST" action="{{ url('/admin/change-password') }}" class="space-y-5">
            @csrf

            <div>
                <label for="currentPassword" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Current Password
                </label>
                <input id="currentPassword" name="currentPassword" type="password" required
                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
            </div>

            <div>
                <label for="newPassword" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    New Password
                </label>
                <input id="newPassword" name="newPassword" type="password" required minlength="10"
                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                <p class="mt-1.5 text-[0.6875rem] text-slate-500">
                    Must be at least 10 characters long. Avoid common passwords or reuse.
                </p>
            </div>

            <div>
                <label for="newPassword_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Confirm New Password
                </label>
                <input id="newPassword_confirmation" name="newPassword_confirmation" type="password" required minlength="10"
                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="pt-3 flex items-center justify-end gap-3">
                <a href="{{ url('/admin/dashboard') }}" class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 shadow-sm transition-colors">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
