<?php

use App\Exceptions\ApiException;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude admin session cookie from default encryption
        $middleware->encryptCookies(except: [
            'dhansree_admin_session',
            '__Host-dhansree_admin_session',
            'dhansree_customer',
            '__Host-dhansree_customer',
            'dhansree_admin_2fa_challenge',
            '__Host-dhansree_admin_2fa_challenge',
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhooks/*',
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\RequireAdminAuth::class,
            'admin.permission' => \App\Http\Middleware\RequireAdminPermission::class,
            'admin.fail_closed' => \App\Http\Middleware\AdminFailClosedGuard::class,
            'customer.auth' => \App\Http\Middleware\RequireCustomerAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            $isApi = $request->is('api/*') || $request->expectsJson();

            if (! $isApi) {
                return null;
            }

            if ($e instanceof ApiException) {
                return $e->render();
            }

            if ($e instanceof ValidationException) {
                $details = [];
                foreach ($e->errors() as $field => $messages) {
                    $details[] = [
                        'path' => $field,
                        'message' => $messages[0] ?? 'Invalid value',
                    ];
                }

                return ApiResponse::error(
                    code: 'VALIDATION_ERROR',
                    message: 'Validation failed',
                    status: 422,
                    details: $details
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(
                    code: 'UNAUTHORIZED',
                    message: 'Authentication required',
                    status: 401
                );
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error(
                    code: 'FORBIDDEN',
                    message: 'Access denied',
                    status: 403
                );
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error(
                    code: 'NOT_FOUND',
                    message: 'Resource not found',
                    status: 404
                );
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return ApiResponse::error(
                    code: 'METHOD_NOT_ALLOWED',
                    message: 'Method not allowed',
                    status: 405
                );
            }

            if ($e instanceof HttpExceptionInterface) {
                return ApiResponse::error(
                    code: 'HTTP_ERROR_' . $e->getStatusCode(),
                    message: $e->getMessage() ?: 'HTTP error',
                    status: $e->getStatusCode()
                );
            }

            // Production catch-all: never leak stack trace or PHP classes
            $message = config('app.debug') ? $e->getMessage() : 'An unexpected error occurred';
            return ApiResponse::error(
                code: 'INTERNAL_ERROR',
                message: $message,
                status: 500
            );
        });
    })->create();
