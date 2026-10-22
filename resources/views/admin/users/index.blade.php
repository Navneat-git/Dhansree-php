@extends('layouts.admin')

@section('title', 'Admin Users & Roles')

@section('content')
<div class="space-y-6" x-data="{ addingUser: false, editingUser: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Admin Users & Access</h1>
                <span class="rounded-full bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                    {{ $users->total() }} Admins
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Staff members with login privileges, role-based permissions, and two-factor status.</p>
        </div>

        @if($canCreate)
            <button type="button" @click="addingUser = true"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-sm transition-all self-start sm:self-auto">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Admin User
            </button>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name or email..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-amber-500">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <select name="status" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Statuses</option>
                <option value="ACTIVE" {{ $currentStatus === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="SUSPENDED" {{ $currentStatus === 'SUSPENDED' ? 'selected' : '' }}>Suspended</option>
            </select>

            <select name="role_id" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" {{ $currentRole == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-semibold uppercase tracking-wider transition-colors shadow-2xs">
                Filter
            </button>

            @if($search || $currentStatus || $currentRole)
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs font-medium text-center transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Admin Member</th>
                        <th class="py-3 px-4">Roles & Permissions</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Last Login</th>
                        <th class="py-3 px-4">Created Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-full bg-amber-100 border border-amber-300 flex items-center justify-center font-bold text-amber-800 text-xs uppercase shrink-0">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-[11px] text-slate-500 font-mono">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @forelse($user->roles as $role)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase
                                            {{ $role->grants_all_permissions ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 italic">No role assigned</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($user->status->value === 'ACTIVE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span> Suspended
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono text-slate-600">
                                {{ $user->last_login_at ? $user->last_login_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}
                            </td>

                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]">
                                {{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($canUpdate)
                                        <!-- Edit User Button -->
                                        <button type="button" @click="editingUser = {
                                            id: {{ $user->id }},
                                            name: '{{ addslashes($user->name) }}',
                                            email: '{{ addslashes($user->email) }}',
                                            status: '{{ $user->status->value }}',
                                            role_ids: {{ json_encode($user->roles->pluck('id')->all()) }}
                                        }" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-semibold shadow-2xs transition-colors">
                                            Edit
                                        </button>

                                        <!-- Toggle Status -->
                                        @if($user->id !== auth('admin')->id())
                                            <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded border transition-colors text-[11px] font-semibold
                                                    {{ $user->status->value === 'ACTIVE' ? 'border-rose-200 text-rose-700 hover:bg-rose-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                                    {{ $user->status->value === 'ACTIVE' ? 'Suspend' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Reset 2FA -->
                                        @if(auth('admin')->user()->hasSuperAdmin())
                                            <form method="POST" action="{{ route('admin.users.reset-2fa', $user->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Reset two-factor authentication for this admin? They will be able to set up a new authenticator app upon next sign-in.')"
                                                        class="px-2 py-1 rounded text-slate-500 hover:text-amber-700 hover:bg-amber-50 text-[11px] font-medium transition-colors" title="Reset 2FA Authenticator">
                                                    Reset 2FA
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No admin users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Add Admin Modal -->
    <div x-show="addingUser" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="addingUser = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="bg-white px-6 pt-5 pb-4 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Add New Admin User</h3>
                            <button type="button" @click="addingUser = false" class="text-slate-400 hover:text-slate-700">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                            <input type="text" name="name" required placeholder="e.g. Navneet Bhardwaj"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input type="email" name="email" required placeholder="name@dhansree.com"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                            <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Assign Roles</label>
                            <div class="space-y-2 border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto">
                                @foreach($roles as $r)
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="roles[]" value="{{ $r->id }}" {{ $r->name === 'Admin' ? 'checked' : '' }}
                                               class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                                        <span class="font-medium">{{ $r->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="addingUser = false"
                                class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs">
                            Create Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div x-show="editingUser" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editingUser = null"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form method="POST" :action="'{{ url('/admin/users') }}/' + (editingUser ? editingUser.id : '')">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-6 pt-5 pb-4 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Edit Admin User</h3>
                            <button type="button" @click="editingUser = null" class="text-slate-400 hover:text-slate-700">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                            <input type="text" name="name" :value="editingUser ? editingUser.name : ''" required
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input type="email" name="email" :value="editingUser ? editingUser.email : ''" required
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" :value="editingUser ? editingUser.status : 'ACTIVE'" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="SUSPENDED">SUSPENDED</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Change Password (leave blank to keep current)</label>
                            <input type="password" name="password" minlength="8" placeholder="New password"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Assign Roles</label>
                            <div class="space-y-2 border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto">
                                @foreach($roles as $r)
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="roles[]" value="{{ $r->id }}"
                                               :checked="editingUser && editingUser.role_ids.includes({{ $r->id }})"
                                               class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                                        <span class="font-medium">{{ $r->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="editingUser = null"
                                class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
