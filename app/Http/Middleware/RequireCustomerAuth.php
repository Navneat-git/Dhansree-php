<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\CustomerStatus;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireCustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw new ApiException('UNAUTHORIZED', 'Customer authentication required', 401);
            }

            $currentUrl = $request->fullUrl();
            $loginUrl = '/account/login';
            if ($request->isMethod('GET') && !$request->is('account/login*')) {
                $loginUrl .= '?next=' . urlencode($request->getRequestUri());
            }

            return redirect()->guest($loginUrl);
        }

        if ($customer->status !== CustomerStatus::ACTIVE) {
            Auth::guard('customer')->logout();

            if ($request->expectsJson() || $request->is('api/*')) {
                throw new ApiException('ACCOUNT_DISABLED', 'This account has been deactivated. Please contact us if you need help.', 403);
            }

            return redirect('/account/login')->with('error', 'This account has been deactivated. Please contact us if you need help.');
        }

        return $next($request);
    }
}
