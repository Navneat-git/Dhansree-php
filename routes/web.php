<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSessionWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Media Route (Local storage with CDN fallback) ─────────────────────────
Route::get('/media/{path}', function (string $path) {
    $localFile = storage_path('app/public/media/' . $path);
    if (file_exists($localFile)) {
        return response()->file($localFile, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
    $cdn = config('store.media_base_url', 'https://assets.dhansree.com/ds-web');
    return redirect()->away(rtrim($cdn, '/') . '/' . ltrim($path, '/'));
})->where('path', '.*')->name('media.serve');

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

        // Catalog: Categories
        Route::prefix('catalog/categories')->name('admin.categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'edit'])->name('edit');
            Route::put('/{category}', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'update'])->name('update');
            Route::post('/{category}/visibility', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'toggleVisibility'])->name('toggle-visibility');
            Route::post('/reorder', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'reorder'])->name('reorder');
            Route::post('/{category}/archive', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'archive'])->name('archive');
            Route::post('/{category}/restore', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'restore'])->name('restore');
            Route::post('/{category}/image', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'uploadImage'])->name('upload-image');
            Route::post('/{category}/image/remove', [\App\Http\Controllers\Admin\AdminCategoryWebController::class, 'removeImage'])->name('remove-image');
        });

        // Catalog: Products
        Route::prefix('catalog/products')->name('admin.products.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'store'])->name('store');
            Route::get('/{product}/edit', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'edit'])->name('edit');
            Route::put('/{product}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'update'])->name('update');
            Route::post('/{product}/publish', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'publish'])->name('publish');
            Route::post('/{product}/unpublish', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'unpublish'])->name('unpublish');
            Route::post('/{product}/archive', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'archive'])->name('archive');
            Route::post('/{product}/restore', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'restore'])->name('restore');

            // Options & Variants
            Route::post('/{product}/options', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'syncOptions'])->name('sync-options');
            Route::post('/{product}/generate-variants', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'generateVariants'])->name('generate-variants');
            Route::post('/{product}/variants', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'storeVariant'])->name('store-variant');

            // Images
            Route::post('/{product}/images', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'uploadImage'])->name('upload-image');

            // Attributes
            Route::post('/{product}/attributes', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateAttributes'])->name('update-attributes');
        });

        // Direct Variant & Image actions
        Route::put('/catalog/variants/{variant}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateVariant'])->name('admin.variants.update');
        Route::post('/catalog/variants/{variant}/archive', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'archiveVariant'])->name('admin.variants.archive');
        Route::post('/catalog/variants/{variant}/restore', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'restoreVariant'])->name('admin.variants.restore');

        Route::delete('/catalog/images/{image}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'deleteImage'])->name('admin.images.delete');
        Route::post('/catalog/images/{image}/set-primary', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'setPrimaryImage'])->name('admin.images.set-primary');
    });
});
