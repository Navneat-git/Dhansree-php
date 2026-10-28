<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\InstagramPost;
use App\Models\Product;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminInstagramWebController extends Controller
{
    /**
     * List all Instagram reels and posts in the admin panel.
     */
    public function index(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_VIEW)) {
            abort(403, 'You do not have permission to view Instagram feed management.');
        }

        $posts = InstagramPost::with('taggedProduct')
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        $products = Product::where('status', ProductStatus::ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.instagram.index', [
            'posts' => $posts,
            'products' => $products,
            'canEdit' => $admin->hasPermission(Permissions::BANNER_UPDATE),
            'canCreate' => $admin->hasPermission(Permissions::BANNER_CREATE),
            'canDelete' => $admin->hasPermission(Permissions::BANNER_DELETE),
        ]);
    }

    /**
     * Create a new Instagram reel or post.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_CREATE)) {
            abort(403, 'You do not have permission to create Instagram posts.');
        }

        $validated = $request->validate([
            'type' => 'required|in:reel,post',
            'caption' => 'nullable|string|max:3000',
            'media' => 'nullable|image|max:10240',
            'media_url' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'instagram_url' => 'nullable|string|max:500',
            'likes_count' => 'nullable|integer|min:0',
            'views_count' => 'nullable|string|max:30',
            'comments_count' => 'nullable|integer|min:0',
            'tagged_product_id' => 'nullable|integer|exists:products,id',
            'position' => 'nullable|integer',
            'is_visible' => 'nullable|boolean',
        ]);

        $mediaUrl = $validated['media_url'] ?? '';

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $ext = $file->getClientOriginalExtension() ?: 'webp';
            $filename = (string) Str::uuid() . '.' . $ext;
            $storageKey = "instagram/{$filename}";
            $content = file_get_contents($file->getRealPath());
            Storage::disk('public')->put('media/' . $storageKey, $content);

            // Also copy to ds-assets directory if configured
            $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                @file_put_contents($targetFile, $content);
            }

            $mediaUrl = $storageKey;
        }

        if (empty($mediaUrl)) {
            return back()->withInput()->with('error', 'Please upload a cover image or provide a valid media URL.');
        }

        $maxPos = (int) InstagramPost::max('position');

        InstagramPost::create([
            'type' => $validated['type'],
            'caption' => $validated['caption'] ?? null,
            'media_url' => $mediaUrl,
            'video_url' => $validated['video_url'] ?? null,
            'instagram_url' => $validated['instagram_url'] ?? 'https://www.instagram.com/dhansreetrends',
            'likes_count' => isset($validated['likes_count']) ? (int) $validated['likes_count'] : 1250,
            'views_count' => $validated['views_count'] ?? '18.5K',
            'comments_count' => isset($validated['comments_count']) ? (int) $validated['comments_count'] : 34,
            'tagged_product_id' => !empty($validated['tagged_product_id']) ? (int) $validated['tagged_product_id'] : null,
            'position' => isset($validated['position']) ? (int) $validated['position'] : ($maxPos + 1),
            'is_visible' => $request->has('is_visible'),
        ]);

        return redirect()->route('admin.instagram.index')->with('success', 'Instagram reel/post added successfully.');
    }

    /**
     * Update an existing Instagram reel or post.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to update Instagram posts.');
        }

        $post = InstagramPost::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:reel,post',
            'caption' => 'nullable|string|max:3000',
            'media' => 'nullable|image|max:10240',
            'media_url' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'instagram_url' => 'nullable|string|max:500',
            'likes_count' => 'nullable|integer|min:0',
            'views_count' => 'nullable|string|max:30',
            'comments_count' => 'nullable|integer|min:0',
            'tagged_product_id' => 'nullable|integer|exists:products,id',
            'position' => 'nullable|integer',
            'is_visible' => 'nullable|boolean',
        ]);

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $ext = $file->getClientOriginalExtension() ?: 'webp';
            $filename = (string) Str::uuid() . '.' . $ext;
            $storageKey = "instagram/{$filename}";
            $content = file_get_contents($file->getRealPath());
            Storage::disk('public')->put('media/' . $storageKey, $content);

            $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                @file_put_contents($targetFile, $content);
            }

            $post->media_url = $storageKey;
        } elseif (!empty($validated['media_url'])) {
            $post->media_url = trim($validated['media_url']);
        }

        $post->type = $validated['type'];
        $post->caption = $validated['caption'] ?? null;
        $post->video_url = $validated['video_url'] ?? null;
        $post->instagram_url = $validated['instagram_url'] ?? $post->instagram_url;
        $post->likes_count = isset($validated['likes_count']) ? (int) $validated['likes_count'] : $post->likes_count;
        $post->views_count = $validated['views_count'] ?? $post->views_count;
        $post->comments_count = isset($validated['comments_count']) ? (int) $validated['comments_count'] : $post->comments_count;
        $post->tagged_product_id = !empty($validated['tagged_product_id']) ? (int) $validated['tagged_product_id'] : null;
        $post->position = isset($validated['position']) ? (int) $validated['position'] : $post->position;
        $post->is_visible = $request->has('is_visible');
        $post->save();

        return redirect()->route('admin.instagram.index')->with('success', "Instagram post #{$post->id} updated successfully.");
    }

    /**
     * Toggle visibility of an Instagram item.
     */
    public function toggle(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to update Instagram posts.');
        }

        $post = InstagramPost::findOrFail($id);
        $post->is_visible = !$post->is_visible;
        $post->save();

        $status = $post->is_visible ? 'visible' : 'hidden';
        return back()->with('success', "Instagram post #{$post->id} is now {$status}.");
    }

    /**
     * Delete an Instagram post.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_DELETE)) {
            abort(403, 'You do not have permission to delete Instagram posts.');
        }

        $post = InstagramPost::findOrFail($id);
        $post->delete();

        return redirect()->route('admin.instagram.index')->with('success', 'Instagram post deleted successfully.');
    }
}
