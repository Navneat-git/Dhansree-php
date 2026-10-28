<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;
use App\Services\Storefront\CatalogBrowsingService;
use App\Support\Auth\AdminSessionManager;
use App\Support\Security\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductRankingTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $sessionToken;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $hasher = new PasswordHasher();
        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'ranking_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Ranking Test Admin',
            'password_hash' => $hasher->hash('SecretPassword123!'),
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $session = AdminSessionManager::createSession($this->admin);
        $this->sessionToken = $session['token'];

        $this->category = Category::firstOrCreate(
            ['slug' => 'kurta-set'],
            ['name' => 'Kurta Set', 'is_visible' => true, 'position' => 1]
        );
    }

    private function createTestProduct(string $name, ?int $ranking = null, ?\DateTimeInterface $publishedAt = null): Product
    {
        $slug = 'test-prod-' . uniqid();
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'category_id' => $this->category->id,
            'status' => ProductStatus::ACTIVE,
            'ranking' => $ranking,
            'published_at' => $publishedAt ?? now(),
            'brand' => 'Dhansree',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => strtoupper($slug),
            'price_paise' => 99900,
            'mrp_paise' => 149900,
            'weight_grams' => 250,
            'position' => 0,
            'option_signature' => '',
        ]);

        $location = InventoryLocation::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main location', 'is_active' => true, 'is_default' => true]
        );

        InventoryItem::firstOrCreate(
            ['variant_id' => $variant->id],
            ['backorder_policy' => 'STOP_SALE']
        );

        InventoryLevel::firstOrCreate(
            ['variant_id' => $variant->id, 'location_id' => $location->id],
            ['on_hand' => 10, 'reserved' => 0]
        );

        return $product;
    }

    public function test_product_model_persists_and_casts_ranking(): void
    {
        $product = $this->createTestProduct('Ranked Kurti', 1);
        $this->assertSame(1, $product->ranking);

        $product->refresh();
        $this->assertSame(1, $product->ranking);
        $this->assertIsInt($product->ranking);

        $product->update(['ranking' => null]);
        $product->refresh();
        $this->assertNull($product->ranking);
    }

    public function test_admin_can_quick_update_ranking_via_ajax_endpoint(): void
    {
        $product = $this->createTestProduct('Quick Rank Item', null);
        $this->assertNull($product->ranking);

        $cookieName = AdminSessionManager::getCookieName();
        $response = $this->withUnencryptedCookie($cookieName, $this->sessionToken)
            ->withToken($this->sessionToken)
            ->withSession(['admin_session_token' => $this->sessionToken])
            ->postJson("/admin/catalog/products/{$product->id}/ranking", [
                'ranking' => 5,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'ranking' => 5,
        ]);

        $product->refresh();
        $this->assertSame(5, $product->ranking);

        // Update to clear ranking (null)
        $clearResponse = $this->withUnencryptedCookie($cookieName, $this->sessionToken)
            ->withToken($this->sessionToken)
            ->withSession(['admin_session_token' => $this->sessionToken])
            ->postJson("/admin/catalog/products/{$product->id}/ranking", [
                'ranking' => '',
            ]);

        $clearResponse->assertStatus(200);
        $product->refresh();
        $this->assertNull($product->ranking);
    }

    public function test_admin_can_update_ranking_via_standard_product_update_form(): void
    {
        $product = $this->createTestProduct('Edit Form Item', 10);

        $cookieName = AdminSessionManager::getCookieName();
        $response = $this->withUnencryptedCookie($cookieName, $this->sessionToken)
            ->put("/admin/catalog/products/{$product->id}", [
                'name' => 'Edit Form Item Updated',
                'ranking' => 2,
                'category_id' => $this->category->id,
            ]);

        $response->assertRedirect("/admin/catalog/products/{$product->id}/edit");
        $product->refresh();
        $this->assertSame(2, $product->ranking);
        $this->assertSame('Edit Form Item Updated', $product->name);
    }

    public function test_admin_catalog_listing_sorts_by_ranking(): void
    {
        $prodUnranked = $this->createTestProduct('Unranked Product', null);
        $prodRank2 = $this->createTestProduct('Rank Two Product', 2);
        $prodRank1 = $this->createTestProduct('Rank One Product', 1);

        $productService = app(ProductService::class);
        $list = $productService->list(['sort' => 'ranking'], 50);

        $items = $list->items();
        $ids = array_map(fn($p) => $p->id, $items);

        $posRank1 = array_search($prodRank1->id, $ids);
        $posRank2 = array_search($prodRank2->id, $ids);
        $posUnranked = array_search($prodUnranked->id, $ids);

        $this->assertNotFalse($posRank1);
        $this->assertNotFalse($posRank2);
        $this->assertNotFalse($posUnranked);

        // Rank 1 must come before Rank 2
        $this->assertLessThan($posRank2, $posRank1, 'Product with Rank 1 should appear before Rank 2');
        // Rank 2 must come before unranked
        $this->assertLessThan($posUnranked, $posRank2, 'Product with Rank 2 should appear before unranked');
    }

    public function test_storefront_home_page_rail_orders_by_ranking(): void
    {
        // Create unranked older product, unranked newer product, rank 2 product, rank 1 product
        $unrankedOld = $this->createTestProduct('Rail Unranked Old', null, now()->subDays(5));
        $unrankedNew = $this->createTestProduct('Rail Unranked New', null, now());
        $ranked2 = $this->createTestProduct('Rail Rank 2', 2, now()->subDays(10));
        $ranked1 = $this->createTestProduct('Rail Rank 1', 1, now()->subDays(10));

        $browsingService = app(CatalogBrowsingService::class);
        $homeData = $browsingService->getWomenFashionHomeData();

        $featuredCards = $homeData['featuredProducts'];
        $productIds = $featuredCards->pluck('product_id')->all();

        $posRank1 = array_search($ranked1->id, $productIds);
        $posRank2 = array_search($ranked2->id, $productIds);

        $this->assertNotFalse($posRank1, 'Rank 1 product must be in featured cards');
        $this->assertNotFalse($posRank2, 'Rank 2 product must be in featured cards');

        // Rank 1 must precede Rank 2
        $this->assertLessThan($posRank2, $posRank1, 'Rank 1 must precede Rank 2 on Home featured rail');

        // If unranked products are also present in featured rail, they must be after ranked products
        $posUnrankedNew = array_search($unrankedNew->id, $productIds);
        if ($posUnrankedNew !== false) {
            $this->assertLessThan($posUnrankedNew, $posRank1, 'Rank 1 must precede unranked on Home rail');
            $this->assertLessThan($posUnrankedNew, $posRank2, 'Rank 2 must precede unranked on Home rail');
        }
    }

    public function test_storefront_category_listing_orders_by_ranking(): void
    {
        $unranked = $this->createTestProduct('Cat Unranked', null, now());
        $ranked2 = $this->createTestProduct('Cat Rank 2', 2, now()->subDays(2));
        $ranked1 = $this->createTestProduct('Cat Rank 1', 1, now()->subDays(3));

        $browsingService = app(CatalogBrowsingService::class);
        $catData = $browsingService->getCategoryPageData('kurta-set');

        $productCards = $catData['productCards'];
        $productIds = collect($productCards)->pluck('product_id')->all();

        $posRank1 = array_search($ranked1->id, $productIds);
        $posRank2 = array_search($ranked2->id, $productIds);
        $posUnranked = array_search($unranked->id, $productIds);

        $this->assertNotFalse($posRank1, 'Rank 1 product must be in category products');
        $this->assertNotFalse($posRank2, 'Rank 2 product must be in category products');
        $this->assertNotFalse($posUnranked, 'Unranked product must be in category products');

        $this->assertLessThan($posRank2, $posRank1, 'Rank 1 product must be before Rank 2 in category listing');
        $this->assertLessThan($posUnranked, $posRank2, 'Rank 2 product must be before unranked in category listing');
    }

    public function test_storefront_collection_listing_orders_by_ranking(): void
    {
        $unranked = $this->createTestProduct('Col Unranked', null, now());
        $ranked2 = $this->createTestProduct('Col Rank 2', 2, now()->subDays(2));
        $ranked1 = $this->createTestProduct('Col Rank 1', 1, now()->subDays(3));

        $browsingService = app(CatalogBrowsingService::class);
        $colData = $browsingService->getCollectionPageData('women-fashion');

        $productCards = $colData['productCards'];
        $productIds = collect($productCards)->pluck('product_id')->all();

        $posRank1 = array_search($ranked1->id, $productIds);
        $posRank2 = array_search($ranked2->id, $productIds);

        $this->assertNotFalse($posRank1, 'Rank 1 product must be in women-fashion collection');
        $this->assertNotFalse($posRank2, 'Rank 2 product must be in women-fashion collection');

        $this->assertLessThan($posRank2, $posRank1, 'Rank 1 must precede Rank 2 in collection listing');
    }
}
