<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\QuickLink;
use App\Models\QuickLinkGroup;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminQuickLinkWebController extends Controller
{
    /**
     * Display Quick Links groups & individual links.
     */
    public function index(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_VIEW) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to view Quick Links.');
        }

        $groups = QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')])
            ->orderBy('position')
            ->get();

        return view('admin.quick-links.index', [
            'groups' => $groups,
            'canManage' => $admin->hasPermission(Permissions::SETTINGS_UPDATE) || $admin->hasSuperAdmin(),
        ]);
    }

    /**
     * Store a new quick link group.
     */
    public function storeGroup(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit Quick Links.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:60',
            'position' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|boolean',
            'show_on_home' => 'nullable|boolean',
            'show_on_listing' => 'nullable|boolean',
            'show_on_product' => 'nullable|boolean',
        ]);

        $group = QuickLinkGroup::create([
            'title' => $validated['title'],
            'position' => (int) ($validated['position'] ?? QuickLinkGroup::max('position') + 1),
            'is_visible' => (bool) ($request->has('is_visible') ? $request->boolean('is_visible') : true),
            'show_on_home' => $request->boolean('show_on_home'),
            'show_on_listing' => $request->boolean('show_on_listing'),
            'show_on_product' => $request->boolean('show_on_product'),
            'version' => 0,
        ]);

        return back()->with('success', "Quick Link Group '{$group->title}' created successfully.");
    }

    /**
     * Update an existing quick link group.
     */
    public function updateGroup(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit Quick Links.');
        }

        $group = QuickLinkGroup::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:60',
            'position' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|boolean',
            'show_on_home' => 'nullable|boolean',
            'show_on_listing' => 'nullable|boolean',
            'show_on_product' => 'nullable|boolean',
        ]);

        $group->update([
            'title' => $validated['title'],
            'position' => (int) ($validated['position'] ?? $group->position),
            'is_visible' => $request->boolean('is_visible'),
            'show_on_home' => $request->boolean('show_on_home'),
            'show_on_listing' => $request->boolean('show_on_listing'),
            'show_on_product' => $request->boolean('show_on_product'),
            'version' => $group->version + 1,
        ]);

        return back()->with('success', "Quick Link Group '{$group->title}' updated.");
    }

    /**
     * Delete a quick link group and child links.
     */
    public function destroyGroup(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to delete Quick Links.');
        }

        $group = QuickLinkGroup::findOrFail($id);
        $group->quickLinks()->delete();
        $group->delete();

        return back()->with('success', 'Quick Link Group deleted.');
    }

    /**
     * Add a link into a group.
     */
    public function storeLink(Request $request, int $groupId): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit Quick Links.');
        }

        $group = QuickLinkGroup::findOrFail($groupId);

        $validated = $request->validate([
            'label' => 'required|string|max:60',
            'path' => 'required|string|max:200',
            'position' => 'nullable|integer|min:0',
        ]);

        QuickLink::create([
            'group_id' => $group->id,
            'label' => trim($validated['label']),
            'path' => trim($validated['path']),
            'position' => (int) ($validated['position'] ?? (QuickLink::where('group_id', $group->id)->max('position') + 1)),
        ]);

        return back()->with('success', "Quick Link '{$validated['label']}' added.");
    }

    /**
     * Update an individual quick link.
     */
    public function updateLink(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit Quick Links.');
        }

        $link = QuickLink::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:60',
            'path' => 'required|string|max:200',
            'position' => 'nullable|integer|min:0',
        ]);

        $link->update([
            'label' => trim($validated['label']),
            'path' => trim($validated['path']),
            'position' => (int) ($validated['position'] ?? $link->position),
        ]);

        return back()->with('success', "Quick Link '{$link->label}' updated.");
    }

    /**
     * Delete an individual quick link.
     */
    public function destroyLink(int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to edit Quick Links.');
        }

        $link = QuickLink::findOrFail($id);
        $link->delete();

        return back()->with('success', 'Quick Link deleted.');
    }
}
