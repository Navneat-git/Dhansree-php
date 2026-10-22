<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Enums\SeoRedirectOrigin;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\FaqEntry;
use App\Models\FaqGroup;
use App\Models\HomepageBanner;
use App\Models\NewsletterSubscriber;
use App\Models\SeoRedirect;
use App\Support\Auth\AdminSessionManager;
use App\Support\Rbac\Permissions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCmsManagementTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $adminToken;
    private AdminUser $readOnlyAdmin;
    private string $readOnlyAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        Storage::fake('public');

        // 1. Super Admin with all permissions
        $superRole = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'cms_admin_' . uniqid() . '@dhansree.test',
            'name' => 'CMS Administrator',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($superRole->id);

        $superSession = AdminSessionManager::createSession($this->admin);
        $this->adminToken = $superSession['token'];

        // 2. Restricted Admin without CMS permissions
        $limitedRole = AdminRole::create([
            'code' => 'ORDER_STAFF_' . uniqid(),
            'name' => 'Order Staff',
            'grants_all_permissions' => false,
        ]);
        $orderPerm = AdminPermission::firstOrCreate(
            ['code' => Permissions::ORDER_VIEW],
            ['description' => 'View orders']
        );
        $limitedRole->permissions()->attach($orderPerm->id);

        $this->readOnlyAdmin = AdminUser::create([
            'email' => 'staff_' . uniqid() . '@dhansree.test',
            'name' => 'Restricted Staff',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->readOnlyAdmin->roles()->attach($limitedRole->id);

        $roSession = AdminSessionManager::createSession($this->readOnlyAdmin);
        $this->readOnlyAdminToken = $roSession['token'];
    }

    private function asAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->adminToken);
    }

    private function asReadOnlyAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->readOnlyAdminToken);
    }

    // ─── 1. Banners Tests ────────────────────────────────────────────────────

    public function test_admin_can_view_banners_list(): void
    {
        HomepageBanner::create([
            'title' => 'Chanderi Festive Banner',
            'link_path' => '/kurta-set',
            'cta_label' => 'Shop Now',
            'word_mode' => 'ON_PICTURE',
            'is_active' => true,
            'position' => 1,
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        $res = $this->asAdmin()->get('/admin/banners');

        $res->assertStatus(200);
        $res->assertViewIs('admin.banners.index');
        $res->assertViewHas('banners');
        $res->assertViewHas('stats');
    }

    public function test_admin_can_create_banner_with_images(): void
    {
        $desktopImage = UploadedFile::fake()->image('desktop_hero.webp', 1920, 600);
        $mobileImage = UploadedFile::fake()->image('mobile_hero.webp', 750, 750);

        $payload = [
            'title' => 'Diwali Grand Ethnic Sale',
            'eyebrow' => 'LIMITED TIME OFFER',
            'subtitle' => 'Flat 25% Off on All Pure Silk Sets',
            'link_path' => '/women-two-piece-sets-co-ord',
            'cta_label' => 'Explore Deals',
            'word_mode' => 'LIGHT_ON_PICTURE',
            'position' => 0,
            'is_active' => '1',
            'desktop_image' => $desktopImage,
            'mobile_image' => $mobileImage,
        ];

        $res = $this->asAdmin()->post('/admin/banners', $payload);

        $res->assertRedirect('/admin/banners');
        $res->assertSessionHas('success');

        $banner = HomepageBanner::where('title', 'Diwali Grand Ethnic Sale')->first();
        $this->assertNotNull($banner);
        $this->assertNotNull($banner->image_storage_key);
        $this->assertNotNull($banner->mobile_storage_key);
        $this->assertEquals(1920, $banner->image_width);
        $this->assertEquals(600, $banner->image_height);
        $this->assertEquals('LIGHT_ON_PICTURE', $banner->word_mode);
        $this->assertTrue($banner->is_active);

        // Verify file stored in fake disk
        Storage::disk('public')->assertExists('media/' . $banner->image_storage_key);
        Storage::disk('public')->assertExists('media/' . $banner->mobile_storage_key);
    }

    public function test_banner_validation_rules(): void
    {
        // 1. CTA label without link_path fails
        $res = $this->asAdmin()->post('/admin/banners', [
            'title' => 'Invalid Banner',
            'cta_label' => 'Click Here',
            'link_path' => '', // missing link path
        ]);
        $res->assertStatus(422);

        // 2. Link path without leading slash fails
        $res = $this->asAdmin()->post('/admin/banners', [
            'title' => 'Invalid Path Banner',
            'link_path' => 'https://external-domain.com/shop',
        ]);
        $res->assertSessionHasErrors('link_path');
    }

    public function test_admin_can_toggle_and_delete_banner(): void
    {
        $banner = HomepageBanner::create([
            'title' => 'Toggle Delete Test',
            'link_path' => '/kurta-set',
            'is_active' => true,
            'position' => 2,
            'word_mode' => 'ON_PICTURE',
            'created_by_admin_user_id' => $this->admin->id,
            'version' => 0,
        ]);

        // Toggle active off
        $res = $this->asAdmin()->post("/admin/banners/{$banner->id}/toggle");
        $res->assertRedirect();
        $banner->refresh();
        $this->assertFalse($banner->is_active);

        // Delete banner
        $res = $this->asAdmin()->delete("/admin/banners/{$banner->id}");
        $res->assertRedirect('/admin/banners');
        $this->assertDatabaseMissing('homepage_banners', ['id' => $banner->id]);
    }

    // ─── 2. FAQs Tests ───────────────────────────────────────────────────────

    public function test_admin_can_manage_faq_groups_and_entries(): void
    {
        // 1. View FAQ management page
        $res = $this->asAdmin()->get('/admin/faqs');
        $res->assertStatus(200);
        $res->assertViewIs('admin.faqs.index');

        // 2. Create FAQ group
        $groupRes = $this->asAdmin()->post('/admin/faqs/groups', [
            'name' => 'Shipping & Delivery Timelines',
            'slug' => 'shipping-delivery-' . uniqid(),
            'position' => 1,
            'is_visible' => '1',
            'show_on_faq_page' => '1',
        ]);
        $groupRes->assertRedirect();

        $group = FaqGroup::where('name', 'Shipping & Delivery Timelines')->first();
        $this->assertNotNull($group);
        $this->assertTrue($group->is_visible);

        // 3. Create FAQ entry under group
        $entryRes = $this->asAdmin()->post("/admin/faqs/groups/{$group->id}/entries", [
            'question' => 'How many days does courier delivery take across India?',
            'answer' => 'Metros take 2-4 business days via Shiprocket Bluedart/Delhivery. Rest of India takes 4-7 business days.',
            'position' => 1,
            'is_visible' => '1',
        ]);
        $entryRes->assertRedirect();

        $entry = FaqEntry::where('group_id', $group->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals('How many days does courier delivery take across India?', $entry->question);

        // 4. Update entry
        $updateRes = $this->asAdmin()->put("/admin/faqs/entries/{$entry->id}", [
            'question' => 'Updated question: Delivery timeline across India?',
            'answer' => 'Fast delivery within 2 to 5 days.',
            'position' => 1,
            'is_visible' => '1',
        ]);
        $updateRes->assertRedirect();
        $entry->refresh();
        $this->assertEquals('Fast delivery within 2 to 5 days.', $entry->answer);

        // 5. Storefront /faq page renders the question
        $storeFaqRes = $this->get('/faq');
        $storeFaqRes->assertStatus(200);
        $storeFaqRes->assertSee('Fast delivery within 2 to 5 days.');

        // 6. Delete entry
        $delEntryRes = $this->asAdmin()->delete("/admin/faqs/entries/{$entry->id}");
        $delEntryRes->assertRedirect();
        $this->assertDatabaseMissing('faq_entries', ['id' => $entry->id]);

        // 7. Delete group
        $delGroupRes = $this->asAdmin()->delete("/admin/faqs/groups/{$group->id}");
        $delGroupRes->assertRedirect();
        $this->assertDatabaseMissing('faq_groups', ['id' => $group->id]);
    }

    // ─── 3. SEO 301 Redirects Tests ──────────────────────────────────────────

    public function test_admin_can_create_and_apply_seo_redirect(): void
    {
        $oldPath = '/legacy-kurties-' . uniqid();
        $targetPath = '/women-two-piece-sets-co-ord';

        // 1. Admin creates manual 301 redirect
        $res = $this->asAdmin()->post('/admin/seo', [
            'source_path' => $oldPath,
            'destination_path' => $targetPath,
            'status_code' => '301',
        ]);
        $res->assertRedirect('/admin/seo');

        $redirect = SeoRedirect::where('source_path', $oldPath)->first();
        $this->assertNotNull($redirect);
        $this->assertEquals($targetPath, $redirect->destination_path);
        $this->assertEquals(301, $redirect->status_code);
        $this->assertEquals(SeoRedirectOrigin::MANUAL, $redirect->origin);
        $this->assertTrue($redirect->is_active);

        // 2. Accessing old path on storefront returns 301 Permanent Redirect
        $storeRes = $this->get($oldPath);
        $storeRes->assertStatus(301);
        $storeRes->assertRedirect($targetPath);

        // 3. Toggle redirect off
        $this->asAdmin()->post("/admin/seo/{$redirect->id}/toggle");
        $redirect->refresh();
        $this->assertFalse($redirect->is_active);

        // 4. Delete redirect
        $delRes = $this->asAdmin()->delete("/admin/seo/{$redirect->id}");
        $delRes->assertRedirect('/admin/seo');
        $this->assertDatabaseMissing('seo_redirects', ['id' => $redirect->id]);
    }

    public function test_seo_redirect_rejects_invalid_rules(): void
    {
        // 1. Identical source and destination
        $res = $this->asAdmin()->post('/admin/seo', [
            'source_path' => '/same-path',
            'destination_path' => '/same-path',
            'status_code' => '301',
        ]);
        $res->assertStatus(422);

        // 2. Missing leading slash in source
        $res = $this->asAdmin()->post('/admin/seo', [
            'source_path' => 'no-slash-path',
            'destination_path' => '/valid-target',
            'status_code' => '301',
        ]);
        $res->assertSessionHasErrors('source_path');
    }

    // ─── 4. Newsletter Subscribers Tests ─────────────────────────────────────

    public function test_admin_can_view_and_export_subscribers(): void
    {
        $testEmail = 'lead_' . uniqid() . '@dhansree-customer.in';

        NewsletterSubscriber::create([
            'email' => $testEmail,
            'status' => 'CONFIRMED',
            'source' => 'POPUP',
            'consent_text' => 'Agreed to receive promo emails',
            'consent_at' => now(),
            'unsubscribe_token' => \Illuminate\Support\Str::random(32),
            'confirmed_at' => now(),
        ]);

        // 1. View subscribers list
        $res = $this->asAdmin()->get('/admin/subscribers');
        $res->assertStatus(200);
        $res->assertViewIs('admin.subscribers.index');
        $res->assertSee($testEmail);

        // 2. Export CSV stream
        $exportRes = $this->asAdmin()->get('/admin/subscribers/export');
        $exportRes->assertStatus(200);
        $exportRes->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('ID,Email,Status,Source', $exportRes->streamedContent());
        $this->assertStringContainsString($testEmail, $exportRes->streamedContent());
    }

    // ─── 5. RBAC Permission Guards ───────────────────────────────────────────

    public function test_unauthorized_admin_cannot_access_cms_modules(): void
    {
        // Banners -> 403
        $this->asReadOnlyAdmin()->get('/admin/banners')->assertStatus(403);
        $this->asReadOnlyAdmin()->post('/admin/banners', ['title' => 'Hacked'])->assertStatus(403);

        // SEO -> 403
        $this->asReadOnlyAdmin()->get('/admin/seo')->assertStatus(403);
        $this->asReadOnlyAdmin()->post('/admin/seo', ['source_path' => '/a', 'destination_path' => '/b'])->assertStatus(403);

        // Subscribers -> 403
        $this->asReadOnlyAdmin()->get('/admin/subscribers')->assertStatus(403);
        $this->asReadOnlyAdmin()->get('/admin/subscribers/export')->assertStatus(403);
    }
}
