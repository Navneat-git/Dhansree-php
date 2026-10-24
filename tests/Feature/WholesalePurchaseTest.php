<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\WholesaleColorCode;
use App\Models\WholesalePurchase;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WholesalePurchaseTest extends TestCase
{
    use DatabaseTransactions;
    private AdminUser $admin;
    private string $adminToken;
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
            'email' => 'admin_ws_' . uniqid() . '@dhansree.test',
            'name' => 'Wholesale Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($superRole->id);

        $session = AdminSessionManager::createSession($this->admin);
        $this->adminToken = $session['token'];

        $this->category = Category::firstOrCreate(
            ['slug' => 'smart-gadgets'],
            ['name' => 'Smart Gadgets', 'position' => 0, 'is_visible' => true]
        );
    }

    private function actingAsAdmin(): self
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->adminToken);
    }

    public function test_admin_can_view_wholesale_index_and_tabs(): void
    {
        $res = $this->actingAsAdmin()->get(route('admin.wholesale.index'));
        $res->assertOk();
        $res->assertSeeText('Wholesale Procurement');
        $res->assertSeeText('Women Fashion');
        $res->assertSeeText('Smart Living');
    }

    public function test_admin_can_access_create_form(): void
    {
        $res = $this->actingAsAdmin()->get(route('admin.wholesale.create'));
        $res->assertOk();
        $res->assertSeeText('Add Wholesale Product Purchase');
        $res->assertSeeText('Women Fashion');
        $res->assertSeeText('Smart Living');
    }

    public function test_admin_can_create_women_fashion_entry_with_auto_generated_sku_pcode_and_pricing(): void
    {
        $res = $this->actingAsAdmin()->post(route('admin.wholesale.store'), [
            'purchase_type' => WholesalePurchase::TYPE_WOMEN_FASHION,
            'brand_code' => 'DS',
            'purchase_date' => '2026-04-19',
            'vcode' => 'ICF',
            'design_number' => '10',
            'category_name' => '3 SUIT SET',
            'color' => 'Blue',
            'size' => 'XL',
            'product_name' => 'Royal Blue Embroidered 3 Suit Set',
            'thread_count' => '60x60',
            'cost' => 1000.00,
            'gst_percent' => 5.0,
            'markup' => 1.5,
            'status' => 'Available',
        ]);

        $res->assertRedirect();

        $entry = WholesalePurchase::where('product_name', 'Royal Blue Embroidered 3 Suit Set')->first();
        $this->assertNotNull($entry);
        $this->assertEquals('ICF19042610', $entry->pcode);
        $this->assertEquals('S3', $entry->category_code);
        $this->assertEquals('BLU', $entry->color_code);
        $this->assertEquals('DSICF19042610-S3-BLU-XL', $entry->sku);
        $this->assertEquals(50.00, (float) $entry->gst_amount);
        $this->assertEquals(1050.00, (float) $entry->total_cost);
        $this->assertEquals(1575.00, (float) $entry->selling_price);
    }

    public function test_admin_can_create_women_fashion_with_multiple_sizes_creates_multiple_rows(): void
    {
        $res = $this->actingAsAdmin()->post(route('admin.wholesale.store'), [
            'purchase_type' => WholesalePurchase::TYPE_WOMEN_FASHION,
            'brand_code' => 'DS',
            'purchase_date' => '2026-04-19',
            'vcode' => 'ICF',
            'design_number' => '25',
            'category_name' => 'KURTI',
            'color' => 'Pink',
            'sizes' => ['S', 'M', 'L', 'XL'],
            'product_name' => 'Festive Pink Anarkali Kurti',
            'thread_count' => '60x60',
            'cost' => 600.00,
            'gst_percent' => 5.0,
            'markup' => 1.8,
            'status' => 'Available',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $entries = WholesalePurchase::where('product_name', 'Festive Pink Anarkali Kurti')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(4, $entries);

        $expectedSizes = ['S', 'M', 'L', 'XL'];
        foreach ($entries as $i => $entry) {
            $expectedSize = $expectedSizes[$i];
            $this->assertEquals($expectedSize, $entry->size);
            $this->assertEquals('ICF19042625', $entry->pcode);
            $this->assertEquals('KT', $entry->category_code);
            $this->assertEquals('PNK', $entry->color_code);
            $this->assertEquals("DSICF19042625-KT-PNK-{$expectedSize}", $entry->sku);
            $this->assertEquals(30.00, (float) $entry->gst_amount);
            $this->assertEquals(630.00, (float) $entry->total_cost);
            $this->assertEquals(1134.00, (float) $entry->selling_price);
        }
    }

    public function test_admin_can_create_smart_living_entry_with_auto_markup_pricing(): void
    {
        $res = $this->actingAsAdmin()->post(route('admin.wholesale.store'), [
            'purchase_type' => WholesalePurchase::TYPE_SMART_LIVING,
            'sku' => 'DS-CHG-001',
            'product_name' => 'Fast Charger 65W',
            'category_id' => $this->category->id,
            'cost_price' => 35.00,
            'markup' => 1.4,
            'retail_markup' => 2.0,
            'stock_qty' => 150,
            'moq' => '5 pcs',
            'brand' => 'Generic',
            'model' => 'CHG-Fast',
            'badge' => 'POPULAR',
            'status' => 'Available',
            'supplier' => 'Supplier A',
            'purchase_date' => '2026-04-20',
        ]);

        $res->assertRedirect();

        $entry = WholesalePurchase::where('sku', 'DS-CHG-001')->first();
        $this->assertNotNull($entry);
        $this->assertEquals(49.00, (float) $entry->wholesale_price);
        $this->assertEquals(70.00, (float) $entry->retail_price);
        $this->assertEquals(150, $entry->stock_qty);
        $this->assertEquals('Supplier A', $entry->supplier);
    }

    public function test_smart_living_sku_auto_picks_up_next_serial_number_when_base_sku_is_entered(): void
    {
        // 1. Create product with serial 061 (like DS-MSG-GUN-061)
        WholesalePurchase::create([
            'purchase_type' => WholesalePurchase::TYPE_SMART_LIVING,
            'sku' => 'DS-MSG-GUN-061',
            'product_name' => 'Handheld Percussion Massage Gun',
            'status' => 'Available',
            'cost_price' => 300.00,
        ]);

        $this->assertEquals('062', WholesalePurchase::getNextSmartLivingSerialNumber());

        // 2. Submit new product with base SKU DS-JWL-BOX (no serial)
        $res = $this->actingAsAdmin()->post(route('admin.wholesale.store'), [
            'purchase_type' => WholesalePurchase::TYPE_SMART_LIVING,
            'sku' => 'DS-JWL-BOX',
            'product_name' => 'Portable Jewelry Organizer Box',
            'status' => 'Available',
            'cost_price' => 200.00,
            'markup' => 1.5,
            'retail_markup' => 2.0,
        ]);

        $res->assertRedirect();

        // 3. Verify it was saved with auto-picked serial 062: DS-JWL-BOX-062
        $entry = WholesalePurchase::where('product_name', 'Portable Jewelry Organizer Box')->first();
        $this->assertNotNull($entry);
        $this->assertEquals('DS-JWL-BOX-062', $entry->sku);

        // 4. Next serial should now be 063
        $this->assertEquals('063', WholesalePurchase::getNextSmartLivingSerialNumber());
    }

    public function test_smart_living_next_serial_endpoint_returns_json(): void
    {
        WholesalePurchase::create([
            'purchase_type' => WholesalePurchase::TYPE_SMART_LIVING,
            'sku' => 'DS-TEST-061',
            'product_name' => 'Test Product',
            'status' => 'Available',
        ]);

        $res = $this->actingAsAdmin()->get(route('admin.wholesale.next-serial'));
        $res->assertOk();
        $res->assertJson([
            'success' => true,
            'next_serial' => '062',
        ]);
    }

    public function test_smart_living_sku_pads_short_digits_and_preserves_custom_serials(): void
    {
        // Two digits gets padded to 3 digits (e.g. DS-JWL-BOX-62 -> DS-JWL-BOX-062)
        $this->assertEquals('DS-JWL-BOX-062', WholesalePurchase::completeSmartLivingSku('DS-JWL-BOX-62'));

        // Existing 3-digit serial preserved
        $this->assertEquals('DS-MSG-GUN-061', WholesalePurchase::completeSmartLivingSku('DS-MSG-GUN-061'));
        $this->assertEquals('DS-JWL-BOX-099', WholesalePurchase::completeSmartLivingSku('DS-JWL-BOX-099'));
    }

    public function test_admin_can_upload_product_image_and_generate_public_url(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('test_product.jpg', 600, 600);

        $res = $this->actingAsAdmin()->post(route('admin.wholesale.store'), [
            'purchase_type' => WholesalePurchase::TYPE_SMART_LIVING,
            'sku' => 'DS-IMG-050',
            'product_name' => 'Product With Image',
            'status' => 'Available',
            'cost_price' => 100.00,
            'image' => $image,
        ]);

        $res->assertRedirect();

        $entry = WholesalePurchase::where('product_name', 'Product With Image')->first();
        $this->assertNotNull($entry);
        $this->assertNotNull($entry->image_path);
        $this->assertNotNull($entry->image_url);
        $this->assertStringContainsString('wholesale/', $entry->image_path);
        $this->assertStringContainsString('.webp', $entry->image_path);
        $this->assertStringContainsString(url('/media/'), $entry->image_url);
    }

    public function test_admin_can_update_wholesale_entry(): void
    {
        $entry = WholesalePurchase::create([
            'purchase_type' => WholesalePurchase::TYPE_WOMEN_FASHION,
            'sku' => 'DSOLD123',
            'product_name' => 'Old Dress',
            'category_name' => 'KURTI',
            'status' => 'Available',
            'cost' => 400.00,
            'gst_percent' => 5.0,
            'markup' => 1.5,
        ]);

        $res = $this->actingAsAdmin()->put(route('admin.wholesale.update', $entry), [
            'product_name' => 'Updated Cotton Kurti',
            'category_name' => 'KURTI',
            'status' => 'Available',
            'cost' => 500.00,
            'gst_percent' => 5.0,
            'markup' => 2.0,
        ]);

        $res->assertRedirect();

        $entry->refresh();
        $this->assertEquals('Updated Cotton Kurti', $entry->product_name);
        $this->assertEquals(500.00, (float) $entry->cost);
        $this->assertEquals(525.00, (float) $entry->total_cost);
        $this->assertEquals(1050.00, (float) $entry->selling_price);
    }

    public function test_admin_can_delete_wholesale_entry(): void
    {
        $entry = WholesalePurchase::create([
            'purchase_type' => WholesalePurchase::TYPE_SMART_LIVING,
            'sku' => 'DS-DEL-001',
            'product_name' => 'To Be Deleted',
            'status' => 'Available',
        ]);

        $res = $this->actingAsAdmin()->delete(route('admin.wholesale.destroy', $entry));
        $res->assertRedirect();

        $this->assertSoftDeleted('wholesale_purchases', ['id' => $entry->id]);
    }

    public function test_admin_can_export_csv_and_tsv(): void
    {
        WholesalePurchase::create([
            'purchase_type' => WholesalePurchase::TYPE_WOMEN_FASHION,
            'sku' => 'DS-EXP-001',
            'product_name' => 'Export Item',
            'category_name' => 'KURTI',
            'status' => 'Available',
            'cost' => 300.00,
        ]);

        // CSV export
        $csvRes = $this->actingAsAdmin()->get(route('admin.wholesale.export', ['tab' => 'women_fashion']));
        $csvRes->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvRes->headers->get('content-type'));

        // TSV copy
        $tsvRes = $this->actingAsAdmin()->get(route('admin.wholesale.copy', ['tab' => 'women_fashion']));
        $tsvRes->assertOk();
        $this->assertStringContainsString('DS-EXP-001', $tsvRes->getContent());
    }

    public function test_admin_can_manage_color_codes_master(): void
    {
        $res = $this->actingAsAdmin()->post(route('admin.wholesale.colors.store'), [
            'color_name' => 'Teal Blue',
            'color_code' => 'TBLU',
            'hex_code' => '#008080',
        ]);

        $res->assertRedirect();

        $color = WholesaleColorCode::where('color_name', 'Teal Blue')->first();
        $this->assertNotNull($color);
        $this->assertEquals('TBLU', $color->color_code);

        // Delete color
        $delRes = $this->actingAsAdmin()->delete(route('admin.wholesale.colors.destroy', $color));
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('wholesale_color_codes', ['color_name' => 'Teal Blue']);
    }
}
