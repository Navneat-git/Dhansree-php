<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Services\Banner\AdminBannersService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $storeSettings = StoreSettings::with(['homeHeroProduct.primaryImage', 'smartLivingPromoProduct.primaryImage'])->findOr(1, fn() => StoreSettings::first() ?? new StoreSettings(['id' => 1]));
        $products = Product::where('status', ProductStatus::ACTIVE)->orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.banners.index', [
            'banners' => $data['items'],
            'paginator' => $data['paginator'],
            'stats' => $data['stats'],
            'filters' => $filters,
            'storeSettings' => $storeSettings,
            'products' => $products,
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

    /**
     * Update cross-promotional hub banners (Homepage & Smart Living).
     */
    public function updatePromos(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::BANNER_UPDATE)) {
            abort(403, 'You do not have permission to edit banners.');
        }

        $request->validate([
            'home_hero_product_id' => 'nullable|integer|exists:products,id',
            'home_hero_image_url' => 'nullable|string|max:500',
            'home_hero_image_file' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'home_hero_title' => 'nullable|string|max:120',
            'home_hero_subtitle' => 'nullable|string|max:160',
            'smart_living_promo_product_id' => 'nullable|integer|exists:products,id',
            'smart_living_promo_image_url' => 'nullable|string|max:500',
            'smart_living_promo_image_file' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'smart_living_promo_title' => 'nullable|string|max:120',
            'smart_living_promo_subtitle' => 'nullable|string|max:160',
        ]);

        $settings = StoreSettings::current();

        if ($request->hasFile('home_hero_image_file')) {
            $file = $request->file('home_hero_image_file');
            $filename = 'home_hero_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('media/promos', $file, $filename);
            $settings->home_hero_image_url = asset('storage/media/promos/' . $filename);
        } elseif ($request->filled('home_hero_image_url')) {
            $settings->home_hero_image_url = trim((string) $request->input('home_hero_image_url'));
        } elseif ($request->boolean('home_hero_image_clear')) {
            $settings->home_hero_image_url = null;
        }

        if ($request->hasFile('smart_living_promo_image_file')) {
            $file = $request->file('smart_living_promo_image_file');
            $filename = 'smart_living_promo_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('media/promos', $file, $filename);
            $settings->smart_living_promo_image_url = asset('storage/media/promos/' . $filename);
        } elseif ($request->filled('smart_living_promo_image_url')) {
            $settings->smart_living_promo_image_url = trim((string) $request->input('smart_living_promo_image_url'));
        } elseif ($request->boolean('smart_living_promo_clear')) {
            $settings->smart_living_promo_image_url = null;
        }

        $settings->home_hero_product_id = $request->filled('home_hero_product_id') ? (int) $request->input('home_hero_product_id') : null;
        $settings->home_hero_title = $request->filled('home_hero_title') ? trim((string) $request->input('home_hero_title')) : null;
        $settings->home_hero_subtitle = $request->filled('home_hero_subtitle') ? trim((string) $request->input('home_hero_subtitle')) : null;

        $settings->smart_living_promo_product_id = $request->filled('smart_living_promo_product_id') ? (int) $request->input('smart_living_promo_product_id') : null;
        $settings->smart_living_promo_title = $request->filled('smart_living_promo_title') ? trim((string) $request->input('smart_living_promo_title')) : null;
        $settings->smart_living_promo_subtitle = $request->filled('smart_living_promo_subtitle') ? trim((string) $request->input('smart_living_promo_subtitle')) : null;

        $settings->save();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Hub promotional banners updated successfully.');
    }
}
