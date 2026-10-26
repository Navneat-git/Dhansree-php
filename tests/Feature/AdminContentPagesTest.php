<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\ContentPage;
use App\Models\ContentPageSection;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminContentPagesTest extends TestCase
{
    use DatabaseTransactions;

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
            'email' => 'content_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Content Admin',
            'password_hash' => '$argon2id$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);

        $this->admin->roles()->attach($role->id);
        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawSessionToken = $sessionData['token'];
    }

    public function test_admin_can_view_policy_pages_index(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.pages.index'));

        $response->assertStatus(200);
        $response->assertSee('Policy &amp; Static Content Pages', false);
        $response->assertSee('Privacy Policy', false);
        $response->assertSee('Shipping Policy', false);
        $response->assertSee('Terms of use', false);
        $response->assertSee('Edit Page &amp; Sections', false);
    }

    public function test_admin_can_view_edit_policy_page_screen(): void
    {
        $page = ContentPage::where('key', 'PRIVACY_POLICY')->firstOrFail();

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.pages.edit', $page->id));

        $response->assertStatus(200);
        $response->assertSee($page->title);
        $response->assertSee('Policy Clauses &amp; Content Sections', false);
        $response->assertSee('Save Page Settings');
    }

    public function test_admin_can_update_page_details_and_seo(): void
    {
        $page = ContentPage::where('key', 'PRIVACY_POLICY')->firstOrFail();
        $originalVersion = (int) $page->version;

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.pages.update', $page->id), [
                'title' => 'Updated Privacy Policy',
                'intro' => 'This is updated introductory text for test.',
                'is_visible' => '1',
                'seo_title' => 'Privacy Policy 2026 | Dhansree',
                'seo_description' => 'Updated meta description for privacy page testing.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $page->refresh();
        $this->assertSame('Updated Privacy Policy', $page->title);
        $this->assertSame('This is updated introductory text for test.', $page->intro);
        $this->assertTrue($page->is_visible);
        $this->assertSame('Privacy Policy 2026 | Dhansree', $page->seo_title);
        $this->assertSame('Updated meta description for privacy page testing.', $page->seo_description);
        $this->assertGreaterThan($originalVersion, $page->version);

        // Storefront displays the updated content
        $frontResponse = $this->get('/privacy-policy');
        $frontResponse->assertStatus(200);
        $frontResponse->assertSee('Updated Privacy Policy');
        $frontResponse->assertSee('This is updated introductory text for test.');
    }

    public function test_admin_can_toggle_visibility(): void
    {
        $page = ContentPage::where('key', 'RETURN_POLICY')->firstOrFail();
        $initialVisibility = $page->is_visible;

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.pages.toggle', $page->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $page->refresh();
        $this->assertSame(!$initialVisibility, $page->is_visible);
    }

    public function test_admin_can_manage_sections_add_edit_move_and_delete(): void
    {
        $page = ContentPage::where('key', 'SHIPPING_POLICY')->firstOrFail();

        // 1. Add new section
        $addResponse = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.pages.sections.store', $page->id), [
                'heading' => 'International Shipping Guidelines',
                'body' => 'Currently Dhansree ships to PIN codes across India only.',
            ]);

        $addResponse->assertRedirect();
        $addResponse->assertSessionHas('success');

        $newSection = ContentPageSection::where('page_id', $page->id)
            ->where('heading', 'International Shipping Guidelines')
            ->firstOrFail();

        $this->assertSame('Currently Dhansree ships to PIN codes across India only.', $newSection->body);

        // 2. Edit section
        $editResponse = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->put(route('admin.pages.sections.update', $newSection->id), [
                'heading' => 'Updated International Shipping Clause',
                'body' => 'We are expanding to international destinations soon.',
            ]);

        $editResponse->assertRedirect();
        $editResponse->assertSessionHas('success');

        $newSection->refresh();
        $this->assertSame('Updated International Shipping Clause', $newSection->heading);
        $this->assertSame('We are expanding to international destinations soon.', $newSection->body);

        // 3. Move section up
        $moveResponse = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.pages.sections.move', $page->id), [
                'section_id' => $newSection->id,
                'direction' => 'up',
            ]);

        $moveResponse->assertRedirect();
        $moveResponse->assertSessionHas('success');

        // 4. Delete section
        $deleteResponse = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->delete(route('admin.pages.sections.destroy', $newSection->id));

        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        $this->assertNull(ContentPageSection::find($newSection->id));
    }
}
