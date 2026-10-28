<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Services\Checkout\CheckoutService;
use App\Services\Storefront\CatalogBrowsingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DefaultCouponAndDiscountedPricingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        StoreSettings::clearCouponCache();
    }

    private function addStockToVariant(ProductVariant $variant, int $qty = 10): void
    {
        InventoryItem::firstOrCreate(
            ['variant_id' => $variant->id],
            ['backorder_policy' => 'STOP_SALE']
        );

        $location = InventoryLocation::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Location', 'is_active' => true, 'is_default' => true]
        );

        InventoryLevel::create([
            'variant_id' => $variant->id,
            'location_id' => $location->id,
            'on_hand' => $qty,
            'reserved' => 0,
        ]);
    }

    public function test_math_calculation_matches_user_specification(): void
    {
        // User specification:
        // Product MRP = 1699, Selling Price = 1199
        // Base discount = 29%
        // Default coupon = FESTIVE10 (10% off)
        // Selling price with coupon = 1079
        // Total discount from MRP = 36% OFF

        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000, // 10%
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $calc = $store->calculateDiscountedPrice(1199, 1699);

        $this->assertEquals(1079, $calc['discounted_price']);
        $this->assertEquals('₹1,079', $calc['formatted_price']);
        $this->assertEquals('₹1,699', $calc['formatted_mrp']);
        $this->assertEquals(36, $calc['discount_percentage']);
    }

    public function test_fallback_when_no_coupon_or_coupon_inactive(): void
    {
        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'NONEXISTENT']);
        StoreSettings::clearCouponCache();

        $calc = $store->calculateDiscountedPrice(1199, 1699);

        $this->assertEquals(1199, $calc['discounted_price']);
        $this->assertEquals('₹1,199', $calc['formatted_price']);
        $this->assertEquals('₹1,699', $calc['formatted_mrp']);
        $this->assertEquals(29, $calc['discount_percentage']);
    }

    public function test_storefront_product_model_displays_discounted_price_and_discount(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000, // 10%
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set',
            'slug' => 'lime-green-chevron-kurta-set',
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'brand' => 'DHANSREE ETHNIC',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'standard',
            'weight_grams' => 300,
            'position' => 1,
        ]);

        $product->load('variants');

        // Storefront context
        $this->assertEquals('₹1,079', $product->formatted_price);
        $this->assertEquals('₹1,699', $product->formatted_mrp);
        $this->assertEquals(36, $product->discount_percentage);
    }

    public function test_catalog_browsing_service_listing_card_shows_discounted_price(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000,
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $category = Category::firstOrCreate(
            ['slug' => 'kurta-set'],
            ['name' => 'Kurta Set', 'is_visible' => true]
        );

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set',
            'slug' => 'lime-green-chevron-kurta-set-2',
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'brand' => 'DHANSREE ETHNIC',
        ]);

        $colorOpt = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Color',
            'position' => 1,
        ]);
        $yellowVal = ProductOptionValue::create([
            'option_id' => $colorOpt->id,
            'value' => 'Yellow',
            'position' => 1,
        ]);

        $var = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-YLW-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'color_yellow',
            'weight_grams' => 300,
            'position' => 1,
        ]);
        $var->optionValues()->attach($yellowVal->id, ['option_id' => $colorOpt->id]);

        $this->addStockToVariant($var, 10);

        $service = app(CatalogBrowsingService::class);
        $cards = $service->expandProductsForListing(collect([$product]));

        $this->assertNotEmpty($cards);
        $card = $cards->first();
        $this->assertEquals('₹1,079', $card->formatted_price);
        $this->assertEquals('₹1,699', $card->formatted_mrp);
        $this->assertEquals(36, $card->discount_percentage);
    }

    public function test_product_detail_page_renders_discounted_price_and_promo_badge(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000,
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $category = Category::firstOrCreate(
            ['slug' => 'kurta-set'],
            ['name' => 'Kurta Set', 'is_visible' => true]
        );

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set',
            'slug' => 'lime-green-chevron-kurta-set-3',
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'brand' => 'DHANSREE ETHNIC',
        ]);

        $var = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-PDP-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'standard',
            'weight_grams' => 300,
            'position' => 1,
        ]);

        $this->addStockToVariant($var, 10);

        $response = $this->get('/product/' . $product->slug);
        $response->assertStatus(200);
        $response->assertSee('₹1,079');
        $response->assertSee('36% OFF');
        $response->assertSee('FESTIVE10');
    }

    public function test_checkout_auto_applies_default_coupon_in_quote_and_order(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000, // 10%
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set',
            'slug' => 'lime-green-chevron-kurta-set-4',
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $var = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-CHK-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'standard',
            'weight_grams' => 300,
            'position' => 1,
        ]);

        $this->addStockToVariant($var, 10);

        /** @var CheckoutService $checkoutService */
        $checkoutService = app(CheckoutService::class);

        // Quote without passing coupon code (couponCode = null)
        $quote = $checkoutService->quote([
            ['variant_id' => $var->id, 'quantity' => 1],
        ]);

        $this->assertNotNull($quote['coupon']);
        $this->assertEquals('FESTIVE10', $quote['coupon']->code);
        $this->assertEquals(11990, $quote['discount_paise']); // 10% of 119900 = 11990
        $this->assertEquals(119.9, $quote['discount_rupees']);

        // Placing order without passing coupon code in data
        $orderResult = $checkoutService->placeOrder([
            'cart_lines' => [
                ['variant_id' => $var->id, 'quantity' => 1],
            ],
            'contact' => [
                'name' => 'Pooja Sharma',
                'email' => 'pooja@example.com',
                'phone' => '9876543210',
            ],
            'shipping_address' => [
                'recipient_name' => 'Pooja Sharma',
                'line1' => 'Plot 45, MG Road',
                'city' => 'Jaipur',
                'state_code' => '08',
                'postal_code' => '302001',
                'phone' => '9876543210',
            ],
            'payment_method' => 'COD',
        ]);

        $order = $orderResult['order'];
        $this->assertEquals(11990, $order->discount_paise);
        $this->assertDatabaseHas('coupon_redemptions', [
            'order_id' => $order->id,
            'discount_paise' => 11990,
        ]);
    }

    public function test_admin_request_preserves_raw_base_prices(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000,
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set Admin',
            'slug' => 'lime-green-chevron-kurta-set-admin',
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'brand' => 'DHANSREE ETHNIC',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-ADM-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'standard',
            'weight_grams' => 300,
            'position' => 1,
        ]);

        $product->load('variants');

        // Simulate admin request
        $request = \Illuminate\Http\Request::create('/admin/catalog/products', 'GET');
        app()->instance('request', $request);

        $this->assertEquals('₹1,199', $product->formatted_price);
        $this->assertEquals('₹1,699', $product->formatted_mrp);
        $this->assertEquals(29, $product->discount_percentage);
    }

    public function test_cart_page_renders_coupon_discount_summary(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000,
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set Cart',
            'slug' => 'lime-green-chevron-kurta-set-cart',
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'brand' => 'DHANSREE ETHNIC',
        ]);

        $var = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-CRT-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'standard',
            'weight_grams' => 300,
            'position' => 1,
        ]);

        $this->addStockToVariant($var, 10);

        // Put item in session cart
        $cartData = [
            $var->id => ['quantity' => 1],
        ];

        $response = $this->withSession([\App\Services\Storefront\CartService::SESSION_KEY => $cartData])
            ->get('/cart');

        $response->assertStatus(200);
        $response->assertSee('FESTIVE10');
        $response->assertSee('−₹120');
        $response->assertSee('₹1,079');
    }

    public function test_quote_endpoint_allows_coupon_removal(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'FESTIVE10'],
            [
                'type' => CouponType::PERCENTAGE,
                'value' => 1000,
                'is_active' => true,
            ]
        );

        $store = StoreSettings::current();
        $store->update(['featured_coupon_code' => 'FESTIVE10']);
        StoreSettings::clearCouponCache();

        $product = Product::create([
            'name' => 'Lime Green Chevron Kurta Set Quote',
            'slug' => 'lime-green-chevron-kurta-set-quote',
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
            'brand' => 'DHANSREE ETHNIC',
        ]);

        $var = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CHEVRON-QTE-M',
            'price_paise' => 119900,
            'mrp_paise' => 169900,
            'option_signature' => 'standard',
            'weight_grams' => 300,
            'position' => 1,
        ]);

        $this->addStockToVariant($var, 10);

        $cartData = [
            $var->id => ['quantity' => 1],
        ];

        // Explicit empty string removes coupon
        $response = $this->withSession([\App\Services\Storefront\CartService::SESSION_KEY => $cartData])
            ->postJson('/checkout/quote', [
                'coupon_code' => '',
                'state_code' => '08',
                'payment_method' => 'COD',
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(0, $data['quote']['discount_paise']);
        $this->assertNull($data['quote']['coupon']);
    }
}
