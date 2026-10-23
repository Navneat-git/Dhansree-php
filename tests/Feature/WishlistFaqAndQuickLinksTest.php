<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\CategoryContent;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\FaqEntry;
use App\Models\Product;
use App\Models\ProductContent;
use App\Models\QuickLink;
use App\Models\QuickLinkGroup;
use App\Models\WishlistItem;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WishlistFaqAndQuickLinksTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $adminToken;
    private Product $product;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();

        $superRole = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'admin_test_' . uniqid() . '@dhansree.test',
            'name' => 'Test Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($superRole->id);

        $session = AdminSessionManager::createSession($this->admin);
        $this->adminToken = $session['token'];

        $this->category = Category::where('slug', 'women-fashion')->first()
            ?? Category::create([
                'name' => 'Women Fashion',
                'slug' => 'women-fashion',
                'position' => 0,
                'is_visible' => true,
            ]);

        $this->product = Product::where('status', ProductStatus::ACTIVE)->first()
            ?? Product::create([
                'name' => 'Test Product',
                'slug' => 'test-product-' . uniqid(),
                'status' => ProductStatus::ACTIVE,
                'category_id' => $this->category->id,
            ]);
    }

    private function actingAsAdmin(): self
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->adminToken);
    }

    public function test_guest_can_toggle_and_view_wishlist(): void
    {
        // 1. Initially wishlist IDs is empty
        $res = $this->getJson(route('storefront.wishlist.ids'));
        $res->assertOk();
        $this->assertEmpty($res->json('ids'));

        // 2. Toggle product into wishlist
        $toggleRes = $this->postJson(route('storefront.wishlist.toggle'), ['product_id' => $this->product->id]);
        $toggleRes->assertOk();
        $this->assertTrue($toggleRes->json('added'));
        $this->assertEquals(1, $toggleRes->json('count'));

        // 3. Check IDs endpoint reflects addition
        $res2 = $this->getJson(route('storefront.wishlist.ids'));
        $res2->assertOk();
        $this->assertContains($this->product->id, $res2->json('ids'));

        // 4. View /wishlist page
        $pageRes = $this->get(route('storefront.wishlist.index'));
        $pageRes->assertOk();
        $pageRes->assertSee($this->product->name);

        // 5. Remove product from wishlist
        $removeRes = $this->postJson(route('storefront.wishlist.remove'), ['product_id' => $this->product->id]);
        $removeRes->assertOk();
        $this->assertEquals(0, $removeRes->json('count'));

        // 6. Check empty state on wishlist page
        $emptyRes = $this->get(route('storefront.wishlist.index'));
        $emptyRes->assertOk();
        $emptyRes->assertSee('Your wishlist is currently empty');
    }

    public function test_admin_can_access_wishlists_index(): void
    {
        $res = $this->actingAsAdmin()->get(route('admin.wishlists.index'));
        $res->assertOk();
        $res->assertSee('Customer Wishlists');
        $res->assertSee('Most Wishlisted Products');
    }

    public function test_admin_can_update_product_faqs_and_redirect_preserves_tab(): void
    {
        // 1. Update to CUSTOM mode with 2 Q&A items
        $res = $this->actingAsAdmin()->post(route('admin.products.update-faqs', $this->product), [
            'faq_mode' => 'CUSTOM',
            'faqs' => [
                ['question' => 'Is this 100% Cotton?', 'answer' => 'Yes, pure breathable cotton.', 'position' => 0],
                ['question' => 'Can I exchange size?', 'answer' => 'Yes, within 7 days of delivery.', 'position' => 1],
            ],
        ]);

        $res->assertRedirect(route('admin.products.edit', ['product' => $this->product, 'tab' => 'faqs']));
        $res->assertSessionHas('active_tab', 'faqs');

        // Check DB
        $this->assertDatabaseHas('product_content', [
            'product_id' => $this->product->id,
            'faq_mode' => 'CUSTOM',
        ]);
        $this->assertDatabaseHas('faq_entries', [
            'product_id' => $this->product->id,
            'question' => 'Is this 100% Cotton?',
        ]);

        // Storefront product page displays custom FAQ
        $storeRes = $this->get('/product/' . $this->product->slug);
        $storeRes->assertOk();
        $storeRes->assertSee('Is this 100% Cotton?');
        $storeRes->assertSee('Yes, pure breathable cotton.');

        // 2. Set to NONE mode
        $noneRes = $this->actingAsAdmin()->post(route('admin.products.update-faqs', $this->product), [
            'faq_mode' => 'NONE',
        ]);
        $noneRes->assertRedirect(route('admin.products.edit', ['product' => $this->product, 'tab' => 'faqs']));

        // Storefront product page hides FAQ section completely
        $storeRes2 = $this->get('/product/' . $this->product->slug);
        $storeRes2->assertOk();
        $storeRes2->assertDontSee('Have Questions?');
        $storeRes2->assertDontSee('Is this 100% Cotton?');
    }

    public function test_quick_links_crud_and_storefront_rendering(): void
    {
        // 1. Admin views Quick Links index
        $indexRes = $this->actingAsAdmin()->get(route('admin.quick-links.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Quick Links');

        // 2. Admin creates a Quick Link Group
        $groupTitle = 'Festive Trends ' . uniqid();
        $createRes = $this->actingAsAdmin()->post(route('admin.quick-links.groups.store'), [
            'title' => $groupTitle,
            'show_on_home' => 1,
            'show_on_listing' => 1,
            'show_on_product' => 1,
            'is_visible' => 1,
            'position' => 0,
        ]);
        $createRes->assertRedirect(route('admin.quick-links.index'));

        $group = QuickLinkGroup::where('title', $groupTitle)->first();
        $this->assertNotNull($group);

        // Add links to group
        $this->actingAsAdmin()->post(route('admin.quick-links.links.store', $group->id), [
            'label' => 'Anarkali Suits',
            'path' => '/anarkali-kurta-sets',
            'position' => 0,
        ]);
        $this->actingAsAdmin()->post(route('admin.quick-links.links.store', $group->id), [
            'label' => 'Two Piece Co-ords',
            'path' => '/women-two-piece-sets-co-ord',
            'position' => 1,
        ]);

        // 3. Home page renders Quick Links
        $homeRes = $this->get('/');
        $homeRes->assertOk();
        $homeRes->assertSee('Anarkali Suits');
        $homeRes->assertSee('Two Piece Co-ords');

        // 4. Update Product Quick Links mode to CUSTOM with this group
        $group = QuickLinkGroup::where('title', 'like', 'Festive Trends%')->latest('id')->first();
        $this->assertNotNull($group);

        $prodQlRes = $this->actingAsAdmin()->post(route('admin.products.update-quick-links', $this->product), [
            'quick_links_mode' => 'CUSTOM',
            'quick_link_group_id' => $group->id,
        ]);
        $prodQlRes->assertRedirect(route('admin.products.edit', ['product' => $this->product, 'tab' => 'quick-links']));
        $prodQlRes->assertSessionHas('active_tab', 'quick-links');

        // Product page displays quick links
        $prodPage = $this->get('/product/' . $this->product->slug);
        $prodPage->assertOk();
        $prodPage->assertSee('Anarkali Suits');

        // 5. Disable quick links on product (NONE)
        $this->actingAsAdmin()->post(route('admin.products.update-quick-links', $this->product), [
            'quick_links_mode' => 'NONE',
        ]);

        $prodPage2 = $this->get('/product/' . $this->product->slug);
        $prodPage2->assertOk();
        $prodPage2->assertDontSee('Anarkali Suits');
    }
}
