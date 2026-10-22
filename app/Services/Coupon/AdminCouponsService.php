<?php

declare(strict_types=1);

namespace App\Services\Coupon;

use App\Enums\AuditActorType;
use App\Enums\CouponType;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminCouponsService
{
    /**
     * List coupons with filtering and pagination.
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $now = now();
        $query = Coupon::query()->with('createdByAdminUser:id,name');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if (!empty($filters['state'])) {
            $state = strtolower(trim((string) $filters['state']));
            match ($state) {
                'usable' => $query->where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                    }),
                'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now),
                'expired' => $query->where('expires_at', '<=', $now),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        $query->orderByDesc('created_at');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('is_active', true)->count(),
            'expired' => Coupon::where('expires_at', '<=', $now)->count(),
            'redeemed_total' => (int) Coupon::sum('times_redeemed'),
        ];

        return [
            'items' => collect($paginator->items()),
            'paginator' => $paginator,
            'stats' => $stats,
        ];
    }

    /**
     * Get single coupon with recent redemptions.
     */
    public function detail(int $id): array
    {
        /** @var Coupon|null $coupon */
        $coupon = Coupon::with([
            'createdByAdminUser:id,name',
            'couponRedemptions' => function ($q) {
                $q->with('order:id,order_number,total_paise,placed_at')->orderByDesc('id')->take(20);
            },
        ])->find($id);

        if (!$coupon) {
            throw new NotFoundHttpException('Coupon not found.');
        }

        $isDeletable = ($coupon->times_redeemed === 0 && $coupon->couponRedemptions->isEmpty());

        return [
            'coupon' => $coupon,
            'isDeletable' => $isDeletable,
        ];
    }

    /**
     * Create a new coupon.
     */
    public function create(array $data, AdminUser $admin, ?Request $request = null): Coupon
    {
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        if (!preg_match('/^[A-Z0-9]+(-[A-Z0-9]+)*$/', $code)) {
            throw new UnprocessableEntityHttpException('Coupon code must contain only uppercase letters, numbers, and hyphens (e.g. WELCOME10, FESTIVE-500).');
        }

        if (Coupon::where('code', $code)->exists()) {
            throw new ConflictHttpException("A coupon with code '{$code}' already exists.");
        }

        $type = CouponType::tryFrom((string) ($data['type'] ?? ''));
        if (!$type) {
            throw new UnprocessableEntityHttpException('Invalid coupon discount type.');
        }

        $value = (int) ($data['value'] ?? 0);
        if ($value < 1) {
            throw new UnprocessableEntityHttpException('Coupon discount value must be at least 1.');
        }

        if ($type === CouponType::PERCENTAGE && $value > 10000) {
            throw new UnprocessableEntityHttpException('Percentage discount cannot exceed 100% (10000 basis points).');
        }

        $maxDiscountPaise = null;
        if (!empty($data['max_discount_paise']) && $type === CouponType::PERCENTAGE) {
            $maxDiscountPaise = (int) $data['max_discount_paise'];
        }

        $minOrderPaise = !empty($data['min_order_paise']) ? (int) $data['min_order_paise'] : null;
        $usageLimit = !empty($data['usage_limit']) ? max(1, (int) $data['usage_limit']) : null;
        $perCustomerLimit = !empty($data['per_customer_limit']) ? max(1, (int) $data['per_customer_limit']) : null;

        $startsAt = !empty($data['starts_at']) ? Carbon::parse($data['starts_at']) : null;
        $expiresAt = !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        if ($startsAt && $expiresAt && $expiresAt->lte($startsAt)) {
            throw new UnprocessableEntityHttpException('Expiry date must be after the start date.');
        }

        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;
        $description = !empty($data['description']) ? mb_substr(trim((string) $data['description']), 0, 200) : null;

        $coupon = Coupon::create([
            'code' => $code,
            'description' => $description,
            'type' => $type,
            'value' => $value,
            'min_order_paise' => $minOrderPaise,
            'max_discount_paise' => $maxDiscountPaise,
            'usage_limit' => $usageLimit,
            'per_customer_limit' => $perCustomerLimit,
            'times_redeemed' => 0,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
            'created_by_admin_user_id' => $admin->id,
            'version' => 0,
        ]);

        AuditLogger::log(
            action: 'COUPON_CREATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'coupon',
            entityId: $coupon->id,
            metadata: [
                'code' => $code,
                'type' => $type->value,
                'value' => $value,
            ],
            request: $request
        );

        return $coupon;
    }

    /**
     * Update an existing coupon.
     */
    public function update(int $id, array $data, AdminUser $admin, ?Request $request = null): Coupon
    {
        /** @var Coupon|null $coupon */
        $coupon = Coupon::find($id);
        if (!$coupon) {
            throw new NotFoundHttpException('Coupon not found.');
        }

        $type = CouponType::tryFrom((string) ($data['type'] ?? $coupon->type->value));
        if (!$type) {
            throw new UnprocessableEntityHttpException('Invalid coupon discount type.');
        }

        $value = isset($data['value']) ? (int) $data['value'] : $coupon->value;
        if ($value < 1) {
            throw new UnprocessableEntityHttpException('Coupon discount value must be at least 1.');
        }

        if ($type === CouponType::PERCENTAGE && $value > 10000) {
            throw new UnprocessableEntityHttpException('Percentage discount cannot exceed 100% (10000 basis points).');
        }

        $maxDiscountPaise = null;
        if ($type === CouponType::PERCENTAGE) {
            $maxDiscountPaise = isset($data['max_discount_paise']) && $data['max_discount_paise'] !== ''
                ? (int) $data['max_discount_paise']
                : null;
        }

        $minOrderPaise = isset($data['min_order_paise']) && $data['min_order_paise'] !== ''
            ? (int) $data['min_order_paise']
            : null;

        $usageLimit = isset($data['usage_limit']) && $data['usage_limit'] !== ''
            ? max(1, (int) $data['usage_limit'])
            : null;

        $perCustomerLimit = isset($data['per_customer_limit']) && $data['per_customer_limit'] !== ''
            ? max(1, (int) $data['per_customer_limit'])
            : null;

        $startsAt = !empty($data['starts_at']) ? Carbon::parse($data['starts_at']) : null;
        $expiresAt = !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        if ($startsAt && $expiresAt && $expiresAt->lte($startsAt)) {
            throw new UnprocessableEntityHttpException('Expiry date must be after the start date.');
        }

        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : $coupon->is_active;
        $description = isset($data['description']) ? mb_substr(trim((string) $data['description']), 0, 200) : $coupon->description;

        $coupon->update([
            'description' => $description,
            'type' => $type,
            'value' => $value,
            'min_order_paise' => $minOrderPaise,
            'max_discount_paise' => $maxDiscountPaise,
            'usage_limit' => $usageLimit,
            'per_customer_limit' => $perCustomerLimit,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
            'version' => $coupon->version + 1,
        ]);

        AuditLogger::log(
            action: 'COUPON_UPDATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'coupon',
            entityId: $coupon->id,
            metadata: [
                'code' => $coupon->code,
                'type' => $type->value,
                'value' => $value,
                'is_active' => $isActive,
            ],
            request: $request
        );

        return $coupon;
    }

    /**
     * Delete an unused coupon. Refuses if coupon has any redemptions.
     */
    public function delete(int $id, AdminUser $admin, ?Request $request = null): void
    {
        /** @var Coupon|null $coupon */
        $coupon = Coupon::withCount('couponRedemptions')->find($id);
        if (!$coupon) {
            throw new NotFoundHttpException('Coupon not found.');
        }

        if ($coupon->times_redeemed > 0 || $coupon->coupon_redemptions_count > 0) {
            throw new ConflictHttpException('This coupon has been used on orders, so it cannot be deleted. Switch it off instead.');
        }

        $code = $coupon->code;
        $coupon->delete();

        AuditLogger::log(
            action: 'COUPON_DELETED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'coupon',
            entityId: $id,
            metadata: [
                'code' => $code,
            ],
            request: $request
        );
    }

    /**
     * Toggle coupon active state.
     */
    public function toggleActive(int $id, AdminUser $admin, ?Request $request = null): Coupon
    {
        /** @var Coupon|null $coupon */
        $coupon = Coupon::find($id);
        if (!$coupon) {
            throw new NotFoundHttpException('Coupon not found.');
        }

        $newActive = !$coupon->is_active;
        $coupon->update([
            'is_active' => $newActive,
            'version' => $coupon->version + 1,
        ]);

        AuditLogger::log(
            action: 'COUPON_UPDATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'coupon',
            entityId: $coupon->id,
            metadata: [
                'code' => $coupon->code,
                'is_active' => $newActive,
            ],
            request: $request
        );

        return $coupon;
    }
}
