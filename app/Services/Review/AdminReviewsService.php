<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\AuditActorType;
use App\Enums\ReviewStatus;
use App\Models\AdminUser;
use App\Models\Review;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminReviewsService
{
    /**
     * List reviews for staff moderation.
     *
     * @return array{
     *     items: \Illuminate\Support\Collection<int, Review>,
     *     paginator: LengthAwarePaginator,
     *     stats: array{total: int, pending: int, approved: int, rejected: int, hidden: int}
     * }
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $query = Review::query()
            ->with([
                'product:id,name,slug',
                'customer:id,name',
                'order:id,order_number',
                'orderItem:id,variant_title',
                'moderatedByAdminUser:id,name',
            ]);

        if (!empty($filters['status'])) {
            $status = strtoupper(trim((string) $filters['status']));
            if (in_array($status, ReviewStatus::values(), true)) {
                $query->where('status', $status);
            }
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('body', 'like', $search)
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', $search);
                    })
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', $search);
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', $search);
                    });
            });
        }

        // Reviews pending decision first, then newest
        $query->orderByRaw("CASE WHEN status = 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Quick moderation stats
        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('status', ReviewStatus::PENDING)->count(),
            'approved' => Review::where('status', ReviewStatus::APPROVED)->count(),
            'rejected' => Review::where('status', ReviewStatus::REJECTED)->count(),
            'hidden' => Review::where('status', ReviewStatus::HIDDEN)->count(),
        ];

        return [
            'items' => collect($paginator->items()),
            'paginator' => $paginator,
            'stats' => $stats,
        ];
    }

    /**
     * Fast count of reviews awaiting staff moderation.
     */
    public function pendingCount(): int
    {
        return Review::where('status', ReviewStatus::PENDING)->count();
    }

    /**
     * Moderate a review: APPROVE, REJECT, or HIDE.
     * Enforces the database CHECK constraint:
     * - status = 'APPROVED' <=> published_at IS NOT NULL
     * - moderated_at IS NOT NULL when status != 'PENDING'
     */
    public function moderate(
        int $id,
        string $action,
        ?string $note,
        AdminUser $admin,
        ?Request $request = null
    ): Review {
        /** @var Review|null $review */
        $review = Review::find($id);
        if (!$review) {
            throw new NotFoundHttpException('We could not find that review.');
        }

        $actionUpper = strtoupper(trim($action));
        $now = now();

        $auditAction = match ($actionUpper) {
            'APPROVE' => 'REVIEW_APPROVED',
            'REJECT' => 'REVIEW_REJECTED',
            'HIDE' => 'REVIEW_HIDDEN',
            default => throw new UnprocessableEntityHttpException("Invalid moderation action: {$action}"),
        };

        $newStatus = match ($actionUpper) {
            'APPROVE' => ReviewStatus::APPROVED,
            'REJECT' => ReviewStatus::REJECTED,
            'HIDE' => ReviewStatus::HIDDEN,
        };

        $publishedAt = ($newStatus === ReviewStatus::APPROVED) ? $now : null;

        $review->update([
            'status' => $newStatus,
            'published_at' => $publishedAt,
            'moderated_at' => $now,
            'moderated_by_admin_user_id' => $admin->id,
            'moderation_note' => $note ? mb_substr(trim($note), 0, 255) : null,
            'version' => $review->version + 1,
        ]);

        AuditLogger::log(
            action: $auditAction,
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'review',
            entityId: $review->id,
            metadata: [
                'productId' => $review->product_id,
                'rating' => $review->rating,
                'status' => $newStatus->value,
                'note' => $note,
            ],
            request: $request
        );

        return $review->fresh(['product:id,name', 'customer:id,name', 'moderatedByAdminUser:id,name']);
    }
}
