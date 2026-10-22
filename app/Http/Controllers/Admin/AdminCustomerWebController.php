<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Customer\AdminCustomersService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminCustomerWebController extends Controller
{
    public function __construct(
        protected AdminCustomersService $customersService
    ) {}

    /**
     * Customer listing with filters and pagination.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::CUSTOMER_VIEW)) {
            abort(403, 'You do not have permission to view customers.');
        }

        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->customersService->list($filters, $page, 25);

        return view('admin.customers.index', [
            'customers' => $data['items'],
            'paginator' => $data['paginator'],
            'filters' => $filters,
            'canUpdate' => $admin->hasPermission(Permissions::CUSTOMER_UPDATE),
        ]);
    }

    /**
     * Customer detail view.
     */
    public function show(int $id): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::CUSTOMER_VIEW)) {
            abort(403, 'You do not have permission to view customers.');
        }

        $data = $this->customersService->detail($id);

        return view('admin.customers.show', array_merge($data, [
            'canUpdate' => $admin->hasPermission(Permissions::CUSTOMER_UPDATE),
        ]));
    }

    /**
     * Toggle customer account status (ACTIVE / DISABLED).
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::CUSTOMER_UPDATE)) {
            abort(403, 'You do not have permission to update customer status.');
        }

        $request->validate([
            'status' => 'required|string|in:' . implode(',', CustomerStatus::values()),
        ]);

        $newStatus = CustomerStatus::from((string) $request->input('status'));
        $this->customersService->setStatus($id, $newStatus, $admin, $request);

        $actionText = ($newStatus === CustomerStatus::DISABLED) ? 'deactivated' : 'activated';
        return redirect()->route('admin.customers.show', $id)
            ->with('success', "Customer account has been {$actionText}.");
    }
}
