<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Coupon\AdminCouponsService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminCouponWebController extends Controller
{
    public function __construct(
        protected AdminCouponsService $couponsService
    ) {}

    /**
     * Coupon list.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_VIEW)) {
            abort(403, 'You do not have permission to view coupons.');
        }

        $filters = [
            'search' => $request->query('search'),
            'state' => $request->query('state'),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->couponsService->list($filters, $page, 25);

        $featuredCouponCode = \App\Models\StoreSettings::current()->featured_coupon_code;

        return view('admin.coupons.index', [
            'coupons' => $data['items'],
            'paginator' => $data['paginator'],
            'stats' => $data['stats'],
            'filters' => $filters,
            'featuredCouponCode' => $featuredCouponCode,
            'canCreate' => $admin->hasPermission(Permissions::COUPON_CREATE),
            'canUpdate' => $admin->hasPermission(Permissions::COUPON_UPDATE),
            'canDelete' => $admin->hasPermission(Permissions::COUPON_DELETE),
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_CREATE)) {
            abort(403, 'You do not have permission to create coupons.');
        }

        return view('admin.coupons.create', [
            'types' => CouponType::cases(),
        ]);
    }

    /**
     * Store a new coupon.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_CREATE)) {
            abort(403, 'You do not have permission to create coupons.');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:24|regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/i',
            'description' => 'nullable|string|max:200',
            'type' => 'required|string|in:' . implode(',', CouponType::values()),
            'value' => 'required|numeric|min:0.01',
            'min_order_rupees' => 'nullable|numeric|min:0',
            'max_discount_rupees' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        $type = CouponType::from($validated['type']);

        // Convert UI inputs:
        // Percentage: input is e.g. 10.5 (%) -> 1050 basis points (bps)
        // Fixed Amount: input is e.g. 500 (₹) -> 50000 paise
        $value = ($type === CouponType::PERCENTAGE)
            ? (int) round((float) $validated['value'] * 100)
            : (int) round((float) $validated['value'] * 100);

        $minOrderPaise = !empty($validated['min_order_rupees'])
            ? (int) round((float) $validated['min_order_rupees'] * 100)
            : null;

        $maxDiscountPaise = (!empty($validated['max_discount_rupees']) && $type === CouponType::PERCENTAGE)
            ? (int) round((float) $validated['max_discount_rupees'] * 100)
            : null;

        $data = [
            'code' => strtoupper(trim($validated['code'])),
            'description' => $validated['description'] ?? null,
            'type' => $type->value,
            'value' => $value,
            'min_order_paise' => $minOrderPaise,
            'max_discount_paise' => $maxDiscountPaise,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        $coupon = $this->couponsService->create($data, $admin, $request);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$coupon->code}' has been created successfully.");
    }

    /**
     * Show edit form with redemption preview.
     */
    public function edit(int $id): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_UPDATE)) {
            abort(403, 'You do not have permission to edit coupons.');
        }

        $data = $this->couponsService->detail($id);

        return view('admin.coupons.edit', array_merge($data, [
            'types' => CouponType::cases(),
            'canDelete' => $admin->hasPermission(Permissions::COUPON_DELETE),
        ]));
    }

    /**
     * Update existing coupon.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_UPDATE)) {
            abort(403, 'You do not have permission to edit coupons.');
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:200',
            'type' => 'required|string|in:' . implode(',', CouponType::values()),
            'value' => 'required|numeric|min:0.01',
            'min_order_rupees' => 'nullable|numeric|min:0',
            'max_discount_rupees' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $type = CouponType::from($validated['type']);

        $value = ($type === CouponType::PERCENTAGE)
            ? (int) round((float) $validated['value'] * 100)
            : (int) round((float) $validated['value'] * 100);

        $minOrderPaise = !empty($validated['min_order_rupees'])
            ? (int) round((float) $validated['min_order_rupees'] * 100)
            : null;

        $maxDiscountPaise = (!empty($validated['max_discount_rupees']) && $type === CouponType::PERCENTAGE)
            ? (int) round((float) $validated['max_discount_rupees'] * 100)
            : null;

        $data = [
            'description' => $validated['description'] ?? null,
            'type' => $type->value,
            'value' => $value,
            'min_order_paise' => $minOrderPaise,
            'max_discount_paise' => $maxDiscountPaise,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        $coupon = $this->couponsService->update($id, $data, $admin, $request);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$coupon->code}' has been updated successfully.");
    }

    /**
     * Toggle active state.
     */
    public function toggle(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_UPDATE)) {
            abort(403, 'You do not have permission to edit coupons.');
        }

        $coupon = $this->couponsService->toggleActive($id, $admin, $request);
        $stateText = $coupon->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Coupon '{$coupon->code}' has been {$stateText}.");
    }

    /**
     * Set coupon as default/featured storefront announcement coupon.
     */
    public function setFeatured(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_UPDATE)) {
            abort(403, 'You do not have permission to edit coupons.');
        }

        $coupon = \App\Models\Coupon::findOrFail($id);
        $settings = \App\Models\StoreSettings::current();
        $settings->featured_coupon_code = $coupon->code;
        $settings->save();

        return redirect()->back(fallback: route('admin.coupons.index'))->with('success', "Coupon '{$coupon->code}' is now set as the default storefront coupon on the top announcement bar.");
    }

    /**
     * Delete unused coupon.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::COUPON_DELETE)) {
            abort(403, 'You do not have permission to delete coupons.');
        }

        $this->couponsService->delete($id, $admin, $request);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon has been deleted.');
    }
}
