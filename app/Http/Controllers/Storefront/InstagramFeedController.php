<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\InstagramPost;
use App\Models\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstagramFeedController extends Controller
{
    /**
     * Display the Instagram-styled feed and lookbook page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $posts = collect();
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('instagram_posts')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                if (\Illuminate\Support\Facades\Schema::hasTable('instagram_posts')) {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', [
                        '--class' => 'Database\\Seeders\\InstagramFeedSeeder',
                        '--force' => true,
                    ]);
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('instagram_posts')) {
                $posts = InstagramPost::query()
                    ->visible()
                    ->with(['taggedProduct.defaultVariant', 'taggedProduct.images'])
                    ->orderBy('position')
                    ->orderByDesc('id')
                    ->get();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Instagram feed lookbook error: ' . $e->getMessage());
            $posts = collect();
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'data' => $posts,
            ]);
        }

        $settings = StoreSettings::current();

        $reels = $posts->where('type', 'reel')->values();
        $regularPosts = $posts->where('type', 'post')->values();
        $shopTheLook = $posts->filter(fn ($p) => $p->tagged_product_id !== null && $p->taggedProduct !== null)->values();


        return view('store.instagram', [
            'posts' => $posts,
            'reels' => $reels,
            'regularPosts' => $regularPosts,
            'shopTheLook' => $shopTheLook,
            'settings' => $settings,
            'instagramHandle' => 'dhansreetrends',
            'instagramProfileUrl' => $settings->instagram_url ?: 'https://www.instagram.com/dhansreetrends',
            'seoTitle' => 'Dhansree Trends on Instagram (@dhansreetrends) — Reels, Styles & Shop The Look',
            'seoDescription' => 'Explore the official Dhansree Trends Instagram lookbook. Watch trending Jaipur kurta reels, festive co-ord styling, and shop authentic ethnic fashion straight from our feed.',
        ]);
    }

    /**
     * Interactive like toggle for posts in the Instagram viewer.
     */
    public function like(int $id): JsonResponse
    {
        $post = InstagramPost::findOrFail($id);
        $post->increment('likes_count');

        return response()->json([
            'success' => true,
            'id' => $post->id,
            'likes_count' => $post->likes_count,
            'formatted_likes' => number_format($post->likes_count),
        ]);
    }
}
