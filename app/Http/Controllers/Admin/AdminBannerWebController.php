<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Banner\AdminBannersService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminBannerWebController extends Controller
{
    public function __construct(
        protected AdminBannersService $bannersService
    ) {}

    /**
     * Banners listing.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_VIEW)) {
            abort(403, 'You do not have permission to view banners.');
        }

        $filters = [
            'search' => $request->query('search'),
            'is_active' => $request->query('is_active'),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->bannersService->list($filters, $page, 20);

        return view('admin.banners.index', [
            'banners' => $data['items'],
            'paginator' => $data['paginator'],
            'stats' => $data['stats'],
            'filters' => $filters,
            'canCreate' => $admin->hasPermission(Permissions::BANNER_CREATE),
            'canUpdate' => $admin->hasPermission(Permissions::BANNER_UPDATE),
            'canDelete' => $admin->hasPermission(Permissions::BANNER_DELETE),
        ]);
    }

    /**
     * Show create banner form.
     */
    public function create(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_CREATE)) {
            abort(403, 'You do not have permission to create banners.');
        }

        return view('admin.banners.create');
    }

    /**
     * Store new banner.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_CREATE)) {
            abort(403, 'You do not have permission to create banners.');
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'eyebrow' => 'nullable|string|max:60',
            'subtitle' => 'nullable|string|max:160',
            'caption' => 'nullable|string|max:255',
            'link_path' => ['nullable', 'string', 'max:255', 'regex:/^\/[a-zA-Z0-9\/_-]*$/'],
            'cta_label' => 'nullable|string|max:50',
            'word_mode' => 'nullable|in:ON_PICTURE,ON_LIGHTENED,LIGHT_ON_PICTURE',
            'desktop_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:3072',
            'image_alt_text' => 'nullable|string|max:120',
            'mobile_alt_text' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        $banner = $this->bannersService->create(
            $validated,
            $request->file('desktop_image'),
            $request->file('mobile_image'),
            $admin,
            $request
        );

        return redirect()->route('admin.banners.index')
            ->with('success', "Banner '{$banner->title}' created successfully.");
    }

    /**
     * Show edit banner form.
     */
    public function edit(int $id): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to edit banners.');
        }

        $banner = $this->bannersService->detail($id);

        return view('admin.banners.edit', [
            'banner' => $banner,
            'canDelete' => $admin->hasPermission(Permissions::BANNER_DELETE),
        ]);
    }

    /**
     * Update existing banner.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to edit banners.');
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'eyebrow' => 'nullable|string|max:60',
            'subtitle' => 'nullable|string|max:160',
            'caption' => 'nullable|string|max:255',
            'link_path' => ['nullable', 'string', 'max:255', 'regex:/^\/[a-zA-Z0-9\/_-]*$/'],
            'cta_label' => 'nullable|string|max:50',
            'word_mode' => 'nullable|in:ON_PICTURE,ON_LIGHTENED,LIGHT_ON_PICTURE',
            'desktop_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:3072',
            'image_alt_text' => 'nullable|string|max:120',
            'mobile_alt_text' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $banner = $this->bannersService->update(
            $id,
            $validated,
            $request->file('desktop_image'),
            $request->file('mobile_image'),
            $admin,
            $request
        );

        return redirect()->route('admin.banners.index')
            ->with('success', "Banner '{$banner->title}' updated successfully.");
    }

    /**
     * Toggle banner active status.
     */
    public function toggle(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to edit banners.');
        }

        $banner = $this->bannersService->toggleActive($id, $admin, $request);
        $stateText = $banner->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Banner '{$banner->title}' {$stateText}.");
    }

    /**
     * Delete banner.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_DELETE)) {
            abort(403, 'You do not have permission to delete banners.');
        }

        $this->bannersService->delete($id, $admin, $request);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner has been deleted.');
    }
}
