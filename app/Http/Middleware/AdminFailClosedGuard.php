<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminFailClosedGuard
{
    public const ALLOWED_RULES = [
        'admin.auth',
        'admin.permission',
        'allow.anonymous',
        RequireAdminAuth::class,
        RequireAdminPermission::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route) {
            $middlewares = $route->gatherMiddleware();
            $hasExplicitRule = false;

            foreach ($middlewares as $mw) {
                $baseMw = explode(':', $mw)[0];
                if (in_array($baseMw, self::ALLOWED_RULES, true) || in_array($mw, self::ALLOWED_RULES, true)) {
                    $hasExplicitRule = true;
                    break;
                }
            }

            if (!$hasExplicitRule) {
                $routeName = $route->getName() ?? $route->uri();
                Log::critical("Admin route [{$routeName}] was accessed without an explicit access rule! Denying fail-closed.");

                if ($request->expectsJson() || $request->is('api/*')) {
                    throw new ApiException('FORBIDDEN', 'Route access policy unspecified', 403);
                }
                abort(403, 'Route access policy unspecified');
            }
        }

        return $next($request);
    }
}
