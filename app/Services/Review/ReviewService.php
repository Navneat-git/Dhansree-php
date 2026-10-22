<?php

declare(strict_types=1);

namespace App\Services\Review;

use App\Enums\AuditActorType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReviewService
{
    public const REVIEW_MIN_RATING = 1;
    public const REVIEW_MAX_RATING = 5;
    public const REVIEW_TITLE_MAX_LENGTH = 80;
    public const REVIEW_BODY_MIN_LENGTH = 10;
    public const REVIEW_BODY_MAX_LENGTH = 2000;
    public const REVIEW_EDIT_WINDOW_DAYS = 30;
    public const REVIEWS_PAGE_SIZE = 10;

    /**
     * Rating summary of a product from approved reviews only.
     * Returns null if no approved reviews exist.
     *
     * @return array{
     *     average: float,
     *     count: int,
     *     breakdown: array<int, int>
     * }|null
     */
    public function ratingFor(int $productId): ?array
    {
        $rows = DB::table('reviews')
            ->select('rating', DB::raw('count(*) as count'))
            ->where('product_id', $productId)
            ->where('status', ReviewStatus::APPROVED->value)
            ->groupBy('rating')
            ->get();

        $breakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $totalCount = 0;
        $totalScore = 0;

        foreach ($rows as $row) {
            $r = (int) $row->rating;
            $c = (int) $row->count;
            if (isset($breakdown[$r])) {
                $breakdown[$r] = $c;
                $totalCount += $c;
                $totalScore += ($r * $c);
            }
        }

        if ($totalCount === 0) {
            return null;
        }

        $average = round($totalScore / $totalCount, 1);

        return [
            'average' => $average,
            'count' => $totalCount,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get paginated approved reviews for storefront product page.
     */
    public function publicForProduct(int $productId, int $page = 1, int $perPage = self::REVIEWS_PAGE_SIZE): array
    {
        $query = Review::query()
            ->with(['customer:id,name', 'orderItem:id,variant_title'])
            ->where('product_id', $productId)
            ->where('status', ReviewStatus::APPROVED)
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $summary = $this->ratingFor($productId);

        $reviews = collect($paginator->items())->map(function (Review $review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'author_name' => $review->author_name,
                'verified_purchase' => true,
                'published_at' => $review->published_at?->toIso8601String() ?? now()->toIso8601String(),
                'variant_title' => $review->orderItem?->variant_title && $review->orderItem?->variant_title !== 'Default'
                    ? $review->orderItem->variant_title
                    : null,
            ];
        })->all();

        return [
            'summary' => $summary,
            'reviews' => $reviews,
            'total_items' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * Check if a customer can review an order item.
     *
     * @return array{eligible: bool, reason?: string, message?: string, orderItem?: OrderItem}
     */
    public function checkEligibility(Customer $customer, int $orderItemId): array
    {
        /** @var OrderItem|null $line */
        $line = OrderItem::query()
            ->with(['order:id,customer_id,status,payment_status,order_number', 'review:id,order_item_id'])
            ->where('id', $orderItemId)
            ->whereHas('order', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->first();

        if (!$line) {
            return [
                'eligible' => false,
                'reason' => 'NOT_FOUND',
                'message' => 'Purchased order item not found.',
            ];
        }

        if ($line->review !== null) {
            return [
                'eligible' => false,
                'reason' => 'ALREADY_REVIEWED',
                'message' => 'You have already reviewed this item.',
                'orderItem' => $line,
            ];
        }

        $order = $line->order;
        if ($order->status === OrderStatus::CANCELLED || $order->status === OrderStatus::EXPIRED) {
            return [
                'eligible' => false,
                'reason' => 'ORDER_CLOSED',
                'message' => 'This order was not completed, so it cannot be reviewed.',
                'orderItem' => $line,
            ];
        }

        if ($order->payment_status === PaymentStatus::REFUNDED) {
            return [
                'eligible' => false,
                'reason' => 'REFUNDED',
                'message' => 'This order was refunded, so it cannot be reviewed.',
                'orderItem' => $line,
            ];
        }

        if ($order->status !== OrderStatus::DELIVERED) {
            return [
                'eligible' => false,
                'reason' => 'NOT_DELIVERED',
                'message' => 'You can write a review once this order has been delivered.',
                'orderItem' => $line,
            ];
        }

        return [
            'eligible' => true,
            'orderItem' => $line,
        ];
    }

    /**
     * Submit a new customer review for a delivered order item.
     */
    public function submit(Customer $customer, array $data, ?Request $request = null): Review
    {
        $orderItemId = (int) ($data['order_item_id'] ?? 0);
        $eligibility = $this->checkEligibility($customer, $orderItemId);

        if (!$eligibility['eligible']) {
            if ($eligibility['reason'] === 'NOT_FOUND') {
                throw new NotFoundHttpException('We could not find that purchase.');
            }
            throw new ConflictHttpException($eligibility['message'] ?? 'Cannot review this item.');
        }

        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < self::REVIEW_MIN_RATING || $rating > self::REVIEW_MAX_RATING) {
            throw new UnprocessableEntityHttpException('Rating must be between 1 and 5 stars.');
        }

        $body = trim((string) ($data['body'] ?? ''));
        if (mb_strlen($body) < self::REVIEW_BODY_MIN_LENGTH) {
            throw new UnprocessableEntityHttpException('Review text must be at least 10 characters long.');
        }
        if (mb_strlen($body) > self::REVIEW_BODY_MAX_LENGTH) {
            throw new UnprocessableEntityHttpException('Review text cannot exceed 2000 characters.');
        }

        $title = !empty($data['title']) ? mb_substr(trim((string) $data['title']), 0, self::REVIEW_TITLE_MAX_LENGTH) : null;

        /** @var OrderItem $line */
        $line = $eligibility['orderItem'];

        $review = DB::transaction(function () use ($line, $customer, $rating, $title, $body) {
            return Review::create([
                'product_id' => $line->product_id,
                'customer_id' => $customer->id,
                'order_id' => $line->order_id,
                'order_item_id' => $line->id,
                'rating' => $rating,
                'title' => $title,
                'body' => $body,
                'status' => ReviewStatus::PENDING,
                'published_at' => null,
                'moderated_at' => null,
                'version' => 0,
            ]);
        });

        AuditLogger::log(
            action: 'REVIEW_SUBMITTED',
            actorType: AuditActorType::CUSTOMER,
            actorCustomerId: $customer->id,
            entityType: 'review',
            entityId: $review->id,
            metadata: [
                'productId' => $line->product_id,
                'rating' => $rating,
            ],
            request: $request
        );

        return $review->load(['product:id,name,slug', 'orderItem:id,variant_title']);
    }

    /**
     * Edit a customer's review within the 30-day window.
     * An edited review resets to PENDING and clears published_at for staff re-moderation.
     */
    public function edit(Customer $customer, int $reviewId, array $data, ?Request $request = null): Review
    {
        /** @var Review|null $review */
        $review = Review::where('id', $reviewId)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$review) {
            throw new NotFoundHttpException('We could not find that review.');
        }

        if (!$review->isEditable()) {
            throw new ConflictHttpException('This review can no longer be changed. Write to us if something is wrong.');
        }

        $rating = (int) ($data['rating'] ?? $review->rating);
        if ($rating < self::REVIEW_MIN_RATING || $rating > self::REVIEW_MAX_RATING) {
            throw new UnprocessableEntityHttpException('Rating must be between 1 and 5 stars.');
        }

        $body = trim((string) ($data['body'] ?? $review->body));
        if (mb_strlen($body) < self::REVIEW_BODY_MIN_LENGTH) {
            throw new UnprocessableEntityHttpException('Review text must be at least 10 characters long.');
        }
        if (mb_strlen($body) > self::REVIEW_BODY_MAX_LENGTH) {
            throw new UnprocessableEntityHttpException('Review text cannot exceed 2000 characters.');
        }

        $title = isset($data['title']) ? mb_substr(trim((string) $data['title']), 0, self::REVIEW_TITLE_MAX_LENGTH) : $review->title;

        $review->update([
            'rating' => $rating,
            'title' => $title,
            'body' => $body,
            'status' => ReviewStatus::PENDING,
            'published_at' => null,
            'moderated_at' => null,
            'moderated_by_admin_user_id' => null,
            'moderation_note' => null,
            'version' => $review->version + 1,
        ]);

        AuditLogger::log(
            action: 'REVIEW_EDITED',
            actorType: AuditActorType::CUSTOMER,
            actorCustomerId: $customer->id,
            entityType: 'review',
            entityId: $review->id,
            metadata: [
                'rating' => $rating,
            ],
            request: $request
        );

        return $review->fresh(['product:id,name,slug', 'orderItem:id,variant_title']);
    }

    /**
     * List all reviews written by a customer with pagination.
     */
    public function customerReviews(int $customerId, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        return Review::query()
            ->with([
                'product:id,name,slug,status',
                'orderItem:id,product_name,variant_title',
                'order:id,order_number',
            ])
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
