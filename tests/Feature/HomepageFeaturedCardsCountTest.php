<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\HomeBlockType;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\HomeBlock;
use App\Support\Auth\AdminSessionManager;
use Tests\TestCase;

class HomepageFeaturedCardsCountTest extends TestCase
{
    private AdminUser $admin;
    private string $rawSessionToken;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'homepage_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Homepage Admin',
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

    private function adminGet(string $uri)
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get($uri);
    }

    private function adminPut(string $uri, array $data = [])
    {
        return $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put($uri, $data);
    }

    public function test_admin_can_view_and_manage_featured_styles_card_count(): void
    {
        $block = HomeBlock::where('type', HomeBlockType::PRODUCT_RAIL)->first();
        if (!$block) {
            $block = HomeBlock::create([
                'type' => HomeBlockType::PRODUCT_RAIL,
                'heading' => 'Featured Ethnic Styles',
                'eyebrow' => 'Festive Radiance',
                'rail_size' => 16,
                'is_visible' => true,
                'position' => 3,
            ]);
        }

        $response = $this->adminGet('/admin/homepage');
        $response->assertStatus(200);
        $response->assertSee('PRODUCT RAIL (FEATURED ETHNIC STYLES)');
        $response->assertSee('Cards Displayed');
        $response->assertSee('Product Cards to Display (Count)');
        $response->assertSee('name="rail_size"', false);

        // Update rail_size to 20
        $updateResponse = $this->adminPut("/admin/homepage/blocks/{$block->id}", [
            'heading' => 'Featured Ethnic Styles',
            'eyebrow' => 'Festive Radiance',
            'rail_size' => 20,
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $this->assertEquals(20, $block->fresh()->rail_size);
    }

    public function test_storefront_home_page_renders_featured_ethnic_styles_section(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Featured Ethnic Styles');
        $response->assertSee('Festive Radiance');
    }
}
