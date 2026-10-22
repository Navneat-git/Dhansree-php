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
            'rail_size' => 'nullable|integer|min:2|max:20',
            'rail_category_id' => 'nullable|integer|exists:categories,id',
            'layout_width' => 'nullable|string|max:20',
            'text_mode' => 'nullable|string|max:20',
            'theme' => 'nullable|string|max:30',
            'image' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = (string) Str::uuid() . '.webp';
            $storageKey = "homepage/{$block->id}/{$filename}";
            Storage::disk('public')->put('media/' . $storageKey, file_get_contents($file->getRealPath()));

            $block->image_storage_key = $storageKey;
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
        $blocks = HomeBlock::orderBy('position')->get();

        $index = $blocks->search(fn($b) => $b->id === $block->id);
        if ($index === false) {
            return back();
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= $blocks->count()) {
            return back();
        }

        $swapBlock = $blocks[$targetIndex];

        DB::transaction(function () use ($block, $swapBlock) {
            $tmpPos = $block->position;
            $block->position = $swapBlock->position;
            $swapBlock->position = $tmpPos;

            $block->save();
            $swapBlock->save();
        });

        return back()->with('success', 'Homepage sections reordered successfully.');
    }
}
