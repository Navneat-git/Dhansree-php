<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminCategoryWebController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryTreeService $treeService
    ) {}

    /**
     * Categories list & tree explorer view.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');
        $search = $request->query('search');
        $viewMode = $request->query('view', 'tree'); // 'tree' or 'table'

        $categories = $this->categoryService->list([
            'status' => $status,
            'search' => $search,
        ], 100);

        $tree = $this->treeService->getTreeHierarchy($status !== 'archived');
        $categoryOptions = $this->treeService->toCategoryOptions();

        return view('admin.catalog.categories.index', [
            'categories' => $categories,
            'tree' => $tree,
            'categoryOptions' => $categoryOptions,
            'status' => $status,
            'search' => $search,
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * Category create form.
     */
    public function create(): View
    {
        $categoryOptions = $this->treeService->toCategoryOptions();

        return view('admin.catalog.categories.create', [
            'categoryOptions' => $categoryOptions,
        ]);
    }

    /**
     * Store new category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'card_shape' => 'nullable|string|in:square,tall,wide',
            'is_visible' => 'nullable|boolean',
            'show_in_browse' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $category = $this->categoryService->create(
                $validated,
                $adminUser?->id,
                $request
            );

            return redirect()->route('admin.categories.edit', $category)
                ->with('success', "Category '{$category->name}' created successfully.");
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Edit category form.
     */
    public function edit(Category $category): View
    {
        $detail = $this->categoryService->detail($category->id);
        $categoryOptions = $this->treeService->toCategoryOptions();

        return view('admin.catalog.categories.edit', [
            'category' => $detail,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    /**
     * Update category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'card_shape' => 'nullable|string|in:square,tall,wide',
            'is_visible' => 'nullable|boolean',
            'show_in_browse' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
            'version' => 'nullable|integer',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->categoryService->update(
                $category,
                $validated,
                $adminUser?->id,
                $request
            );

            return redirect()->route('admin.categories.edit', $category)
                ->with('success', 'Category details updated successfully.');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle visibility.
     */
    public function toggleVisibility(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $adminUser = $request->attributes->get('admin_user');
        $newVisibility = (bool) $request->input('is_visible', !$category->is_visible);

        try {
            $updated = $this->categoryService->setVisibility($category, $newVisibility, $adminUser?->id, $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'is_visible' => $updated->is_visible,
                    'message' => 'Visibility updated.',
                ]);
            }

            return back()->with('success', 'Category visibility updated.');
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reorder siblings.
     */
    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|integer',
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->categoryService->reorder(
                $validated['parent_id'] ?? null,
                $validated['ordered_ids'],
                $adminUser?->id,
                $request
            );

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Categories reordered.']);
            }

            return back()->with('success', 'Categories reordered successfully.');
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Archive category.
     */
    public function archive(Request $request, Category $category): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->categoryService->archive($category, $adminUser?->id, $request);
            return redirect()->route('admin.categories.index')
                ->with('success', "Category '{$category->name}' was archived.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore category.
     */
    public function restore(Request $request, Category $category): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->categoryService->restore($category, $adminUser?->id, $request);
            return back()->with('success', "Category '{$category->name}' was restored.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Upload category picture.
     */
    public function uploadImage(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'image' => 'required|file|max:10240', // 10MB
            'alt_text' => 'nullable|string|max:160',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->categoryService->setImage(
                $category,
                $request->file('image'),
                $request->input('alt_text', $category->name),
                $adminUser?->id,
                $request
            );

            return back()->with('success', 'Category image uploaded successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete category picture.
     */
    public function removeImage(Request $request, Category $category): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->categoryService->removeImage($category, $adminUser?->id, $request);
            return back()->with('success', 'Category image removed.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
