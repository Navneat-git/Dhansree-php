<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialPublishingLog;
use App\Models\SocialPublishingQueue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialPublishingHistoryController extends Controller
{
    /**
     * View history of published social posts, reels, and individual platform logs.
     */
    public function index(Request $request): View
    {
        $query = SocialPublishingLog::with(['queueItem.reel', 'queueItem.mediaAsset'])
            ->latest('published_at');

        if ($request->filled('platform')) {
            $query->where('platform', $request->input('platform'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $logs = $query->paginate(25)->withQueryString();

        $stats = [
            'total_published' => SocialPublishingLog::where('status', 'SUCCESS')->count(),
            'instagram_count' => SocialPublishingLog::where('platform', 'INSTAGRAM')->where('status', 'SUCCESS')->count(),
            'facebook_count' => SocialPublishingLog::where('platform', 'FACEBOOK')->where('status', 'SUCCESS')->count(),
            'failed_count' => SocialPublishingLog::where('status', 'FAILED')->count(),
        ];

        return view('admin.social-commerce.history.index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->all(),
        ]);
    }
}
