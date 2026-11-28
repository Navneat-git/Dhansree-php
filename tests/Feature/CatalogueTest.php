<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $admin;
    protected string $rawAdminToken;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'catalogue_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Catalogue Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawAdminToken = $sessionData['token'];

        $this->category = Category::create([
            'name' => 'Anarkali Kurta Sets',
            'slug' => 'anarkali-kurta-sets-' . uniqid(),
            'is_visible' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pure Cotton Sanganeri Print Anarkali Kurta Set',
            'slug' => 'pure-cotton-sanganeri-anarkali-' . uniqid(),
            'brand' => 'Dhansree Trends',
            'status' => 'ACTIVE',
            'published_at' => now(),
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'ANARKALI-SNG-' . uniqid(),
            'price_paise' => 149900,
            'mrp_paise' => 249900,
            'cost_paise' => 80000,
            'option_signature' => 'XL',
            'weight_grams' => 600,
            'position' => 1,
        ]);
    }

    public function test_catalogue_page_renders_with_prices_delivery_options_and_buy_now_buttons(): void
    {
        $response = $this->get('/catalogue');

        $response->assertStatus(200);
        $response->assertSee("Women's Ethnic &amp; Festive Collection", false);
        $response->assertSee('FREE Home Delivery');
        $response->assertSee('Cash on Delivery');
        $response->assertSee('Delhivery');
        $response->assertSee('Buy Now');
        $response->assertSee('Share on WhatsApp');
        $response->assertSee('Check Delivery Timeline');
        $response->assertSee('₹1,499.00');
        $response->assertSee('₹2,499.00');
        $response->assertSee($this->product->name);
    }

    public function test_catalogue_alias_routes_render_the_same_catalogue(): void
    {
        $resp1 = $this->get('/catalog');
        $resp1->assertStatus(200);
        $resp1->assertSee("Women's Ethnic &amp; Festive Collection", false);

        $resp2 = $this->get('/women-catalogue');
        $resp2->assertStatus(200);
        $resp2->assertSee("Women's Ethnic &amp; Festive Collection", false);
    }

    public function test_catalogue_pincode_check_endpoint(): void
    {
        $response = $this->postJson('/catalogue/check-pincode', [
            'pincode' => '302001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'serviceable' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'serviceable',
            'message',
        ]);
    }

    public function test_admin_catalog_share_hub_displays_digital_catalogue_quick_share_banner(): void
    {
        $response = $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawAdminToken)
            ->get('/admin/catalog/share');

        $response->assertStatus(200);
        $response->assertSee("Women's Fashion Digital Catalogue with Price &amp; Delivery", false);
        $response->assertSee('/catalogue');
        $response->assertSee('Copy Catalogue Link');
        $response->assertSee('Open Live Catalogue');
    }
}
