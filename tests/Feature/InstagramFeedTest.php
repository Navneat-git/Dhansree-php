<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminSession;
use App\Models\AdminUser;
use App\Models\InstagramPost;
use App\Models\Product;
use App\Support\Auth\AdminSessionManager;
use App\Support\Rbac\Permissions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstagramFeedTest extends TestCase
{
    use DatabaseTransactions;

    protected string $rawSessionToken;
    protected AdminUser $admin;


    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'admin_insta_' . uniqid() . '@dhansree.test',
            'name' => 'Insta Test Admin',
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


    public function test_storefront_homepage_renders_instagram_slider_section_with_reels(): void
    {
        // Ensure at least one Instagram post exists
        InstagramPost::firstOrCreate(
            ['id' => 101],
            [
                'type' => 'reel',
                'caption' => 'Test Jaipur Festive Kurta Reel #dhansreetrends',
                'media_url' => 'https://assets.dhansree.com/test-media.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 1250,
                'views_count' => '24.5K',
                'is_visible' => true,
                'position' => 1,
            ]
        );

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Shop Our Instagram Reels &amp; Styles', false);
        $response->assertSee('@dhansreetrends');
        $response->assertSee('/instragram');
        $response->assertSee('24.5K');
    }

    public function test_dedicated_instagram_page_renders_authentically_on_both_routes(): void
    {
        // 1. Test /instagram route
        $res1 = $this->get('/instagram');
        $res1->assertStatus(200);
        $res1->assertSee('dhansreetrends');
        $res1->assertSee('Shop The Look');
        $res1->assertSee('Follow');

        // 2. Test /instragram route (handling the user-specified URL spelling seamlessly)
        $res2 = $this->get('/instragram');
        $res2->assertStatus(200);
        $res2->assertSee('dhansreetrends');
        $res2->assertSee('Reels');
    }

    public function test_instagram_reel_with_tagged_product_links_to_product(): void
    {
        $product = Product::first();
        $this->assertNotNull($product, 'Product must exist for testing tagged reels.');

        $post = InstagramPost::create([
            'type' => 'reel',
            'caption' => 'Tagged test reel',
            'media_url' => 'https://assets.dhansree.com/test-tagged.webp',
            'instagram_url' => 'https://www.instagram.com/dhansreetrends',
            'likes_count' => 990,
            'views_count' => '15.2K',
            'tagged_product_id' => $product->id,
            'is_visible' => true,
            'position' => 1,
        ]);

        $response = $this->get('/instagram');
        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee($product->slug);
    }

    public function test_user_can_interactively_like_an_instagram_post(): void
    {
        $post = InstagramPost::create([
            'type' => 'reel',
            'caption' => 'Likeable post',
            'media_url' => 'https://assets.dhansree.com/test-like.webp',
            'likes_count' => 500,
            'is_visible' => true,
        ]);

        $response = $this->postJson("/instagram/posts/{$post->id}/like");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'id' => $post->id,
            'likes_count' => 501,
        ]);

        $this->assertSame(501, $post->fresh()->likes_count);
    }

    public function test_admin_can_view_instagram_feed_management(): void
    {
        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->get(route('admin.instagram.index'));

        $response->assertStatus(200);
        $response->assertSee('Instagram Feed &amp; Lookbook', false);
        $response->assertSee('Add Reel / Post');
    }

    public function test_admin_can_create_instagram_reel(): void
    {
        $product = Product::first();

        $postData = [
            'type' => 'reel',
            'caption' => 'New Admin Created Festive Reel #dhansree',
            'media_url' => 'products/2/6e2d12d1.webp',
            'instagram_url' => 'https://www.instagram.com/reel/C123456789/',
            'likes_count' => 1500,
            'views_count' => '28.1K',
            'comments_count' => 45,
            'tagged_product_id' => $product?->id,
            'position' => 10,
            'is_visible' => '1',
        ];

        $response = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.instagram.store'), $postData);

        $response->assertRedirect(route('admin.instagram.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('instagram_posts', [
            'caption' => 'New Admin Created Festive Reel #dhansree',
            'type' => 'reel',
            'likes_count' => 1500,
            'views_count' => '28.1K',
        ]);
    }

    public function test_admin_can_toggle_visibility_and_delete_instagram_post(): void
    {
        $post = InstagramPost::create([
            'type' => 'post',
            'caption' => 'Toggle and delete test',
            'media_url' => 'products/3/sample.webp',
            'is_visible' => true,
            'position' => 5,
        ]);

        // 1. Toggle visibility
        $toggleRes = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->post(route('admin.instagram.toggle', $post->id));

        $toggleRes->assertRedirect();
        $this->assertFalse($post->fresh()->is_visible);

        // 2. Delete post
        $delRes = $this->withUnencryptedCookie('dhansree_admin_session', $this->rawSessionToken)
            ->delete(route('admin.instagram.destroy', $post->id));

        $delRes->assertRedirect(route('admin.instagram.index'));
        $this->assertDatabaseMissing('instagram_posts', ['id' => $post->id]);
    }
}
