<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\HomeBlockType;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\HomeBlock;
use App\Models\StoreSettings;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminHomepageWebController extends Controller
{
    /**
     * Homepage sections & blocks dashboard.
     */
    public function index(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_VIEW)) {
            abort(403, 'You do not have permission to view homepage sections.');
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('home_blocks')) {
                try {
                    DB::statement("ALTER TABLE home_blocks MODIFY COLUMN type ENUM('PROMO_CAROUSEL','HERO','TILE_GRID','PRODUCT_RAIL','PROMO_BANNER','FEATURES','INSTAGRAM') NOT NULL");
                } catch (\Throwable $e) {}

                if (!HomeBlock::where('type', HomeBlockType::INSTAGRAM)->exists()) {
                    $maxPos = (int) (HomeBlock::max('position') ?? 6);
                    HomeBlock::create([
                        'type' => HomeBlockType::INSTAGRAM,
                        'position' => $maxPos + 1,
                        'is_visible' => true,
                        'heading' => 'Shop Our Instagram Reels & Styles',
                        'eyebrow' => 'As Seen On Instagram • @dhansreetrends',
                        'body' => 'Tap any reel to explore trending Jaipur fits, festive styling & shop the exact looks.',
                        'primary_label' => 'View All Reels',
                        'primary_path' => '/instragram',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        $blocks = HomeBlock::with(['railCategory', 'homeBlockItems'])
            ->orderBy('position')
            ->get();

        $categories = Category::whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $settings = StoreSettings::firstOrNew(['id' => 1]);

        return view('admin.homepage.index', [
            'blocks' => $blocks,
            'categories' => $categories,
            'settings' => $settings,
            'canEdit' => $admin->hasPermission(Permissions::BANNER_UPDATE),
        ]);
    }

    /**
     * Toggle block visibility.
     */
    public function toggle(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to update homepage sections.');
        }

        $block = HomeBlock::findOrFail($id);
        $block->is_visible = !$block->is_visible;
        $block->version = ($block->version ?? 0) + 1;
        $block->save();

        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        $statusText = $block->is_visible ? 'visible on storefront' : 'hidden from storefront';
        return back()->with('success', "Section '{$block->type->value}' is now {$statusText}.");
    }

    /**
     * Update block details.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to update homepage sections.');
        }

        $block = HomeBlock::findOrFail($id);

        $validated = $request->validate([
            'heading' => 'nullable|string|max:200',
            'eyebrow' => 'nullable|string|max:120',
            'body' => 'nullable|string|max:1000',
            'primary_label' => 'nullable|string|max:60',
            'primary_path' => 'nullable|string|max:200',
            'secondary_label' => 'nullable|string|max:60',
            'secondary_path' => 'nullable|string|max:200',
            'rail_source' => 'nullable|string|max:40',
            'rail_size' => 'nullable|integer|min:1|max:100',
            'rail_category_id' => 'nullable|integer|exists:categories,id',
            'layout_width' => 'nullable|string|max:20',
            'text_mode' => 'nullable|string|max:20',
            'theme' => 'nullable|string|max:30',
            'image' => 'nullable|image|max:10240',
            'image_url_direct' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = $file->getClientOriginalExtension() ?: 'webp';
            $filename = (string) Str::uuid() . '.' . $ext;
            $storageKey = "homepage/{$block->id}/{$filename}";
            $content = file_get_contents($file->getRealPath());
            Storage::disk('public')->put('media/' . $storageKey, $content);

            // Also copy to ds-assets directory if present so CDN can serve it immediately
            $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                @file_put_contents($targetFile, $content);
            }

            $block->image_storage_key = $storageKey;
            $block->image_alt_text = $validated['heading'] ?? 'Homepage banner';
        } elseif ($request->filled('image_url_direct')) {
            $directUrl = trim((string) $request->input('image_url_direct'));
            $block->image_storage_key = $directUrl;
            $block->image_alt_text = $validated['heading'] ?? 'Homepage banner';
        }

        $block->heading = $validated['heading'] ?? $block->heading;
        $block->eyebrow = $validated['eyebrow'] ?? null;
        $block->body = $validated['body'] ?? null;
        $block->primary_label = $validated['primary_label'] ?? null;
        $block->primary_path = $validated['primary_path'] ?? null;
        $block->secondary_label = $validated['secondary_label'] ?? null;
        $block->secondary_path = $validated['secondary_path'] ?? null;
        $block->rail_source = $validated['rail_source'] ?? $block->rail_source;
        $block->rail_size = isset($validated['rail_size']) ? (int) $validated['rail_size'] : $block->rail_size;
        $block->rail_category_id = isset($validated['rail_category_id']) ? (int) $validated['rail_category_id'] : null;
        $block->layout_width = $validated['layout_width'] ?? $block->layout_width;
        $block->text_mode = $validated['text_mode'] ?? $block->text_mode;
        $block->theme = $validated['theme'] ?? $block->theme;
        $block->version = ($block->version ?? 0) + 1;
        $block->save();

        // If this is the HERO Festive Signature block, safely sync with StoreSettings if columns exist
        if ($block->type === HomeBlockType::HERO) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('store_settings', 'home_hero_image_url')) {
                    $settings = StoreSettings::current();
                    if ($block->image_url) {
                        $settings->home_hero_image_url = $block->image_url;
                    }
                    if (!empty($block->heading)) {
                        $settings->home_hero_title = $block->heading;
                    }
                    if (!empty($block->eyebrow)) {
                        $settings->home_hero_subtitle = $block->eyebrow;
                    }
                    $settings->save();
                }
            } catch (\Throwable $e) {
                // Ignore sync error so homepage block update never fails
            }
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        return back()->with('success', "Section #{$block->id} ({$block->type->value}) updated successfully.");
    }

    /**
     * Move block up or down.
     */
    public function move(int $id, string $direction): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to update homepage sections.');
        }

        $block = HomeBlock::findOrFail($id);
        $blocksList = HomeBlock::orderBy('position')->orderBy('id')->get()->all();

        $index = null;
        foreach ($blocksList as $i => $b) {
            if ($b->id === $block->id) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return back();
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= count($blocksList)) {
            return back();
        }

        // Swap the two items in the array
        $temp = $blocksList[$index];
        $blocksList[$index] = $blocksList[$targetIndex];
        $blocksList[$targetIndex] = $temp;

        DB::transaction(function () use ($blocksList) {
            foreach ($blocksList as $pos => $b) {
                if ($b->position !== $pos) {
                    $b->position = $pos;
                    $b->save();
                }
            }
        });

        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        return back()->with('success', 'Homepage sections reordered successfully.');
    }
}
