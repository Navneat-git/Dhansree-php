<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Customer's written reviews in Account Portal.
     */
    public function index(Request $request): View|JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();
        $page = max(1, (int) $request->query('page', 1));
        $reviews = $this->reviewService->customerReviews($customer->id, $page);

        if ($request->wantsJson()) {
            return ApiResponse::success([
                'reviews' => $reviews->items(),
                'paginator' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'total' => $reviews->total(),
                ],
            ]);
        }

        return view('store.account.reviews', [
            'customer' => $customer,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Submit a customer review for a delivered order item.
     */
    public function submit(Request $request): RedirectResponse|JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'order_item_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:80',
            'body' => 'required|string|min:10|max:2000',
        ]);

        $review = $this->reviewService->submit($customer, $validated, $request);

        if ($request->wantsJson()) {
            return ApiResponse::success(
                $review,
                201,
                ['message' => 'Thank you! Your review has been submitted and will appear on the store once approved.']
            );
        }

        return back()->with('success', 'Thank you! Your review has been submitted and will appear on the store once approved.');
    }

    /**
     * Update an existing review within 30 days.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:80',
            'body' => 'required|string|min:10|max:2000',
        ]);

        $review = $this->reviewService->edit($customer, $id, $validated, $request);

        if ($request->wantsJson()) {
            return ApiResponse::success(
                $review,
                200,
                ['message' => 'Your review has been updated and is awaiting moderation.']
            );
        }

        return back()->with('success', 'Your review has been updated and is awaiting moderation.');
    }

    /**
     * Public paginated reviews for a product.
     */
    public function productReviews(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->reviewService->publicForProduct($product->id, $page);

        return ApiResponse::success($data);
    }
}
