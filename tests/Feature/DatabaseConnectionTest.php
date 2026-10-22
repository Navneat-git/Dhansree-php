<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Point DB connection to real mysql database
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'dhansree_prod',
            'database.connections.mysql.username' => 'dhansree',
            'database.connections.mysql.password' => 'navneat@123',
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    public function test_mysql_connection_reads_real_database(): void
    {
        $dbName = DB::connection('mysql')->getDatabaseName();
        $this->assertEquals('dhansree_prod', $dbName);

        $tables = DB::connection('mysql')->select('SHOW TABLES');
        $this->assertNotEmpty($tables);
    }

    public function test_can_query_admin_user_from_database(): void
    {
        $admin = AdminUser::first();
        $this->assertNotNull($admin, 'An admin user should exist in the database');
        $this->assertInstanceOf(AdminUser::class, $admin);
        $this->assertNotEmpty($admin->email);
        $this->assertInstanceOf(\App\Enums\AdminUserStatus::class, $admin->status);
    }

    public function test_can_query_categories_tree_from_database(): void
    {
        $categories = Category::all();
        $this->assertNotEmpty($categories, 'Categories should exist in the database');

        $rootCategories = Category::whereNull('parent_id')->get();
        $this->assertNotEmpty($rootCategories);

        foreach ($categories as $cat) {
            $this->assertIsInt($cat->id);
            $this->assertNotEmpty($cat->name);
            $this->assertNotEmpty($cat->slug);
            $this->assertIsBool($cat->is_visible);
        }
    }

    public function test_can_query_products_with_variants_from_database(): void
    {
        $products = Product::with(['category', 'variants'])->get();
        $this->assertNotEmpty($products, 'Products should exist in the database');

        foreach ($products as $prod) {
            $this->assertIsInt($prod->id);
            $this->assertNotEmpty($prod->name);
            $this->assertNotEmpty($prod->slug);
            $this->assertInstanceOf(\App\Enums\ProductStatus::class, $prod->status);

            // Check variant relations
            foreach ($prod->variants as $variant) {
                $this->assertIsInt($variant->id);
                $this->assertIsInt($variant->price_paise);
                $this->assertGreaterThan(0, $variant->price_paise);
                $this->assertIsFloat($variant->price_rupees);
            }
        }
    }

    public function test_store_settings_singleton_from_database(): void
    {
        $settings = StoreSettings::find(1);
        $this->assertNotNull($settings, 'StoreSettings id 1 should exist in the database');
        $this->assertIsBool($settings->whatsapp_enabled);
        $this->assertIsBool($settings->cod_enabled);
    }
}
