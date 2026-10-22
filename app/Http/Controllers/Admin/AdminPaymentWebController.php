<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Payment;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminPaymentWebController extends Controller
{
    /**
     * Payments list and reconciliation.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::ORDER_VIEW)) {
            abort(403, 'You do not have permission to view payments.');
        }

        $query = Payment::with(['order.customer', 'recordedByAdminUser'])->latest('id');

        // Status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Provider filter
        if ($provider = $request->query('provider')) {
            $query->where('provider', $provider);
        }

        // Search filter
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('provider_payment_id', 'like', "%{$search}%")
                  ->orWhere('provider_reference', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_number', 'like', "%{$search}%")
                         ->orWhere('customer_name', 'like', "%{$search}%")
                         ->orWhere('customer_phone', 'like', "%{$search}%");
                  });
            });
        }

        // Summary metrics
        $metrics = [
            'total_volume' => (int) Payment::where('status', PaymentStatus::PAID)->sum('amount_paise'),
            'paid_count' => Payment::where('status', PaymentStatus::PAID)->count(),
            'pending_volume' => (int) Payment::where('status', PaymentStatus::PENDING)->sum('amount_paise'),
            'pending_count' => Payment::where('status', PaymentStatus::PENDING)->count(),
            'failed_count' => Payment::where('status', PaymentStatus::FAILED)->count(),
            'refunded_volume' => (int) Payment::where('status', PaymentStatus::REFUNDED)->sum('amount_paise'),
        ];

        $payments = $query->paginate(20)->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'metrics' => $metrics,
            'currentStatus' => $status,
            'currentProvider' => $provider,
            'search' => $search,
            'canUpdate' => $admin->hasPermission(Permissions::ORDER_UPDATE),
        ]);
    }

    /**
     * View detailed payment record.
     */
    public function show(int $id): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::ORDER_VIEW)) {
            abort(403, 'You do not have permission to view payments.');
        }

        $payment = Payment::with(['order.customer', 'recordedByAdminUser', 'paymentEvents', 'paymentRefunds'])
            ->findOrFail($id);

        return view('admin.payments.show', [
            'payment' => $payment,
            'canUpdate' => $admin->hasPermission(Permissions::ORDER_UPDATE),
        ]);
    }

    /**
     * Record cash collected for COD payment.
     */
    public function recordOffline(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::ORDER_UPDATE)) {
            abort(403, 'You do not have permission to update payments.');
        }

        $payment = Payment::findOrFail($id);
        if ($payment->status === PaymentStatus::PAID) {
            return back()->with('info', 'Payment is already marked as paid.');
        }

        $payment->status = PaymentStatus::PAID;
        $payment->paid_at = now();
        $payment->recorded_by_admin_user_id = $admin->id;
        $payment->save();

        if ($payment->order) {
            $payment->order->payment_status = 'PAID';
            $payment->order->save();
        }

        return back()->with('success', "Payment #{$payment->id} marked as Paid/Collected.");
    }
}
