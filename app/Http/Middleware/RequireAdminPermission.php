<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Support\Rbac\AdminAuthorizer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminPermission
{
    /**
     * Handle an incoming request.
     * Enforces that the authenticated admin has ALL required permissions (AND semantics).
     *
     * @param string ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw new ApiException('UNAUTHORIZED', 'Administrator authentication required', 401);
            }
            return redirect()->guest('/admin/login');
        }

        if (!empty($permissions)) {
            $hasAccess = AdminAuthorizer::hasAccess($admin, $permissions);
            if (!$hasAccess) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    throw new ApiException(
                        'FORBIDDEN',
                        'You do not have permission to perform this action',
                        403,
                        ['requiredPermissions' => $permissions]
                    );
                }
                abort(403, 'You do not have permission to access this page.');
            }
        }

        return $next($request);
    }
}
