<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SeoRedirect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use DatabaseTransactions;

    public function test_homepage_renders_women_fashion_hub(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('DHANSREE');
        $response->assertSee('Ethnic Elegance');
        $response->assertSee('Smart Living &amp; Gadgets', false);
        $response->assertSee('Festive Signature Edit');
    }

    public function test_smart_living_hub_renders_tech_essentials(): void
    {
        $response = $this->get('/smart-living');

        $response->assertStatus(200);
        $response->assertSee('Better Products');
        $response->assertSee('Smarter Living');
        $response->assertSee("Women's Ethnic Wear", false);
        $response->assertSee('Direct-to-Customer Value');
    }

    public function test_category_page_renders_with_products(): void
    {
        $category = Category::where('is_visible', true)
            ->whereNull('archived_at')
            ->first();

        $this->assertNotNull($category);

        $response = $this->get('/' . $category->slug);

        $response->assertStatus(200);
        $response->assertSee($category->name);
        $response->assertSee('Sort By');
        $response->assertSee('Price Range');
    }

    public function test_category_page_sorting_low_high_and_dynamic_variation_filters(): void
    {
        // 1. Verify Price: High to Low with empty min/max does not throw 500 error
        $responseDesc = $this->get('/women-two-piece-sets-co-ord?sort=price_desc&min_price=&max_price=');
        $responseDesc->assertStatus(200);
        $responseDesc->assertSee('Price: High to Low');

        // 2. Verify Price: Low to High does not throw 500 error
        $responseAsc = $this->get('/women-two-piece-sets-co-ord?sort=price_asc&min_price=&max_price=');
        $responseAsc->assertStatus(200);
        $responseAsc->assertSee('Price: Low to High');

        // 3. Dynamic options: women-two-piece-sets-co-ord has products with Color and Size options
        $responseDesc->assertSee('Color');
        $responseDesc->assertSee('Pink');
        $responseDesc->assertSee('Size');
        $responseDesc->assertSee('M');

        // 4. Filtering by specific color works
        $colorFilterResponse = $this->get('/women-two-piece-sets-co-ord?color=Pink');
        $colorFilterResponse->assertStatus(200);
        $colorFilterResponse->assertSee('Active:');
        $colorFilterResponse->assertSee('Color:');
        $colorFilterResponse->assertSee('Pink');

        // 5. Filtering by size works
        $sizeFilterResponse = $this->get('/women-two-piece-sets-co-ord?size=M');
        $sizeFilterResponse->assertStatus(200);
        $sizeFilterResponse->assertSee('Active:');
        $sizeFilterResponse->assertSee('Size:');
        $sizeFilterResponse->assertSee('M');

        // 6. Categories without Size/Color options (e.g. smart-gadgets) dynamically omit them
        $gadgetResponse = $this->get('/smart-gadgets');
        $gadgetResponse->assertStatus(200);
        $gadgetResponse->assertDontSee('<h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Size</h3>', false);
    }

    public function test_product_detail_page_renders_with_schema_and_sizing(): void
    {
        $kurtiProduct = Product::find(3);
        $this->assertNotNull($kurtiProduct, 'Kurti product should exist in test database');

        $kurtiResponse = $this->get('/product/' . $kurtiProduct->slug);
        $kurtiResponse->assertStatus(200);
        $kurtiResponse->assertSee($kurtiProduct->name);
        $kurtiResponse->assertSee('Add to Bag');
        $kurtiResponse->assertSee('https://schema.org/');
        $kurtiResponse->assertSee('"@type": "Product"', false);
        $kurtiResponse->assertSee('pictures');
        $kurtiResponse->assertSee('lg:grid-cols-2');
        $kurtiResponse->assertSee('aspect-[4/5]', false);
        $kurtiResponse->assertSee('Size chart');
        $kurtiResponse->assertSee('Size guide');
        $kurtiResponse->assertSee('Size Chart – Garment Measurements (in inches)');
        $kurtiResponse->assertSee('Bust');
        $kurtiResponse->assertSee('Waist');
        $kurtiResponse->assertSee('Fabric');
        $kurtiResponse->assertSee('Country of origin');

        // 2. Test Smart Living product (Square cards, larger grid, no size chart, no select size option)
        $smartProduct = Product::where('slug', 'vintage-t-9-trimmer')->first();
        $this->assertNotNull($smartProduct, 'Smart living product should exist in test database');

        $smartResponse = $this->get('/product/' . $smartProduct->slug);
        $smartResponse->assertStatus(200);
        $smartResponse->assertSee($smartProduct->name);
        $smartResponse->assertSee('aspect-square');
        $smartResponse->assertSee('lg:grid-cols-12');
        $smartResponse->assertDontSee('Size chart');
        $smartResponse->assertDontSee('Select Size / Option:');
    }

    public function test_cart_empty_state_and_ajax_lifecycle(): void
    {
        // 1. Empty cart page
        $emptyResponse = $this->get('/cart');
        $emptyResponse->assertStatus(200);
        $emptyResponse->assertSee('A little room for something lovely');

        // 2. Add an item via AJAX
        $variant = ProductVariant::whereNull('archived_at')
            ->whereHas('product', fn($q) => $q->where('status', 'ACTIVE'))
            ->first();

        $this->assertNotNull($variant);

        $addResponse = $this->postJson('/cart/add', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $addResponse->assertStatus(200);
        $addResponse->assertJson([
            'success' => true,
            'cart' => [
                'total_quantity' => 2,
            ],
        ]);

        // 3. Cart Data JSON endpoint
        $dataResponse = $this->getJson('/cart/data');
        $dataResponse->assertStatus(200);
        $dataResponse->assertJsonPath('total_quantity', 2);

        // 4. Update quantity
        $updateResponse = $this->postJson('/cart/update', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('cart.total_quantity', 1);

        // 5. Filled cart page now shows item
        $filledResponse = $this->get('/cart');
        $filledResponse->assertStatus(200);
        $filledResponse->assertSee($variant->product->name);
        $filledResponse->assertSee('Proceed to Checkout');

        // 6. Remove item
        $removeResponse = $this->postJson('/cart/remove', [
            'variant_id' => $variant->id,
        ]);
        $removeResponse->assertStatus(200);
        $removeResponse->assertJsonPath('cart.total_quantity', 0);
    }

    public function test_search_functionality(): void
    {
        $response = $this->get('/search?q=cotton');
        $response->assertStatus(200);
        $response->assertSee('Search results for');
        $response->assertSee('cotton');

        $emptySearch = $this->get('/search?q=thiskeyworddoesnotexist999');
        $emptySearch->assertStatus(200);
        $emptySearch->assertSee('No results found');
    }

    public function test_seo_301_redirect_for_categories_and_products(): void
    {
        // 1. Create a 301 category redirect
        SeoRedirect::create([
            'source_path' => '/old-ethnic-collection',
            'destination_path' => '/kurta-set',
            'status_code' => 301,
            'origin' => 'AUTOMATIC',
            'is_active' => true,
        ]);

        $response = $this->get('/old-ethnic-collection');
        $response->assertRedirect('/kurta-set');
        $response->assertStatus(301);

        // 2. Create a 301 product redirect
        SeoRedirect::create([
            'source_path' => '/product/old-product-slug',
            'destination_path' => '/product/new-product-slug',
            'status_code' => 301,
            'origin' => 'AUTOMATIC',
            'is_active' => true,
        ]);

        $prodResponse = $this->get('/product/old-product-slug');
        $prodResponse->assertRedirect('/product/new-product-slug');
        $prodResponse->assertStatus(301);
    }

    public function test_newsletter_subscription_modal(): void
    {
        // Subscribe with valid email and explicit consent
        $response = $this->postJson('/newsletter/subscribe', [
            'email' => 'vip.shopper@example.com',
            'consent' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'code' => 'WELCOME50',
        ]);

        // Duplicate subscription returns friendly message
        $dupResponse = $this->postJson('/newsletter/subscribe', [
            'email' => 'vip.shopper@example.com',
            'consent' => true,
        ]);

        $dupResponse->assertStatus(200);
        $dupResponse->assertJson([
            'success' => true,
        ]);

        // Missing consent fails validation
        $invalidResponse = $this->postJson('/newsletter/subscribe', [
            'email' => 'noconsent@example.com',
            'consent' => false,
        ]);

        $invalidResponse->assertStatus(422);
    }

    public function test_all_policy_and_content_pages_render(): void
    {
        $pages = [
            '/about',
            '/contact',
            '/faq',
            '/privacy-policy',
            '/shipping-policy',
            '/terms',
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertSee('DHANSREE');
            $response->assertSee('Email Support');
        }

        // Return policy returns 200 if visible, or 404 if switched off by store owner
        $returnPage = \App\Models\ContentPage::where('key', 'RETURN_POLICY')->first();
        $expectedStatus = ($returnPage && $returnPage->is_visible) ? 200 : 404;
        $this->get('/return-policy')->assertStatus($expectedStatus);
    }
}
