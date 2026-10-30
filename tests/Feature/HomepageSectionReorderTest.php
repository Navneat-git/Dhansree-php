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

class HomepageSectionReorderTest extends TestCase
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
            'email' => 'section_reorder_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Section Reorder Admin',
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

    public function test_homepage_sections_reorder_dynamically_alters_storefront_render_sequence(): void
    {
        $carousel = HomeBlock::where('type', HomeBlockType::PROMO_CAROUSEL)->first();
        $rail = HomeBlock::where('type', HomeBlockType::PRODUCT_RAIL)->first();
        $hero = HomeBlock::where('type', HomeBlockType::HERO)->first();

        $this->assertNotNull($carousel);
        $this->assertNotNull($rail);
        $this->assertNotNull($hero);

        // 1. Normalize initial positions: Carousel (0), Rail (1), Hero (2), and others sequentially
        $carousel->update(['position' => 0, 'is_visible' => true]);
        $rail->update(['position' => 1, 'is_visible' => true]);
        $hero->update(['position' => 2, 'is_visible' => true]);

        $pos = 3;
        foreach (HomeBlock::whereNotIn('id', [$carousel->id, $rail->id, $hero->id])->orderBy('position')->orderBy('id')->get() as $otherBlock) {
            $otherBlock->update(['position' => $pos++]);
        }

        $response1 = $this->get('/');
        $response1->assertStatus(200);
        $html1 = substr($response1->getContent(), strpos($response1->getContent(), '<main'));

        $posRail1 = strpos($html1, '<!-- Featured Products Section -->');
        $posHero1 = strpos($html1, '<!-- Festive Signature Edit / Hero Showcase Section -->');

        $this->assertNotFalse($posRail1, 'Rail section must be rendered');
        $this->assertNotFalse($posHero1, 'Hero section must be rendered');
        $this->assertLessThan($posHero1, $posRail1, 'Product rail (New Arrivals) should appear before Hero when position is 1 vs 2');

        // 2. Admin moves rail DOWN (swaps with hero)
        $moveResponse = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post("/admin/homepage/blocks/{$rail->id}/move/down");
        $moveResponse->assertRedirect();
        $moveResponse->assertSessionHas('success');

        $this->assertEquals(2, $rail->fresh()->position);
        $this->assertEquals(1, $hero->fresh()->position);

        $response2 = $this->get('/');
        $response2->assertStatus(200);
        $html2 = substr($response2->getContent(), strpos($response2->getContent(), '<main'));

        $posRail2 = strpos($html2, '<!-- Featured Products Section -->');
        $posHero2 = strpos($html2, '<!-- Festive Signature Edit / Hero Showcase Section -->');

        $this->assertNotFalse($posRail2);
        $this->assertNotFalse($posHero2);
        $this->assertLessThan($posRail2, $posHero2, 'Hero section should now appear before Product Rail when swapped');

        // 3. Move rail back UP to position 1 as requested by user
        $moveBack = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post("/admin/homepage/blocks/{$rail->id}/move/up");
        $moveBack->assertRedirect();

        $this->assertEquals(1, $rail->fresh()->position);
        $this->assertEquals(2, $hero->fresh()->position);

        $response3 = $this->get('/');
        $html3 = substr($response3->getContent(), strpos($response3->getContent(), '<main'));
        $posRail3 = strpos($html3, '<!-- Featured Products Section -->');
        $posHero3 = strpos($html3, '<!-- Festive Signature Edit / Hero Showcase Section -->');
        $this->assertLessThan($posHero3, $posRail3, 'Product rail is now once again right after hero banner');
    }
}
