<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSessionWebController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\StorefrontController;

// ─── Storefront Hubs ────────────────────────────────────────────────────────
Route::get('/', [StorefrontController::class, 'home'])->name('store.home');
Route::get('/smart-living', [StorefrontController::class, 'smartLiving'])->name('store.smart-living');

// ─── Shopping Bag / Cart ───────────────────────────────────────────────────
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::get('/data', [CartController::class, 'data'])->name('cart.data');
    Route::post('/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// ─── Express Checkout & Payment ─────────────────────────────────────────────
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
Route::post('/checkout/place', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
Route::post('/checkout/razorpay/verify', [CheckoutController::class, 'razorpayVerify'])->name('checkout.razorpay.verify');
Route::get('/order/{orderNumber}/confirmed', [CheckoutController::class, 'confirmation'])->name('order.confirmed');

// ─── Storefront Order Tracking ───────────────────────────────────────────
Route::get('/track-order', [\App\Http\Controllers\Storefront\OrderTrackingController::class, 'index'])->name('store.track-order.index');
Route::post('/track-order', [\App\Http\Controllers\Storefront\OrderTrackingController::class, 'lookup'])->name('store.track-order.lookup');
Route::get('/order/{orderNumber}/track', [\App\Http\Controllers\Storefront\OrderTrackingController::class, 'show'])->name('store.track-order.show');
Route::get('/track-order/{orderNumber}', [\App\Http\Controllers\Storefront\OrderTrackingController::class, 'show']);

// ─── Payment & Shipping Webhooks ───────────────────────────────────────────
Route::post('/webhooks/razorpay', [CheckoutController::class, 'razorpayWebhook'])->name('webhooks.razorpay');
Route::post('/api/webhooks/razorpay', [CheckoutController::class, 'razorpayWebhook']);
Route::post('/webhooks/shiprocket', [\App\Http\Controllers\Webhooks\ShiprocketWebhookController::class, 'handle'])->name('webhooks.shiprocket');
Route::post('/api/webhooks/shiprocket', [\App\Http\Controllers\Webhooks\ShiprocketWebhookController::class, 'handle']);

// ─── Newsletter Subscription & Unsubscribe ──────────────────────────────────
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// ─── Customer Passwordless Auth & Portal ──────────────────────────────────
Route::prefix('account')->name('storefront.account.')->group(function () {
    // Guest Auth Routes
    Route::get('/login', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'sendOtp'])->name('send-otp');
    Route::get('/login/verify', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'showVerify'])->name('verify');
    Route::post('/login/verify', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'verifyOtp'])->name('verify.submit');
    Route::post('/logout', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'logout'])->name('logout');

    // Authenticated Customer Area
    Route::middleware('customer.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{orderNumber}', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'orderDetail'])->name('order-detail');
        Route::get('/addresses', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'addresses'])->name('addresses');
        Route::post('/addresses', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'storeAddress'])->name('addresses.store');
        Route::put('/addresses/{id}', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'updateAddress'])->name('addresses.update');
        Route::delete('/addresses/{id}', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'deleteAddress'])->name('addresses.delete');
        Route::get('/profile', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/reviews', [\App\Http\Controllers\Storefront\ReviewController::class, 'index'])->name('reviews');
        Route::post('/reviews', [\App\Http\Controllers\Storefront\ReviewController::class, 'submit'])->name('reviews.store');
        Route::put('/reviews/{id}', [\App\Http\Controllers\Storefront\ReviewController::class, 'update'])->name('reviews.update');
    });
});

// JSON API Storefront Endpoints for Customer Account
Route::prefix('store/account')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'sendOtp']);
    Route::post('/verify', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'verifyOtp']);
    Route::post('/logout', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'logout']);

    Route::middleware('customer.auth')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'dashboard']);
        Route::get('/orders', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'orders']);
        Route::get('/orders/{orderNumber}', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'orderDetail']);
        Route::get('/addresses', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'addresses']);
        Route::post('/addresses', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'storeAddress']);
        Route::put('/addresses/{id}', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'deleteAddress']);
        Route::put('/profile', [\App\Http\Controllers\Storefront\CustomerAccountController::class, 'updateProfile']);
    });
});


// ─── Curated Collections ───────────────────────────────────────────────────
Route::get('/collections/{slug}', [StorefrontController::class, 'collection'])->name('store.collection');

// ─── Search ────────────────────────────────────────────────────────────────
Route::get('/search', [StorefrontController::class, 'search'])->name('store.search');

// ─── Product Detail & Reviews ──────────────────────────────────────────────
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('store.product');
Route::get('/product/{slug}/reviews', [\App\Http\Controllers\Storefront\ReviewController::class, 'productReviews'])->name('store.product.reviews');

// ─── Static Pages & Policies ───────────────────────────────────────────────
Route::get('/about', [StorefrontController::class, 'about'])->name('store.about');
Route::get('/contact', [StorefrontController::class, 'contact'])->name('store.contact');
Route::get('/faq', [StorefrontController::class, 'faq'])->name('store.faq');
Route::get('/privacy-policy', [StorefrontController::class, 'privacyPolicy'])->name('store.privacy-policy');
Route::get('/return-policy', [StorefrontController::class, 'returnPolicy'])->name('store.return-policy');
Route::get('/returns-and-exchanges', [StorefrontController::class, 'returnPolicy'])->name('store.returns-and-exchanges');
Route::get('/shipping-policy', [StorefrontController::class, 'shippingPolicy'])->name('store.shipping-policy');
Route::get('/terms', [StorefrontController::class, 'terms'])->name('store.terms');
Route::get('/terms-of-service', [StorefrontController::class, 'terms'])->name('store.terms-of-service');

// ─── Search Engine Optimization & Crawlers ────────────────────────────────
Route::get('/sitemap.xml', [StorefrontController::class, 'sitemap'])->name('store.sitemap');
Route::get('/robots.txt', [StorefrontController::class, 'robots'])->name('store.robots');

// ─── Media Route (Local storage with CDN fallback) ─────────────────────────
Route::get('/media/{path}', function (string $path) {
    // 1. Laravel public storage disk (storage/app/public/media/...)
    $localFile = storage_path('app/public/media/' . $path);
    if (file_exists($localFile)) {
        return response()->file($localFile, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    // 2. Extra local directory if set (e.g. ds-assets/ds-web)
    $mediaDir = config('store.media_local_directory');
    if ($mediaDir && file_exists(rtrim($mediaDir, '/') . '/' . ltrim($path, '/'))) {
        return response()->file(rtrim($mediaDir, '/') . '/' . ltrim($path, '/'), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    // 3. Fallback redirect to CDN
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

        // Catalog: Collections
        Route::prefix('catalog/collections')->name('admin.collections.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'store'])->name('store');
            Route::get('/{collection}/edit', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'edit'])->name('edit');
            Route::put('/{collection}', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'update'])->name('update');
            Route::delete('/{collection}', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'destroy'])->name('destroy');
            Route::post('/{collection}/visibility', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'toggleVisibility'])->name('toggle-visibility');
            Route::post('/reorder', [\App\Http\Controllers\Admin\AdminCollectionWebController::class, 'reorder'])->name('reorder');
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

        // Orders & Fulfillment
        Route::prefix('orders')->name('admin.orders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'index'])->name('index');
            Route::get('/{order}', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'show'])->name('show');
            Route::post('/{order}/status', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'updateStatus'])->name('status');
            Route::post('/{order}/notes', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'addNote'])->name('notes.store');
            Route::post('/{order}/shipment', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'createShipment'])->name('shipment.store');
            Route::post('/{order}/shipments/{shipment}/dispatch', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'dispatch'])->name('dispatch');
            Route::post('/{order}/cancel', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'cancel'])->name('cancel');
            Route::get('/{order}/invoice', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'invoice'])->name('invoice');
        });

        // Customers Management
        Route::prefix('customers')->name('admin.customers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCustomerWebController::class, 'index'])->name('index');
            Route::get('/{customer}', [\App\Http\Controllers\Admin\AdminCustomerWebController::class, 'show'])->name('show');
            Route::post('/{customer}/status', [\App\Http\Controllers\Admin\AdminCustomerWebController::class, 'updateStatus'])->name('status');
        });

        // Reviews Moderation
        Route::prefix('reviews')->name('admin.reviews.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminReviewWebController::class, 'index'])->name('index');
            Route::post('/{id}/moderate', [\App\Http\Controllers\Admin\AdminReviewWebController::class, 'moderate'])->name('moderate');
        });

        // Coupons Management
        Route::prefix('coupons')->name('admin.coupons.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'store'])->name('store');
            Route::get('/{coupon}/edit', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'edit'])->name('edit');
            Route::put('/{coupon}', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'update'])->name('update');
            Route::post('/{coupon}/toggle', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'toggle'])->name('toggle');
            Route::delete('/{coupon}', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'destroy'])->name('destroy');
        });

        // Banners Management
        Route::prefix('banners')->name('admin.banners.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'store'])->name('store');
            Route::post('/promos', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'updatePromos'])->name('promos.update');
            Route::get('/{banner}/edit', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'edit'])->name('edit');
            Route::put('/{banner}', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'update'])->name('update');
            Route::post('/{banner}/toggle', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'toggle'])->name('toggle');
            Route::delete('/{banner}', [\App\Http\Controllers\Admin\AdminBannerWebController::class, 'destroy'])->name('destroy');
        });

        // FAQs Management
        Route::prefix('faqs')->name('admin.faqs.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'index'])->name('index');
            Route::post('/groups', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'storeGroup'])->name('groups.store');
            Route::put('/groups/{id}', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'updateGroup'])->name('groups.update');
            Route::delete('/groups/{id}', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'deleteGroup'])->name('groups.destroy');
            Route::post('/groups/{groupId}/entries', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'storeEntry'])->name('entries.store');
            Route::put('/entries/{id}', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'updateEntry'])->name('entries.update');
            Route::delete('/entries/{id}', [\App\Http\Controllers\Admin\AdminFaqWebController::class, 'deleteEntry'])->name('entries.destroy');
        });

        // SEO Redirects Management
        Route::prefix('seo')->name('admin.seo.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminSeoRedirectWebController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminSeoRedirectWebController::class, 'store'])->name('store');
            Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\AdminSeoRedirectWebController::class, 'toggle'])->name('toggle');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminSeoRedirectWebController::class, 'destroy'])->name('destroy');
        });

        // Newsletter Subscribers
        Route::prefix('subscribers')->name('admin.subscribers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminNewsletterWebController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminNewsletterWebController::class, 'export'])->name('export');
        });

        // Homepage Sections & Blocks
        Route::prefix('homepage')->name('admin.homepage.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'index'])->name('index');
            Route::post('/blocks/{id}/toggle', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'toggle'])->name('toggle');
            Route::put('/blocks/{id}', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'update'])->name('update');
            Route::post('/blocks/{id}/move/{direction}', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'move'])->name('move');
        });

        // Payments & Reconciliation
        Route::prefix('payments')->name('admin.payments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminPaymentWebController::class, 'index'])->name('index');
            Route::get('/{payment}', [\App\Http\Controllers\Admin\AdminPaymentWebController::class, 'show'])->name('show');
            Route::post('/{payment}/record-offline', [\App\Http\Controllers\Admin\AdminPaymentWebController::class, 'recordOffline'])->name('record-offline');
        });

        // Store Settings
        Route::prefix('settings')->name('admin.settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminSettingsWebController::class, 'index'])->name('index');
            Route::put('/', [\App\Http\Controllers\Admin\AdminSettingsWebController::class, 'update'])->name('update');
        });

        // Email Templates
        Route::prefix('emails')->name('admin.emails.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminEmailTemplateWebController::class, 'index'])->name('index');
            Route::get('/{type}', [\App\Http\Controllers\Admin\AdminEmailTemplateWebController::class, 'edit'])->name('edit');
            Route::put('/{type}', [\App\Http\Controllers\Admin\AdminEmailTemplateWebController::class, 'update'])->name('update');
            Route::post('/{type}/preview', [\App\Http\Controllers\Admin\AdminEmailTemplateWebController::class, 'preview'])->name('preview');
            Route::post('/{type}/test', [\App\Http\Controllers\Admin\AdminEmailTemplateWebController::class, 'sendTest'])->name('test');
            Route::post('/{type}/reset', [\App\Http\Controllers\Admin\AdminEmailTemplateWebController::class, 'reset'])->name('reset');
        });

        // Audit Logs
        Route::prefix('audit-logs')->name('admin.audit-logs.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminAuditLogWebController::class, 'index'])->name('index');
        });

        // Admin Users & Roles
        Route::prefix('users')->name('admin.users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminUserWebController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminUserWebController::class, 'store'])->name('store');
            Route::put('/{user}', [\App\Http\Controllers\Admin\AdminUserWebController::class, 'update'])->name('update');
            Route::post('/{user}/toggle-status', [\App\Http\Controllers\Admin\AdminUserWebController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{user}/reset-2fa', [\App\Http\Controllers\Admin\AdminUserWebController::class, 'resetTwoFactor'])->name('reset-2fa');
        });

        // Shipments & Fulfillment
        Route::prefix('shipments')->name('admin.shipments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminShipmentWebController::class, 'index'])->name('index');
        });

        // Inventory Management
        Route::prefix('inventory')->name('admin.inventory.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminInventoryWebController::class, 'index'])->name('index');
            Route::post('/adjust/{variantId}', [\App\Http\Controllers\Admin\AdminInventoryWebController::class, 'adjust'])->name('adjust');
        });
    });
});


// ─── Dynamic Category Collection Route (Placed last to prevent collisions) ───
Route::get('/{slug}', [StorefrontController::class, 'category'])->name('store.category');

