<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Category;
use App\Services\Faq\AdminFaqService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminFaqWebController extends Controller
{
    public function __construct(
        protected AdminFaqService $faqService
    ) {}

    /**
     * FAQ groups & entries index.
     */
    public function index(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_VIEW) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to view FAQs.');
        }

        $groups = $this->faqService->listGroupsWithEntries();
        $ungroupedEntries = \App\Models\FaqEntry::whereNull('group_id')->orderBy('position')->get();
        $categories = Category::whereNull('archived_at')->orderBy('name')->get();

        return view('admin.faqs.index', [
            'groups' => $groups,
            'ungroupedEntries' => $ungroupedEntries,
            'categories' => $categories,
            'canManage' => $admin->hasPermission(Permissions::SETTINGS_UPDATE) || $admin->hasSuperAdmin(),
        ]);
    }

    /**
     * Store new FAQ group.
     */
    public function storeGroup(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit FAQs.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'position' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|boolean',
            'show_on_faq_page' => 'nullable|boolean',
            'show_on_listing' => 'nullable|boolean',
            'show_on_product' => 'nullable|boolean',
        ]);

        $group = $this->faqService->createGroup($validated);

        return back()->with('success', "FAQ Group '{$group->name}' created successfully.");
    }

    /**
     * Update existing FAQ group.
     */
    public function updateGroup(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit FAQs.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'position' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|boolean',
            'show_on_faq_page' => 'nullable|boolean',
            'show_on_listing' => 'nullable|boolean',
            'show_on_product' => 'nullable|boolean',
        ]);

        $this->faqService->updateGroup($id, $validated);

        return back()->with('success', 'FAQ Group updated.');
    }

    /**
     * Delete FAQ group.
     */
    public function deleteGroup(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit FAQs.');
        }

        $this->faqService->deleteGroup($id);

        return back()->with('success', 'FAQ Group and associated entries deleted.');
    }

    /**
     * Store new FAQ entry under a group.
     */
    public function storeEntry(Request $request, int $groupId): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit FAQs.');
        }

        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:3000',
            'position' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|boolean',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
        ]);

        $this->faqService->createEntry($groupId, $validated);

        return back()->with('success', 'FAQ Question added.');
    }

    /**
     * Update an FAQ entry.
     */
    public function updateEntry(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit FAQs.');
        }

        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:3000',
            'position' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|boolean',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
        ]);

        $this->faqService->updateEntry($id, $validated);

        return back()->with('success', 'FAQ Question updated.');
    }

    /**
     * Delete an FAQ entry.
     */
    public function deleteEntry(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit FAQs.');
        }

        $this->faqService->deleteEntry($id);

        return back()->with('success', 'FAQ entry removed.');
    }
}
