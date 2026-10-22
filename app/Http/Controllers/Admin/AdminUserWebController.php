<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AdminUserStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminRecoveryCode;
use App\Models\AdminRole;
use App\Models\AdminTotpCredential;
use App\Models\AdminUser;
use App\Support\Crypto\PasswordHasher;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserWebController extends Controller
{
    public function __construct(
        protected PasswordHasher $hasher
    ) {}

    /**
     * List admin users.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $currentAdmin */
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasPermission(Permissions::ADMIN_USER_VIEW)) {
            abort(403, 'You do not have permission to view admin users.');
        }

        $query = AdminUser::with('roles')->latest('id');

        // Status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Role filter
        if ($roleId = $request->query('role_id')) {
            $query->whereHas('roles', fn($rq) => $rq->where('admin_roles.id', $roleId));
        }

        // Search
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = AdminRole::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'currentStatus' => $status,
            'currentRole' => $roleId,
            'search' => $search,
            'canCreate' => $currentAdmin->hasPermission(Permissions::ADMIN_USER_CREATE),
            'canUpdate' => $currentAdmin->hasPermission(Permissions::ADMIN_USER_UPDATE),
            'canDelete' => $currentAdmin->hasPermission(Permissions::ADMIN_USER_DELETE),
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var AdminUser $currentAdmin */
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasPermission(Permissions::ADMIN_USER_CREATE)) {
            abort(403, 'You do not have permission to create admin users.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:admin_users,email',
            'password' => 'required|string|min:8|max:100',
            'roles' => 'required|array|min:1',
            'roles.*' => 'integer|exists:admin_roles,id',
        ]);

        DB::transaction(function () use ($validated) {
            $user = AdminUser::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'password_hash' => $this->hasher->hash($validated['password']),
                'status' => AdminUserStatus::ACTIVE,
                'password_change_required' => false,
            ]);

            $user->roles()->sync($validated['roles']);
        });

        return back()->with('success', "Admin user '{$validated['name']}' created successfully.");
    }

    /**
     * Update an admin user.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $currentAdmin */
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasPermission(Permissions::ADMIN_USER_UPDATE)) {
            abort(403, 'You do not have permission to update admin users.');
        }

        $user = AdminUser::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => "required|email|max:150|unique:admin_users,email,{$id}",
            'password' => 'nullable|string|min:8|max:100',
            'roles' => 'required|array|min:1',
            'roles.*' => 'integer|exists:admin_roles,id',
            'status' => 'required|in:ACTIVE,SUSPENDED',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->name = trim($validated['name']);
            $user->email = strtolower(trim($validated['email']));
            $user->status = AdminUserStatus::from($validated['status']);

            if (!empty($validated['password'])) {
                $user->password_hash = $this->hasher->hash($validated['password']);
                $user->password_changed_at = now();
            }

            $user->save();
            $user->roles()->sync($validated['roles']);
        });

        return back()->with('success', "Admin user '{$user->name}' updated successfully.");
    }

    /**
     * Toggle active/suspended status.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        /** @var AdminUser $currentAdmin */
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasPermission(Permissions::ADMIN_USER_UPDATE)) {
            abort(403, 'You do not have permission to modify admin user status.');
        }

        $user = AdminUser::findOrFail($id);
        if ($user->id === $currentAdmin->id) {
            return back()->with('error', 'You cannot change your own account status.');
        }

        $user->status = $user->status === AdminUserStatus::ACTIVE
            ? AdminUserStatus::SUSPENDED
            : AdminUserStatus::ACTIVE;
        $user->save();

        $text = $user->status === AdminUserStatus::ACTIVE ? 'activated' : 'suspended';
        return back()->with('success', "Admin user '{$user->name}' {$text}.");
    }

    /**
     * Reset 2FA for an admin user.
     */
    public function resetTwoFactor(int $id): RedirectResponse
    {
        /** @var AdminUser $currentAdmin */
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasSuperAdmin()) {
            abort(403, 'Only Super Admins can reset two-factor authentication.');
        }

        $user = AdminUser::findOrFail($id);

        DB::transaction(function () use ($user) {
            AdminTotpCredential::where('admin_user_id', $user->id)->delete();
            AdminRecoveryCode::where('admin_user_id', $user->id)->delete();
        });

        return back()->with('success', "Two-factor authentication reset for '{$user->name}'. They can now set up 2FA afresh.");
    }
}
