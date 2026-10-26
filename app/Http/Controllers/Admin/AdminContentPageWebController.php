<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\ContentPage;
use App\Models\ContentPageSection;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminContentPageWebController extends Controller
{
    /**
     * Display a listing of all policy and content pages.
     */
    public function index(): View
    {
        $this->authorizeView();

        $pages = ContentPage::withCount('contentPageSections')
            ->orderBy('id')
            ->get();

        return view('admin.pages.index', [
            'pages' => $pages,
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * Show the form for editing the specified policy page.
     */
    public function edit(int $id): View
    {
        $this->authorizeView();

        $page = ContentPage::with(['contentPageSections' => fn($q) => $q->orderBy('position')])
            ->findOrFail($id);

        return view('admin.pages.edit', [
            'page' => $page,
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * Update the specified policy page metadata and preamble.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeManage();

        $page = ContentPage::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:70',
            'intro' => 'nullable|string',
            'is_visible' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
        ]);

        $page->title = trim($validated['title']);
        $page->intro = $validated['intro'] !== null && trim($validated['intro']) !== '' ? trim($validated['intro']) : null;
        $page->is_visible = $request->boolean('is_visible');
        $page->seo_title = !empty($validated['seo_title']) ? trim($validated['seo_title']) : null;
        $page->seo_description = !empty($validated['seo_description']) ? trim($validated['seo_description']) : null;
        $page->version = ((int) $page->version) + 1;
        $page->save();

        return back()->with('success', "Page '{$page->title}' settings updated successfully.");
    }

    /**
     * Quick toggle visibility for a policy page.
     */
    public function toggle(int $id): RedirectResponse
    {
        $this->authorizeManage();

        $page = ContentPage::findOrFail($id);
        $page->is_visible = !$page->is_visible;
        $page->version = ((int) $page->version) + 1;
        $page->save();

        $state = $page->is_visible ? 'published (Live)' : 'hidden (Draft)';
        return back()->with('success', "Page '{$page->title}' is now {$state}.");
    }

    /**
     * Store a new content section in the specified policy page.
     */
    public function storeSection(Request $request, int $pageId): RedirectResponse
    {
        $this->authorizeManage();

        $page = ContentPage::findOrFail($pageId);

        $validated = $request->validate([
            'heading' => 'required|string|max:120',
            'body' => 'nullable|string',
        ]);

        $maxPosition = ContentPageSection::where('page_id', $pageId)->max('position');
        $position = $maxPosition !== null ? ((int) $maxPosition) + 1 : 0;

        ContentPageSection::create([
            'page_id' => $page->id,
            'position' => $position,
            'heading' => trim($validated['heading']),
            'body' => $validated['body'] !== null && trim($validated['body']) !== '' ? trim($validated['body']) : null,
        ]);

        $page->version = ((int) $page->version) + 1;
        $page->save();

        return back()->with('success', "New section '{$validated['heading']}' added successfully.");
    }

    /**
     * Update an existing content section.
     */
    public function updateSection(Request $request, int $sectionId): RedirectResponse
    {
        $this->authorizeManage();

        $section = ContentPageSection::findOrFail($sectionId);

        $validated = $request->validate([
            'heading' => 'required|string|max:120',
            'body' => 'nullable|string',
        ]);

        $section->heading = trim($validated['heading']);
        $section->body = $validated['body'] !== null && trim($validated['body']) !== '' ? trim($validated['body']) : null;
        $section->save();

        if ($section->page) {
            $section->page->version = ((int) $section->page->version) + 1;
            $section->page->save();
        }

        return back()->with('success', "Section '{$section->heading}' updated successfully.");
    }

    /**
     * Delete an existing content section.
     */
    public function destroySection(int $sectionId): RedirectResponse
    {
        $this->authorizeManage();

        $section = ContentPageSection::findOrFail($sectionId);
        $pageId = (int) $section->page_id;
        $heading = $section->heading;

        $section->delete();

        // Reorder remaining sections to maintain contiguous 0..N-1 positions
        $remaining = ContentPageSection::where('page_id', $pageId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        foreach ($remaining as $idx => $s) {
            if ($s->position !== $idx) {
                $s->position = $idx;
                $s->save();
            }
        }

        $page = ContentPage::find($pageId);
        if ($page) {
            $page->version = ((int) $page->version) + 1;
            $page->save();
        }

        return back()->with('success', "Section '{$heading}' deleted successfully.");
    }

    /**
     * Move section up or down within its page.
     */
    public function moveSection(Request $request, int $pageId): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'section_id' => 'required|integer',
            'direction' => 'required|in:up,down',
        ]);

        $section = ContentPageSection::where('page_id', $pageId)->findOrFail((int) $validated['section_id']);
        $direction = $validated['direction'];

        $sections = ContentPageSection::where('page_id', $pageId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $currentIndex = $sections->search(fn($s) => $s->id === $section->id);
        if ($currentIndex === false) {
            return back()->with('error', 'Section not found.');
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex >= 0 && $targetIndex < $sections->count()) {
            $other = $sections[$targetIndex];
            $temp = $section->position;
            $section->position = $other->position;
            $other->position = $temp;

            // Handle potential collision before saving
            if ($section->position === $other->position) {
                $section->position = $targetIndex;
                $other->position = $currentIndex;
            }

            $other->save();
            $section->save();

            return back()->with('success', "Section '{$section->heading}' moved {$direction}.");
        }

        return back();
    }

    /**
     * Check if authenticated admin can view policy pages.
     */
    private function authorizeView(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin || (!$admin->hasSuperAdmin() && !$admin->hasPermission(Permissions::SETTINGS_VIEW) && !$admin->hasPermission(Permissions::SEO_VIEW))) {
            abort(403, 'You do not have permission to view policy and content pages.');
        }
    }

    /**
     * Check if authenticated admin can edit policy pages.
     */
    private function authorizeManage(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin || (!$admin->hasSuperAdmin() && !$admin->hasPermission(Permissions::SETTINGS_UPDATE) && !$admin->hasPermission(Permissions::SEO_UPDATE))) {
            abort(403, 'You do not have permission to modify policy and content pages.');
        }
    }

    /**
     * Helper boolean whether admin can modify pages.
     */
    private function canManage(): bool
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();
        return $admin && ($admin->hasSuperAdmin() || $admin->hasPermission(Permissions::SETTINGS_UPDATE) || $admin->hasPermission(Permissions::SEO_UPDATE));
    }
}
