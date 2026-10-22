<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Enums\AuditActorType;
use App\Enums\CustomerSessionRevokeReason;
use App\Enums\CustomerStatus;
use App\Enums\OrderStatus;
use App\Exceptions\ApiException;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Support\Audit\AuditLogger;
use App\Support\Commerce\IndianStates;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminCustomersService
{
    /**
     * List customers with search, status filter and order aggregates.
     */
    public function list(array $filters, int $page = 1, int $perPage = 25): array
    {
        $query = Customer::query()->withCount('reviews');

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $status = $filters['status'] ?? null;
        if ($status && in_array($status, CustomerStatus::values(), true)) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', 'desc');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $customerIds = collect($paginator->items())->pluck('id')->all();
        $totals = $this->getOrderTotals($customerIds);

        $items = collect($paginator->items())->map(function (Customer $customer) use ($totals) {
            $stat = $totals[$customer->id] ?? null;
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status->value,
                'orderCount' => $stat['count'] ?? 0,
                'totalSpentPaise' => $stat['paise'] ?? 0,
                'reviewCount' => $customer->reviews_count ?? 0,
                'lastOrderAt' => $stat['lastOrderAt'] ?? null,
                'createdAt' => $customer->created_at?->toIso8601String(),
            ];
        })->all();

        return [
            'items' => $items,
            'paginator' => $paginator,
            'totalItems' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
            'currentPage' => $paginator->currentPage(),
        ];
    }

    /**
     * Customer details for staff inspection.
     */
    public function detail(int $id): array
    {
        $customer = Customer::with([
            'addresses' => function ($q) {
                $q->orderBy('is_default_shipping', 'desc')->orderBy('updated_at', 'desc');
            },
            'orders' => function ($q) {
                $q->orderBy('placed_at', 'desc')->limit(20);
            },
            'reviews' => function ($q) {
                $q->with('product:id,name')->orderBy('created_at', 'desc')->limit(20);
            },
        ])->find($id);

        if (!$customer) {
            throw new ApiException('NOT_FOUND', 'We could not find that customer.', 404);
        }

        $totals = $this->getOrderTotals([$id]);
        $summary = $totals[$id] ?? null;

        $activeSessions = CustomerSession::where('customer_id', $id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'status' => $customer->status->value,
            'orderCount' => $summary['count'] ?? 0,
            'totalSpentPaise' => $summary['paise'] ?? 0,
            'reviewCount' => $customer->reviews()->count(),
            'lastOrderAt' => $summary['lastOrderAt'] ?? null,
            'createdAt' => $customer->created_at?->toIso8601String(),
            'emailVerifiedAt' => $customer->email_verified_at?->toIso8601String(),
            'lastLoginAt' => $customer->last_login_at?->toIso8601String(),
            'updatedAt' => $customer->updated_at?->toIso8601String(),
            'activeSessions' => $activeSessions,
            'customer' => $customer,
            'addresses' => $customer->addresses->map(fn (CustomerAddress $addr) => [
                'id' => $addr->id,
                'label' => $addr->label?->value ?? $addr->label,
                'recipientName' => $addr->recipient_name,
                'phone' => $addr->phone,
                'line1' => $addr->line1,
                'line2' => $addr->line2,
                'landmark' => $addr->landmark,
                'city' => $addr->city,
                'stateCode' => $addr->state_code,
                'stateName' => IndianStates::getName($addr->state_code) ?? $addr->state_code,
                'postalCode' => $addr->postal_code,
                'country' => $addr->country ?? 'IN',
                'isDefaultShipping' => (bool) $addr->is_default_shipping,
                'isDefaultBilling' => (bool) $addr->is_default_billing,
                'createdAt' => $addr->created_at?->toIso8601String(),
                'updatedAt' => $addr->updated_at?->toIso8601String(),
            ])->all(),
            'orders' => $customer->orders->map(fn (Order $order) => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'adminPath' => "/admin/orders/{$order->id}",
                'placedAt' => $order->placed_at?->toIso8601String(),
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'paymentStatus' => $order->payment_status->value,
                'totalPaise' => $order->total_paise,
            ])->all(),
            'reviews' => $customer->reviews->map(fn ($review) => [
                'id' => $review->id,
                'productName' => $review->product?->name ?? 'Product #' . $review->product_id,
                'rating' => $review->rating,
                'status' => $review->status?->value ?? $review->status,
                'createdAt' => $review->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Activate or deactivate customer account.
     */
    public function setStatus(int $id, CustomerStatus $newStatus, AdminUser $admin, ?Request $request = null): Customer
    {
        $customer = Customer::find($id);
        if (!$customer) {
            throw new ApiException('NOT_FOUND', 'We could not find that customer.', 404);
        }

        if ($customer->status === $newStatus) {
            return $customer;
        }

        $customer->update(['status' => $newStatus]);

        if ($newStatus === CustomerStatus::DISABLED) {
            CustomerSession::where('customer_id', $id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'revoke_reason' => CustomerSessionRevokeReason::REVOKED_BY_ADMIN,
                ]);
        }

        AuditLogger::log(
            action: $newStatus === CustomerStatus::DISABLED ? 'CUSTOMER_DISABLED' : 'CUSTOMER_ENABLED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'customer',
            entityId: $id,
            request: $request
        );

        return $customer;
    }

    /**
     * Group query for customer order totals.
     *
     * @param int[] $customerIds
     * @return array<int, array{count: int, paise: int, lastOrderAt: string|null}>
     */
    private function getOrderTotals(array $customerIds): array
    {
        if (empty($customerIds)) {
            return [];
        }

        $records = DB::table('orders')
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(total_paise), 0) as total_spent_paise'),
                DB::raw('MAX(placed_at) as last_order_at')
            )
            ->whereIn('customer_id', $customerIds)
            ->whereNotIn('status', [OrderStatus::CANCELLED->value, OrderStatus::EXPIRED->value])
            ->groupBy('customer_id')
            ->get();

        $map = [];
        foreach ($records as $row) {
            $map[(int) $row->customer_id] = [
                'count' => (int) $row->order_count,
                'paise' => (int) $row->total_spent_paise,
                'lastOrderAt' => $row->last_order_at ? (string) $row->last_order_at : null,
            ];
        }

        return $map;
    }
}
