<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductOption;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMissingPagesAndDynamicFormsTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = AdminUser::firstOrCreate(
            ['email' => 'test.superadmin@dhansree.com'],
            [
                'name' => 'Test Super Admin',
                'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$c29tZXNhbHQ$c29tZWhhc2g',
                'status' => AdminUserStatus::ACTIVE,
                'password_change_required' => false,
            ]
        );

        $superRole = AdminRole::firstOrCreate(
            ['name' => 'Super Admin'],
            ['slug' => 'super-admin', 'grants_all_permissions' => true]
        );

        if (!$this->superAdmin->roles->contains($superRole->id)) {
            $this->superAdmin->roles()->attach($superRole->id);
        }
    }

    public function test_all_newly_implemented_admin_routes_return_ok(): void
    {
        $routes = [
            '/admin/homepage',
            '/admin/payments',
            '/admin/settings',
            '/admin/audit-logs',
            '/admin/users',
            '/admin/shipments',
            '/admin/inventory',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin, 'admin')->get($route);
            $response->assertOk();
        }
    }

    public function test_dynamic_product_specifications_save_and_delete_rows(): void
    {
        $product = Product::first();
        if (!$product) {
            $this->markTestSkipped('No product available');
        }

        // 1. Submit 3 custom specifications
        $payload = [
            'attributes' => [
                ['label' => 'Fabric', 'value' => '100% Chanderi Silk'],
                ['label' => 'Country of Origin', 'value' => 'India'],
                ['label' => 'Warranty', 'value' => '1 Year Replacement'],
            ],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/catalog/products/{$product->id}/attributes", $payload);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $product->id,
            'label' => 'Fabric',
            'value' => '100% Chanderi Silk',
        ]);
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $product->id,
            'label' => 'Warranty',
            'value' => '1 Year Replacement',
        ]);

        // 2. Submit with 1 row deleted (only 2 left)
        $payload2 = [
            'attributes' => [
                ['label' => 'Fabric', 'value' => '100% Chanderi Silk'],
                ['label' => 'Country of Origin', 'value' => 'India'],
            ],
        ];

        $response2 = $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/catalog/products/{$product->id}/attributes", $payload2);

        $response2->assertSessionHas('success');

        $this->assertDatabaseMissing('product_attributes', [
            'product_id' => $product->id,
            'label' => 'Warranty',
        ]);
    }

    public function test_dynamic_product_options_sync_multiple_dimensions(): void
    {
        $product = Product::first();
        if (!$product) {
            $this->markTestSkipped('No product available');
        }

        // Add 2 option types: Model and Color
        $payload = [
            'options' => [
                ['name' => 'Model', 'values' => 'iPhone 15, iPhone 16'],
                ['name' => 'Color', 'values' => 'Midnight, Starlight'],
            ],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/catalog/products/{$product->id}/options", $payload);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_options', [
            'product_id' => $product->id,
            'name' => 'Model',
        ]);
        $this->assertDatabaseHas('product_options', [
            'product_id' => $product->id,
            'name' => 'Color',
        ]);
    }
}
