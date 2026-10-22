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

    public function test_product_detail_page_renders_with_schema_and_sizing(): void
    {
        $product = Product::where('status', 'ACTIVE')
            ->has('variants')
            ->first();

        $this->assertNotNull($product);

        $response = $this->get('/product/' . $product->slug);

        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee('Add to Bag');
        $response->assertSee('https://schema.org/');
        $response->assertSee('"@type": "Product"', false);
        $response->assertSee('pictures');
        $response->assertSee('lg:grid-cols-2');
        $response->assertSee('scale(2.2)', false);

        // Test kurti size chart modal
        $kurtiProduct = Product::where('slug', 'women-solid-cotton-straight-short-kurta-with-v-notch-neckline-red')->first();
        if ($kurtiProduct) {
            $kurtiResponse = $this->get('/product/' . $kurtiProduct->slug);
            $kurtiResponse->assertStatus(200);
            $kurtiResponse->assertSee('Size chart');
            $kurtiResponse->assertSee('Size guide');
            $kurtiResponse->assertSee('Size Chart – Garment Measurements (in inches)');
            $kurtiResponse->assertSee('Bust');
            $kurtiResponse->assertSee('Waist');
        }
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
            '/return-policy',
            '/shipping-policy',
            '/terms',
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertSee('DHANSREE');
            $response->assertSee('Email Support');
        }
    }
}
