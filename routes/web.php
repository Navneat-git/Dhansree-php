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

// ─── Customer Wishlist ──────────────────────────────────────────────────────
Route::prefix('wishlist')->name('storefront.wishlist.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\WishlistController::class, 'index'])->name('index');
    Route::get('/ids', [\App\Http\Controllers\Storefront\WishlistController::class, 'ids'])->name('ids');
    Route::post('/toggle', [\App\Http\Controllers\Storefront\WishlistController::class, 'toggle'])->name('toggle');
    Route::post('/remove', [\App\Http\Controllers\Storefront\WishlistController::class, 'remove'])->name('remove');
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

// ─── Meta & Google Product Feeds ─────────────────────────────────────────────
Route::get('/feeds/meta_catalog.csv', [\App\Http\Controllers\Storefront\ProductFeedDeliveryController::class, 'metaFeed'])->name('feeds.meta');
Route::get('/feeds/meta-catalog.csv', [\App\Http\Controllers\Storefront\ProductFeedDeliveryController::class, 'metaFeed']);
Route::get('/feeds/google_merchant.csv', [\App\Http\Controllers\Storefront\ProductFeedDeliveryController::class, 'googleFeed'])->name('feeds.google');
Route::get('/feeds/google-merchant.csv', [\App\Http\Controllers\Storefront\ProductFeedDeliveryController::class, 'googleFeed']);

// ─── Customer Passwordless Auth & Portal ──────────────────────────────────
Route::redirect('/login', '/account/login');
Route::redirect('/register', '/account/register');
Route::redirect('/signup', '/account/register');

Route::prefix('account')->name('storefront.account.')->group(function () {
    // Guest Auth Routes
    Route::get('/login', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'sendOtp'])->name('send-otp');
    Route::get('/register', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'register'])->name('register.submit');
    Route::get('/auth/{provider}', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'redirectToProvider'])->name('social');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Storefront\CustomerAuthController::class, 'handleProviderCallback'])->name('social.callback');
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


// ─── Curated Collections & Shop ──────────────────────────────────────────
Route::get('/collections/{slug}', [StorefrontController::class, 'collection'])->name('store.collection');
Route::get('/shop', [StorefrontController::class, 'shop'])->name('store.shop');

// ─── Search ────────────────────────────────────────────────────────────────
Route::get('/search', [StorefrontController::class, 'search'])->name('store.search');

// ─── Product Detail & Reviews ──────────────────────────────────────────────
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('store.product');
Route::get('/product/{slug}/reviews', [\App\Http\Controllers\Storefront\ReviewController::class, 'productReviews'])->name('store.product.reviews');
Route::redirect('/products/{slug}', '/product/{slug}', 301);

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

// ─── Instagram Reels & Posts Lookbook ─────────────────────────────────────
Route::get('/instagram', [\App\Http\Controllers\Storefront\InstagramFeedController::class, 'index'])->name('store.instagram');
Route::get('/instragram', [\App\Http\Controllers\Storefront\InstagramFeedController::class, 'index'])->name('store.instragram');
Route::post('/instagram/posts/{id}/like', [\App\Http\Controllers\Storefront\InstagramFeedController::class, 'like'])->name('store.instagram.like');


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
            Route::post('/{product}/ranking', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateRanking'])->name('update-ranking');

            // Options & Variants
            Route::post('/{product}/options', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'syncOptions'])->name('sync-options');
            Route::post('/{product}/generate-variants', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'generateVariants'])->name('generate-variants');
            Route::post('/{product}/variants', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'storeVariant'])->name('store-variant');

            // Images
            Route::post('/{product}/images', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'uploadImage'])->name('upload-image');

            // Attributes
            Route::post('/{product}/attributes', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateAttributes'])->name('update-attributes');

            // FAQs
            Route::post('/{product}/faqs', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateFaqs'])->name('update-faqs');

            // Quick Links
            Route::post('/{product}/quick-links', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateQuickLinks'])->name('update-quick-links');
        });

        // Catalog: Wishlists
        Route::get('/catalog/wishlists', [\App\Http\Controllers\Admin\AdminWishlistWebController::class, 'index'])->name('admin.wishlists.index');

        // Catalog: Share & WhatsApp Hub
        Route::get('/catalog/share', [\App\Http\Controllers\Admin\AdminCatalogShareController::class, 'index'])->name('admin.catalog.share');

        // Direct Variant & Image actions
        Route::put('/catalog/variants/{variant}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateVariant'])->name('admin.variants.update');
        Route::delete('/catalog/variants/{variant}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'destroyVariant'])->name('admin.variants.destroy');
        Route::post('/catalog/variants/{variant}/archive', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'archiveVariant'])->name('admin.variants.archive');
        Route::post('/catalog/variants/{variant}/restore', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'restoreVariant'])->name('admin.variants.restore');
        Route::post('/catalog/products/{product}/variants/bulk-stock', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'bulkStock'])->name('admin.products.variants.bulk-stock');
        Route::delete('/catalog/products/{product}/variants/delete-all', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'destroyAllVariants'])->name('admin.products.variants.delete-all');

        Route::delete('/catalog/images/{image}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'deleteImage'])->name('admin.images.delete');
        Route::put('/catalog/images/{image}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateImage'])->name('admin.images.update');
        Route::post('/catalog/images/{image}/set-primary', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'setPrimaryImage'])->name('admin.images.set-primary');
        Route::post('/catalog/images/{image}/move/{direction}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'moveImage'])->name('admin.images.move');

        Route::post('/catalog/products/{product}/upload-video', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'uploadVideo'])->name('admin.products.upload-video');
        Route::put('/catalog/videos/{video}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'updateVideo'])->name('admin.videos.update');
        Route::post('/catalog/videos/{video}/set-primary', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'setPrimaryVideo'])->name('admin.videos.set-primary');
        Route::get('/catalog/videos/{video}/download-reel', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'downloadReel'])->name('admin.videos.download-reel');
        Route::delete('/catalog/videos/{video}', [\App\Http\Controllers\Admin\AdminProductWebController::class, 'deleteVideo'])->name('admin.videos.delete');

        // Marketplace Listings & Export (Meesho, Flipkart, Amazon, Myntra, Ajio)
        Route::prefix('marketplace')->name('admin.marketplace.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminMarketplaceWebController::class, 'listings'])->name('index');
            Route::get('/listings', [\App\Http\Controllers\Admin\AdminMarketplaceWebController::class, 'listings'])->name('listings');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminMarketplaceWebController::class, 'export'])->name('export');
            Route::get('/copy', [\App\Http\Controllers\Admin\AdminMarketplaceWebController::class, 'copyTsv'])->name('copy');
            Route::post('/products/{product}/values', [\App\Http\Controllers\Admin\AdminMarketplaceWebController::class, 'saveProductValues'])->name('products.values');
        });

        // Google Merchant Center & Meta Business Product Feeds
        Route::prefix('feeds')->name('admin.feeds.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminProductFeedWebController::class, 'index'])->name('index');
            Route::post('/generate', [\App\Http\Controllers\Admin\AdminProductFeedWebController::class, 'generate'])->name('generate');
            Route::get('/download/{type}', [\App\Http\Controllers\Admin\AdminProductFeedWebController::class, 'download'])->name('download');
        });

        // Wholesale Purchases & Procurement (Women Fashion & Smart Living)
        Route::prefix('wholesale')->name('admin.wholesale.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'create'])->name('create');
            Route::get('/next-serial', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'nextSerial'])->name('next-serial');
            Route::post('/', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'store'])->name('store');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'export'])->name('export');
            Route::get('/copy', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'copyTsv'])->name('copy');
            Route::post('/colors', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'storeColor'])->name('colors.store');
            Route::delete('/colors/{color}', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'deleteColor'])->name('colors.destroy');
            Route::get('/{purchase}/edit', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'edit'])->name('edit');
            Route::put('/{purchase}', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'update'])->name('update');
            Route::delete('/{purchase}', [\App\Http\Controllers\Admin\AdminWholesalePurchaseWebController::class, 'destroy'])->name('destroy');
        });

        // Orders & Fulfillment
        Route::prefix('orders')->name('admin.orders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'index'])->name('index');
            Route::get('/{order}', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'show'])->name('show');
            Route::post('/{order}/status', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'updateStatus'])->name('status');
            Route::post('/{order}/notes', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'addNote'])->name('notes.store');
            Route::post('/{order}/shipment', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'createShipment'])->name('shipment.store');
            Route::post('/{order}/shipments/{shipment}/dispatch', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'dispatch'])->name('dispatch');
            Route::post('/{order}/local-delivery', [\App\Http\Controllers\Admin\AdminOrderWebController::class, 'createLocalDelivery'])->name('local-delivery');
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
            Route::post('/{coupon}/featured', [\App\Http\Controllers\Admin\AdminCouponWebController::class, 'setFeatured'])->name('set-featured');
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

        // Quick Links Management
        Route::prefix('quick-links')->name('admin.quick-links.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'index'])->name('index');
            Route::post('/groups', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'storeGroup'])->name('groups.store');
            Route::put('/groups/{id}', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'updateGroup'])->name('groups.update');
            Route::delete('/groups/{id}', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'destroyGroup'])->name('groups.destroy');
            Route::post('/groups/{groupId}/links', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'storeLink'])->name('links.store');
            Route::put('/links/{id}', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'updateLink'])->name('links.update');
            Route::delete('/links/{id}', [\App\Http\Controllers\Admin\AdminQuickLinkWebController::class, 'destroyLink'])->name('links.destroy');
        });

        // Policy & Static Pages
        Route::prefix('pages')->name('admin.pages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'index'])->name('index');
            Route::get('/{page}/edit', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'edit'])->name('edit');
            Route::put('/{page}', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'update'])->name('update');
            Route::post('/{page}/toggle', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'toggle'])->name('toggle');
            Route::post('/{page}/sections', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'storeSection'])->name('sections.store');
            Route::put('/sections/{section}', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'updateSection'])->name('sections.update');
            Route::delete('/sections/{section}', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'destroySection'])->name('sections.destroy');
            Route::post('/{page}/sections/move', [\App\Http\Controllers\Admin\AdminContentPageWebController::class, 'moveSection'])->name('sections.move');
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

        // Customer Journeys & GA4 Analytics
        Route::prefix('analytics')->name('admin.analytics.')->group(function () {
            Route::get('/journeys', [\App\Http\Controllers\Admin\AdminCustomerJourneyWebController::class, 'index'])->name('journeys');
        });

        // Homepage Sections & Blocks
        Route::prefix('homepage')->name('admin.homepage.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'index'])->name('index');
            Route::post('/blocks/{id}/toggle', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'toggle'])->name('toggle');
            Route::put('/blocks/{id}', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'update'])->name('update');
            Route::post('/blocks/{id}/move/{direction}', [\App\Http\Controllers\Admin\AdminHomepageWebController::class, 'move'])->name('move');
        });

        // Instagram Reels & Lookbook Management
        Route::prefix('instagram')->name('admin.instagram.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminInstagramWebController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminInstagramWebController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminInstagramWebController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminInstagramWebController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\AdminInstagramWebController::class, 'toggle'])->name('toggle');
        });

        // Dhansree Video Studio
        Route::prefix('video-studio')->name('admin.video-studio.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'create'])->name('create');
            Route::get('/products/search', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'searchProducts'])->name('products.search');
            Route::get('/products/{product}/images', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'productImages'])->name('products.images');
            Route::post('/preview-prompt', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'previewPrompt'])->name('preview-prompt');
            Route::post('/', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'store'])->name('store');
            Route::get('/{video}/status', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'status'])->name('status');
            Route::get('/{video}/download', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'download'])->name('download');
            Route::get('/{video}/review', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'review'])->name('review');
            Route::post('/{video}/approve', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'approve'])->name('approve');
            Route::post('/{video}/reject', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'reject'])->name('reject');
            Route::post('/{video}/regenerate', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'regenerate'])->name('regenerate');
            Route::post('/{video}/retry', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'retry'])->name('retry');
            Route::delete('/{video}', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'destroy'])->name('destroy');
            Route::post('/migrate-db', [\App\Http\Controllers\Admin\AdminVideoStudioWebController::class, 'migrateDb'])->name('migrate-db');
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

