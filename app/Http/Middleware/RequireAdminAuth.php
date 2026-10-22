<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw new ApiException('UNAUTHORIZED', 'Administrator authentication required', 401);
            }
            return redirect()->guest('/admin/login');
        }

        // Check if password change is required
        if ($admin->password_change_required && !$request->is('*/password*') && !$request->is('*/logout*') && !$request->is('*/me*')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw new ApiException('PASSWORD_CHANGE_REQUIRED', 'Password change required before accessing other resources', 403);
            }
            return redirect('/admin/change-password');
        }

        return $next($request);
    }
}
