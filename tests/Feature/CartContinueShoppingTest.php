<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartContinueShoppingTest extends TestCase
{
    use DatabaseTransactions;

    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Anarkali Kurta Sets',
            'slug' => 'anarkali-kurta-sets',
            'is_visible' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ajrakh Print Pure Cotton Anarkali Kurta with Pant and Mulmul Dupatta',
            'slug' => 'ajrakh-print-pure-cotton-anarkali-kurta-with-pant-and-mulmul-dupatta',
            'status' => ProductStatus::ACTIVE,
            'published_at' => now(),
        ]);

        $variantService = app(ProductVariantService::class);
        $variantService->syncOptions($this->product, [
            ['name' => 'Size', 'values' => ['M']],
        ]);
        $this->product->refresh();
        $sizeOption = $this->product->options->firstWhere('name', 'Size');
        $sizeVal = $sizeOption->values->firstWhere('value', 'M');

        $this->variant = $variantService->createVariant($this->product, [
            'sku' => 'AJRAKH-M-' . uniqid(),
            'price_rupees' => 1566.00,
            'mrp_rupees' => 2099.00,
            'option_value_ids' => [$sizeVal->id],
            'stock_quantity' => 20,
        ]);
    }

    public function test_continue_shopping_redirects_to_product_page_when_landing_from_product(): void
    {
        $productUrl = route('store.product', $this->product->slug);

        // Add item to cart
        $this->postJson('/cart/add', [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ])->assertOk();

        // Visit cart with product page as referer
        $response = $this->withHeaders([
            'referer' => $productUrl,
        ])->get('/cart');

        $response->assertOk();
        $response->assertSee('Continue Shopping');
        $response->assertSee('href="' . $productUrl . '"', false);
        $this->assertEquals($productUrl, session('continue_shopping_url'));
    }

    public function test_continue_shopping_redirects_to_category_page_when_landing_from_category(): void
    {
        $categoryUrl = url('/' . $this->category->slug);

        // Add item to cart
        $this->postJson('/cart/add', [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ])->assertOk();

        // Visit cart with category page as referer
        $response = $this->withHeaders([
            'referer' => $categoryUrl,
        ])->get('/cart');

        $response->assertOk();
        $response->assertSee('href="' . $categoryUrl . '"', false);
        $this->assertEquals($categoryUrl, session('continue_shopping_url'));
    }

    public function test_cart_item_update_or_reload_preserves_continue_shopping_product_url(): void
    {
        $productUrl = route('store.product', $this->product->slug);

        // Add item to cart
        $this->postJson('/cart/add', [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ])->assertOk();

        // Initial visit from product page
        $this->withHeaders([
            'referer' => $productUrl,
        ])->get('/cart')->assertOk();

        $this->assertEquals($productUrl, session('continue_shopping_url'));

        // Update quantity from cart page (referer is now /cart)
        $this->withHeaders([
            'referer' => url('/cart'),
        ])->postJson('/cart/update', [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ])->assertOk();

        // Reload cart page (referer is /cart)
        $reloadResponse = $this->withHeaders([
            'referer' => url('/cart'),
        ])->get('/cart');

        $reloadResponse->assertOk();
        // continue_shopping_url MUST NOT be overwritten with /cart
        $reloadResponse->assertSee('href="' . $productUrl . '"', false);
        $this->assertEquals($productUrl, session('continue_shopping_url'));
    }

    public function test_continue_shopping_supports_explicit_return_url_query_parameter(): void
    {
        $customShoppingUrl = url('/smart-living');

        $response = $this->get('/cart?return_url=' . urlencode($customShoppingUrl));
        $response->assertOk();
        $response->assertSee('href="' . $customShoppingUrl . '"', false);
        $this->assertEquals($customShoppingUrl, session('continue_shopping_url'));
    }

    public function test_external_referrer_defaults_to_home(): void
    {
        $response = $this->withHeaders([
            'referer' => 'https://evil-phishing.com/some-page',
        ])->get('/cart');

        $response->assertOk();
        // Should default to home page
        $response->assertSee('href="' . url('/') . '"', false);
        $this->assertNotEquals('https://evil-phishing.com/some-page', session('continue_shopping_url'));
    }

    public function test_empty_cart_view_renders_continue_shopping_button(): void
    {
        $productUrl = route('store.product', $this->product->slug);

        $response = $this->withHeaders([
            'referer' => $productUrl,
        ])->get('/cart');

        $response->assertOk();
        $response->assertSee('A little room for something lovely');
        $response->assertSee('Continue Shopping');
        $response->assertSee('href="' . $productUrl . '"', false);
    }
}
