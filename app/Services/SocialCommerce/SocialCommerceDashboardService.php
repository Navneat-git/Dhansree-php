<?php

declare(strict_types=1);

namespace App\Services\SocialCommerce;

use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialPublishingAccount;
use App\Models\SocialPublishingQueue;
use App\Models\SocialReel;
use App\Models\SocialSupplier;
use Illuminate\Support\Facades\DB;

class SocialCommerceDashboardService
{
    /**
     * Get aggregate statistics and summary data for the dashboard.
     */
    public function getDashboardMetrics(): array
    {
        // Media Inbox counts
        $totalMedia = SocialMediaAsset::count();
        $unprocessedMedia = SocialMediaAsset::where('status', 'UNPROCESSED')->count();
        $inProductMedia = SocialMediaAsset::where('status', 'IN_PRODUCT')->count();
        $totalStorageBytes = (int) SocialMediaAsset::sum('file_size_bytes');

        // Products counts
        $totalProducts = SocialProduct::count();
        $availableProducts = SocialProduct::where('availability_status', 'AVAILABLE')->count();
        $newProducts = SocialProduct::where('availability_status', 'NEW')->count();
        $outOfStockProducts = SocialProduct::where('availability_status', 'OUT_OF_STOCK')->count();
        $promotedToWebCount = SocialProduct::where('is_promoted_to_web', true)->count();

        // Suppliers count
        $activeSuppliers = SocialSupplier::where('is_active', true)->count();

        // Reels count
        $totalReels = SocialReel::count();
        $completedReels = SocialReel::where('status', 'COMPLETED')->count();
        $pendingReels = SocialReel::whereIn('status', ['DRAFT', 'AWAITING_APPROVAL', 'APPROVED'])->count();

        // Publishing Queue
        $queueScheduled = SocialPublishingQueue::where('status', 'SCHEDULED')->count();
        $queuePublished = SocialPublishingQueue::where('status', 'PUBLISHED')->count();

        // Connected Accounts
        $connectedAccounts = SocialPublishingAccount::where('is_connected', true)->count();

        // Recent items
        $recentMedia = SocialMediaAsset::with('supplier')
            ->latest('id')
            ->take(8)
            ->get();

        $recentProducts = SocialProduct::with(['supplier', 'primaryMedia'])
            ->latest('id')
            ->take(6)
            ->get();

        return [
            'metrics' => [
                'total_media' => $totalMedia,
                'unprocessed_media' => $unprocessedMedia,
                'in_product_media' => $inProductMedia,
                'storage_used_formatted' => $this->formatBytes($totalStorageBytes),
                'total_products' => $totalProducts,
                'available_products' => $availableProducts,
                'new_products' => $newProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'promoted_to_web_count' => $promotedToWebCount,
                'active_suppliers' => $activeSuppliers,
                'total_reels' => $totalReels,
                'completed_reels' => $completedReels,
                'pending_reels' => $pendingReels,
                'queue_scheduled' => $queueScheduled,
                'queue_published' => $queuePublished,
                'connected_accounts' => $connectedAccounts,
            ],
            'recent_media' => $recentMedia,
            'recent_products' => $recentProducts,
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
