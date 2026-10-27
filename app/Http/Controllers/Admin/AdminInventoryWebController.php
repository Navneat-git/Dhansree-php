<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BackorderPolicy;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\ProductVariant;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminInventoryWebController extends Controller
{
    /**
     * Inventory list and stock management.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::INVENTORY_VIEW)) {
            abort(403, 'You do not have permission to view inventory.');
        }

        $query = ProductVariant::with([
            'product',
            'inventoryLevels.location',
            'optionValues.option',
        ])->whereNull('archived_at')->latest('id');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        // Low stock filter
        if ($request->boolean('low_stock')) {
            $query->whereHas('inventoryLevels', function ($lq) {
                $lq->whereRaw('(on_hand - reserved) <= 5');
            });
        }

        $variants = $query->paginate(25)->withQueryString();
        $locations = InventoryLocation::where('is_active', true)->get();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'locations' => $locations,
            'search' => $search,
            'lowStock' => $request->boolean('low_stock'),
            'canAdjust' => $admin->hasPermission(Permissions::INVENTORY_UPDATE),
        ]);
    }

    /**
     * Adjust stock quantity.
     */
    public function adjust(Request $request, int $variantId): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::INVENTORY_UPDATE)) {
            abort(403, 'You do not have permission to adjust inventory.');
        }

        $validated = $request->validate([
            'location_id' => 'required|integer|exists:inventory_locations,id',
            'quantity' => 'required|integer',
            'mode' => 'nullable|string|in:set,adjust',
            'reason' => 'nullable|string|max:100',
        ]);

        $mode = $validated['mode'] ?? $request->input('mode', 'adjust');

        DB::transaction(function () use ($variantId, $validated, $mode) {
            // Ensure inventory item exists for foreign key constraint
            InventoryItem::firstOrCreate(
                ['variant_id' => $variantId],
                ['backorder_policy' => BackorderPolicy::STOP_SALE]
            );

            $level = InventoryLevel::firstOrCreate(
                ['variant_id' => $variantId, 'location_id' => $validated['location_id']],
                ['on_hand' => 0, 'reserved' => 0]
            );

            if ($mode === 'set') {
                $level->on_hand = max(0, (int) $validated['quantity']);
            } else {
                $level->on_hand = max(0, $level->on_hand + (int) $validated['quantity']);
            }
            $level->save();
        });

        return back()->with('success', 'Inventory updated successfully.');
    }
}
