<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSessionWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Admin Web Panel ─────────────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    // Guest Admin Auth routes
    Route::get('/login', [AdminSessionWebController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminSessionWebController::class, 'submitLogin']);
    Route::get('/two-factor', [AdminSessionWebController::class, 'showTwoFactor'])->name('admin.two-factor');
    Route::post('/two-factor', [AdminSessionWebController::class, 'submitTwoFactor']);
    Route::post('/logout', [AdminSessionWebController::class, 'logout'])->name('admin.logout');

    // Authenticated Admin Shell & Pages
    Route::middleware(['admin.auth', 'admin.fail_closed'])->group(function () {
        Route::get('/', fn () => redirect('/admin/dashboard'));
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/change-password', [AdminSessionWebController::class, 'showChangePassword'])->name('admin.change-password');
        Route::post('/change-password', [AdminSessionWebController::class, 'submitChangePassword']);
    });
});
