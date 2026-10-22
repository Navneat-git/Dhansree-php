<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Review\AdminReviewsService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminReviewWebController extends Controller
{
    public function __construct(
        protected AdminReviewsService $reviewsService
    ) {}

    /**
     * Review moderation listing.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::REVIEW_VIEW)) {
            abort(403, 'You do not have permission to view reviews.');
        }

        $filters = [
            'status' => $request->query('status'),
            'rating' => $request->query('rating'),
            'search' => $request->query('search'),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->reviewsService->list($filters, $page, 25);

        return view('admin.reviews.index', [
            'reviews' => $data['items'],
            'paginator' => $data['paginator'],
            'stats' => $data['stats'],
            'filters' => $filters,
            'canModerate' => $admin->hasPermission(Permissions::REVIEW_MODERATE),
        ]);
    }

    /**
     * Moderate a review (APPROVE, REJECT, or HIDE).
     */
    public function moderate(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::REVIEW_MODERATE)) {
            abort(403, 'You do not have permission to moderate reviews.');
        }

        $validated = $request->validate([
            'action' => 'required|string|in:APPROVE,REJECT,HIDE',
            'note' => 'nullable|string|max:255',
        ]);

        $review = $this->reviewsService->moderate(
            id: $id,
            action: $validated['action'],
            note: $validated['note'] ?? null,
            admin: $admin,
            request: $request
        );

        $actionWord = match ($validated['action']) {
            'APPROVE' => 'approved and published',
            'REJECT' => 'rejected',
            'HIDE' => 'hidden from storefront',
        };

        return back()->with('success', "Review #{$id} has been {$actionWord}.");
    }
}
