<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Support\Rbac\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuditLogWebController extends Controller
{
    /**
     * Audit log listing.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::AUDIT_LOG_VIEW)) {
            abort(403, 'You do not have permission to view audit logs.');
        }

        $query = AuditLog::with('actorAdminUser')->latest('occurred_at');

        // Filter by action code prefix
        if ($action = trim((string) $request->query('action'))) {
            $query->where('action', 'like', "{$action}%");
        }

        // Filter by actor
        if ($actorId = $request->query('admin_user_id')) {
            $query->where('actor_admin_user_id', $actorId);
        }

        // Filter by search term
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhere('entity_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($from = $request->query('from')) {
            $query->where('occurred_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->query('to')) {
            $query->where('occurred_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->paginate(25)->withQueryString();
        $adminUsers = AdminUser::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'adminUsers' => $adminUsers,
            'currentAction' => $action,
            'currentActor' => $actorId,
            'search' => $search,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
