<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return ApiResponse::success(['status' => 'healthy', 'timestamp' => now()->toISOString()]);
});

// ─── Admin Authentication ───────────────────────────────────────────────────
Route::prefix('admin/auth')->group(function () {
    // Public auth endpoints
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/login/code', [AdminAuthController::class, 'loginCode']);
    Route::post('/logout', [AdminAuthController::class, 'logout']);

    // Authenticated admin profile and self-service
    Route::middleware(['admin.auth', 'admin.fail_closed'])->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/password', [AdminAuthController::class, 'changePassword']);

        // Two-factor authentication self-service
        Route::prefix('two-factor')->group(function () {
            Route::get('/', [AdminAuthController::class, 'twoFactorStatus']);
            Route::post('/setup', [AdminAuthController::class, 'beginEnrolment']);
            Route::post('/enable', [AdminAuthController::class, 'confirmEnrolment']);
            Route::post('/recovery-codes', [AdminAuthController::class, 'regenerateRecoveryCodes']);
            Route::post('/disable', [AdminAuthController::class, 'disableTwoFactor']);
        });
    });
});
