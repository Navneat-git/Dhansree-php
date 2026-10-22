<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\NewsletterSubscriber;
use App\Support\Rbac\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNewsletterWebController extends Controller
{
    /**
     * List subscribers.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::CUSTOMER_VIEW) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to view newsletter subscribers.');
        }

        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'source' => $request->query('source'),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $query = NewsletterSubscriber::query();

        if (!empty($filters['search'])) {
            $query->where('email', 'like', '%' . trim((string) $filters['search']) . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $query->orderByDesc('id');
        $paginator = $query->paginate(25, ['*'], 'page', $page);

        $stats = [
            'total' => NewsletterSubscriber::count(),
            'confirmed' => NewsletterSubscriber::where('status', 'CONFIRMED')->count(),
            'pending' => NewsletterSubscriber::where('status', 'PENDING')->count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'UNSUBSCRIBED')->count(),
        ];

        return view('admin.subscribers.index', [
            'subscribers' => collect($paginator->items()),
            'paginator' => $paginator,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Export subscribers as CSV stream.
     */
    public function export(Request $request): StreamedResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::CUSTOMER_VIEW) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to export newsletter subscribers.');
        }

        $status = $request->query('status');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="dhansree-subscribers-' . now()->format('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($status) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Email', 'Status', 'Source', 'Subscribed At', 'Confirmed At']);

            $query = NewsletterSubscriber::query();
            if ($status) {
                $query->where('status', $status);
            }

            $query->orderBy('id')->chunk(500, function ($batch) use ($handle) {
                foreach ($batch as $sub) {
                    fputcsv($handle, [
                        $sub->id,
                        $sub->email,
                        $sub->status,
                        $sub->source,
                        $sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : '',
                        $sub->confirmed_at ? $sub->confirmed_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
