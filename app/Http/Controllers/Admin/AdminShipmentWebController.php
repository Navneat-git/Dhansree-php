<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Shipment;
use App\Support\Rbac\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminShipmentWebController extends Controller
{
    /**
     * List all courier shipments.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::ORDER_VIEW)) {
            abort(403, 'You do not have permission to view shipments.');
        }

        $query = Shipment::with(['order.customer', 'items.orderItem.variant.product', 'location'])
            ->latest('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($courier = $request->query('courier')) {
            $query->where('courier_name', $courier);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('awb_code', 'like', "%{$search}%")
                  ->orWhere('courier_name', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_number', 'like', "%{$search}%")
                         ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        $shipments = $query->paginate(20)->withQueryString();

        return view('admin.shipments.index', [
            'shipments' => $shipments,
            'currentStatus' => $status,
            'currentCourier' => $courier,
            'search' => $search,
        ]);
    }
}
