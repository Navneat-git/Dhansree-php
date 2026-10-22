<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\InventoryLevel;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today('UTC');

        $totalRevenuePaise = Order::whereNotIn('status', [OrderStatus::CANCELLED])
            ->sum('total_paise') ?? 0;
        $totalRevenueRupees = round($totalRevenuePaise / 100, 2);

        $totalOrders = Order::count();
        $ordersToday = Order::where('placed_at', '>=', $today)->count();

        $activeProductsCount = Product::where('status', ProductStatus::ACTIVE)->count();
        $totalCustomersCount = Customer::count();

        $lowStockCount = InventoryLevel::whereRaw('(on_hand - reserved) <= 5')->count();

        $recentOrders = Order::with(['customer', 'items'])
            ->latest('placed_at')
            ->limit(8)
            ->get();

        $recentAuditLogs = AuditLog::with('adminUser')
            ->latest('occurred_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'totalRevenueRupees' => $totalRevenueRupees,
            'totalOrders' => $totalOrders,
            'ordersToday' => $ordersToday,
            'activeProductsCount' => $activeProductsCount,
            'totalCustomersCount' => $totalCustomersCount,
            'lowStockCount' => $lowStockCount,
            'recentOrders' => $recentOrders,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
