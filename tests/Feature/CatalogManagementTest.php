<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\GstRegistrationType;
use App\Enums\ProductStatus;
use App\Models\AdminRole;
use App\Models\AdminSession;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SeoRedirect;
use App\Models\StoreSettings;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\ImageProcessor;
use App\Services\Catalog\ProductImageService;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariantService;
use App\Services\Catalog\SlugService;
use App\Support\Auth\AdminSessionManager;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    private AdminUser $admin;
    private string $rawSessionToken;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Ensure StoreSettings has REGULAR GST for tests
        $settings = StoreSettings::current();
        $settings->update([
            'gst_registration_type' => GstRegistrationType::REGULAR,
            'default_gst_rate_bps' => 1800,
        ]);

        // Create active super admin
        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'catalog_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Catalog Tester',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];
    }

    public function test_category_tree_cycle_detection_and_depth_limits(): void
    {
        $treeService = app(CategoryTreeService::class);

        // Map: 1 -> null (root), 2 -> 1 (child), 3 -> 2 (grandchild)
        $parentMap = [
            1 => null,
            2 => 1,
            3 => 2,
        ];

        // Depth calculations
        $this->assertSame(1, $treeService->depthOf(1, $parentMap));
        $this->assertSame(2, $treeService->depthOf(2, $parentMap));
        $this->assertSame(3, $treeService->depthOf(3, $parentMap));

        // Subtree heights
        $this->assertSame(2, $treeService->subtreeHeight(1, $parentMap));
        $this->assertSame(1, $treeService->subtreeHeight(2, $parentMap));
        $this->assertSame(0, $treeService->subtreeHeight(3, $parentMap));

        // Cycle detection
        $this->assertTrue($treeService->wouldCreateCycle(1, 1, $parentMap)); // cannot place inside itself
        $this->assertTrue($treeService->wouldCreateCycle(1, 2, $parentMap)); // cannot place inside child
        $this->assertTrue($treeService->wouldCreateCycle(1, 3, $parentMap)); // cannot place inside grandchild
        $this->assertFalse($treeService->wouldCreateCycle(3, 1, $parentMap)); // can move grandchild up to child of root
    }

    public function test_slug_generation_and_reserved_words(): void
    {
        $slug = SlugService::slugify('Women Kurta & Pant Set!');
        $this->assertSame('women-kurta-and-pant-set', $slug);

        // Unique candidate generator
        $candidate1 = SlugService::candidate('kurta-set', 1);
        $candidate2 = SlugService::candidate('kurta-set', 2);
        $this->assertSame('kurta-set', $candidate1);
        $this->assertSame('kurta-set-2', $candidate2);

        // Reserved word check
        $this->assertTrue(in_array('checkout', SlugService::RESERVED_CATEGORY_SLUGS, true));
        $this->assertTrue(in_array('admin', SlugService::RESERVED_CATEGORY_SLUGS, true));
    }

    public function test_category_crud_and_seo_redirects(): void
    {
        $categoryService = app(CategoryService::class);

        // Create Root Category
        $root = $categoryService->create([
            'name' => 'Ethnic Wear Test ' . uniqid(),
            'description' => 'Test root category',
        ], $this->admin->id);

        $this->assertNotNull($root->id);
        $this->assertNull($root->parent_id);
        $this->assertTrue($root->is_visible);

        // Create Subcategory
        $sub = $categoryService->create([
            'name' => 'Anarkali Suits ' . uniqid(),
            'parent_id' => $root->id,
        ], $this->admin->id);

        $this->assertSame($root->id, $sub->parent_id);

        // Updating slug creates a permanent 301 SEO redirect
        $oldSlug = $sub->slug;
        $newSlug = $oldSlug . '-premium';

        $updated = $categoryService->update($sub, [
            'name' => $sub->name,
            'slug' => $newSlug,
        ], $this->admin->id);

        $this->assertSame($newSlug, $updated->slug);

        $redirect = SeoRedirect::where('source_path', '/' . $oldSlug)->first();
        $this->assertNotNull($redirect);
        $this->assertSame('/' . $newSlug, $redirect->destination_path);
        $this->assertSame(301, $redirect->status_code);
        $this->assertTrue($redirect->is_active);

        // Clean up
        $sub->delete();
        $root->delete();
    }

    public function test_product_creation_and_inventory_initialization(): void
    {
        $productService = app(ProductService::class);
        $category = Category::whereNull('archived_at')->first();

        $product = $productService->create([
            'name' => 'Festive Silk Kurti ' . uniqid(),
            'category_id' => $category?->id,
            'price_rupees' => 899.00,
            'mrp_rupees' => 1499.00,
            'cost_rupees' => 450.00,
            'initial_stock' => 25,
            'hsn_code' => '6204',
            'gst_rate_bps' => 1200,
            'weight_grams' => 300,
        ], $this->admin->id);

        $this->assertNotNull($product->id);
        $this->assertSame(ProductStatus::DRAFT, $product->status);

        // Default variant created with paise pricing
        $variant = $product->variants()->first();
        $this->assertNotNull($variant);
        $this->assertSame(89900, $variant->price_paise);
        $this->assertSame(149900, $variant->mrp_paise);
        $this->assertSame(45000, $variant->cost_paise);
        $this->assertSame(300, $variant->weight_grams);

        // Inventory level initialized with on_hand stock
        $invLevel = InventoryLevel::where('variant_id', $variant->id)->first();
        $this->assertNotNull($invLevel);
        $this->assertSame(25, $invLevel->on_hand);
        $this->assertSame(0, $invLevel->reserved);

        // Clean up
        $invLevel->delete();
        $variant->inventoryItem()->delete();
        $variant->delete();
        $product->delete();
    }

    public function test_publish_checklist_validations(): void
    {
        $productService = app(ProductService::class);
        $category = Category::whereNull('archived_at')->first();

        // Create product without images
        $product = $productService->create([
            'name' => 'Checklist Test Product ' . uniqid(),
            'category_id' => $category?->id,
            'price_rupees' => 499.00,
            'hsn_code' => '6204',
        ], $this->admin->id);

        $product = Product::with(['category', 'variants', 'images'])->find($product->id);
        $issues = $productService->publishIssues($product);

        // Must fail with IMAGE_REQUIRED
        $codes = array_column($issues, 'code');
        $this->assertContains('IMAGE_REQUIRED', $codes);

        // Attempting publish throws InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $productService->publish($product, $this->admin->id);
    }

    public function test_multi_variant_matrix_generation(): void
    {
        $productService = app(ProductService::class);
        $variantService = app(ProductVariantService::class);
        $category = Category::whereNull('archived_at')->first();

        $product = $productService->create([
            'name' => 'Matrix Test Kurti ' . uniqid(),
            'category_id' => $category?->id,
            'price_rupees' => 799.00,
        ], $this->admin->id);

        // Configure options: Size (M, L) and Color (Maroon, Gold)
        $variantService->syncOptions($product, [
            ['name' => 'Size', 'values' => ['M', 'L']],
            ['name' => 'Color', 'values' => ['Maroon', 'Gold']],
        ], $this->admin->id);

        // Generate matrix (2 x 2 = 4 variants)
        $generated = $variantService->generateVariantMatrix(
            $product,
            799.00,
            1299.00,
            'MATKURT',
            $this->admin->id
        );

        $this->assertCount(4, $generated);

        $allVariants = ProductVariant::where('product_id', $product->id)->get();
        // 1 default variant + 4 matrix variants = 5 total
        $this->assertSame(5, $allVariants->count());

        // Clean up
        foreach ($allVariants as $v) {
            $v->inventoryLevels()->delete();
            $v->inventoryItem()->delete();
            DB::table('product_variant_option_values')->where('variant_id', $v->id)->delete();
            $v->delete();
        }
        $product->options()->each(function ($opt) {
            $opt->values()->delete();
            $opt->delete();
        });
        $product->delete();
    }

    public function test_image_processing_to_webp(): void
    {
        $processor = app(ImageProcessor::class);

        // Create a 300x300 valid test image
        $im = imagecreatetruecolor(300, 300);
        $red = imagecolorallocate($im, 200, 50, 50);
        imagefilledrectangle($im, 0, 0, 300, 300, $red);

        ob_start();
        imagejpeg($im);
        $jpegData = ob_get_clean();
        imagedestroy($im);

        $tempFile = UploadedFile::fake()->createWithContent('test_product.jpg', $jpegData);

        $result = $processor->process($tempFile);

        $this->assertSame('image/webp', $result['contentType']);
        $this->assertSame(300, $result['width']);
        $this->assertSame(300, $result['height']);
        $this->assertGreaterThan(0, $result['byteSize']);
        $this->assertSame('webp', $processor->sniffType($result['data']));
    }

    public function test_authenticated_admin_can_access_catalog_web_screens(): void
    {
        // Category Index
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get('/admin/catalog/categories');
        $response->assertStatus(200);
        $response->assertSee('Category Hierarchy');

        // Product Index
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get('/admin/catalog/products');
        $response->assertStatus(200);
        $response->assertSee('Manage catalog items');

        // Product Create Form
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get('/admin/catalog/products/create');
        $response->assertStatus(200);
        $response->assertSee('Add Product');
    }

    public function test_unauthenticated_visitor_cannot_access_catalog(): void
    {
        $response = $this->get('/admin/catalog/products');
        $response->assertRedirect('/admin/login');

        $response = $this->get('/admin/catalog/categories');
        $response->assertRedirect('/admin/login');
    }
}
