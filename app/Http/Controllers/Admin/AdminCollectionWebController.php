<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCollectionWebController extends Controller
{
    /**
     * List all curated collections.
     */
    public function index(): View
    {
        $collections = Collection::with('categories')
            ->withCount('categories')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $categories = Category::whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('admin.collections.index', compact('collections', 'categories'));
    }

    /**
     * Store a newly created collection.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'slug' => 'nullable|string|max:80|unique:collections,slug',
            'heading' => 'nullable|string|max:90',
            'intro' => 'nullable|string|max:400',
            'is_visible' => 'nullable|boolean',
            'card_shape' => 'nullable|in:SQUARE,PORTRAIT',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $nextPosition = (Collection::max('position') ?? 0) + 1;

        $collection = Collection::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'heading' => $validated['heading'] ?? $validated['name'],
            'intro' => $validated['intro'] ?? null,
            'is_visible' => $request->boolean('is_visible', true),
            'position' => $nextPosition,
            'card_shape' => $validated['card_shape'] ?? 'PORTRAIT',
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        if (!empty($validated['categories'])) {
            $syncData = [];
            foreach ($validated['categories'] as $idx => $catId) {
                $syncData[$catId] = ['position' => $idx];
            }
            $collection->categories()->sync($syncData);
        }

        return redirect()->route('admin.collections.index')
            ->with('status', "Collection '{$collection->name}' created successfully.");
    }

    /**
     * Show the edit form for a collection.
     */
    public function edit(Collection $collection): View
    {
        $collection->load('categories');

        $categories = Category::whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('admin.collections.edit', compact('collection', 'categories'));
    }

    /**
     * Update an existing collection.
     */
    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'slug' => 'required|string|max:80|unique:collections,slug,' . $collection->id,
            'heading' => 'nullable|string|max:90',
            'intro' => 'nullable|string|max:400',
            'is_visible' => 'nullable|boolean',
            'card_shape' => 'nullable|in:SQUARE,PORTRAIT',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        $collection->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'heading' => $validated['heading'] ?? $validated['name'],
            'intro' => $validated['intro'] ?? null,
            'is_visible' => $request->boolean('is_visible', false),
            'card_shape' => $validated['card_shape'] ?? 'PORTRAIT',
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        $syncData = [];
        if (!empty($validated['categories'])) {
            foreach ($validated['categories'] as $idx => $catId) {
                $syncData[$catId] = ['position' => $idx];
            }
        }
        $collection->categories()->sync($syncData);

        return redirect()->route('admin.collections.index')
            ->with('status', "Collection '{$collection->name}' updated successfully.");
    }

    /**
     * Delete a collection.
     */
    public function destroy(Collection $collection): RedirectResponse
    {
        $name = $collection->name;
        $collection->categories()->detach();
        $collection->delete();

        return redirect()->route('admin.collections.index')
            ->with('status', "Collection '{$name}' deleted successfully.");
    }

    /**
     * Quick toggle visibility status.
     */
    public function toggleVisibility(Collection $collection): RedirectResponse
    {
        $collection->is_visible = !$collection->is_visible;
        $collection->save();

        $state = $collection->is_visible ? 'visible' : 'hidden';
        return redirect()->back()
            ->with('status', "Collection '{$collection->name}' is now {$state}.");
    }

    /**
     * Reorder collections.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        foreach ($ids as $pos => $id) {
            Collection::where('id', $id)->update(['position' => $pos]);
        }

        return redirect()->route('admin.collections.index')
            ->with('status', 'Collections reordered successfully.');
    }
}
