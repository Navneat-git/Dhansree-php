<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorPageView;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCustomerJourneyWebController extends Controller
{
    /**
     * Display the GA4-style customer journey analytics dashboard.
     */
    public function index(Request $request): View
    {
        $period = (string) $request->input('period', '7days');
        $platformFilter = (string) $request->input('platform', 'all');

        $startDate = match ($period) {
            'today' => Carbon::today(),
            'yesterday' => Carbon::yesterday(),
            '30days' => Carbon::now()->subDays(30),
            'all' => Carbon::createFromTimestamp(0),
            default => Carbon::now()->subDays(7), // '7days'
        };

        $endDate = match ($period) {
            'yesterday' => Carbon::yesterday()->endOfDay(),
            default => Carbon::now(),
        };

        // Base query for sessions in period
        $baseQuery = VisitorSession::whereBetween('started_at', [$startDate, $endDate]);

        if ($platformFilter !== 'all' && $platformFilter !== '') {
            $baseQuery->where('platform', $platformFilter);
        }

        // 1. KPI Aggregations
        $totalSessions = (clone $baseQuery)->count();
        $uniqueVisitors = (clone $baseQuery)->distinct('visitor_uuid')->count('visitor_uuid');
        $totalPageViews = (clone $baseQuery)->sum('page_views_count');
        $avgPagesPerSession = $totalSessions > 0 ? round($totalPageViews / $totalSessions, 1) : 0;

        // 2. Acquisition Platform Breakdown
        $platformBreakdown = (clone $baseQuery)
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->orderByDesc('count')
            ->get();

        $topPlatform = $platformBreakdown->first();
        $topPlatformShare = ($totalSessions > 0 && $topPlatform)
            ? round(($topPlatform->count / $totalSessions) * 100, 1)
            : 0;

        // 3. UTM Campaigns breakdown (if any)
        $campaignsBreakdown = (clone $baseQuery)
            ->whereNotNull('utm_campaign')
            ->select('utm_campaign', 'utm_source', DB::raw('count(*) as count'))
            ->groupBy('utm_campaign', 'utm_source')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // 4. Detailed Customer Journeys Feed (Paginated)
        $journeys = (clone $baseQuery)
            ->with(['pageViews'])
            ->latest('started_at')
            ->paginate(25)
            ->withQueryString();

        // List of all platforms for filter dropdown
        $allPlatforms = VisitorSession::distinct()->pluck('platform')->filter()->values();

        return view('admin.analytics.customer-journeys', [
            'journeys' => $journeys,
            'totalSessions' => $totalSessions,
            'uniqueVisitors' => $uniqueVisitors,
            'avgPagesPerSession' => $avgPagesPerSession,
            'topPlatform' => $topPlatform?->platform ?? 'Direct',
            'topPlatformShare' => $topPlatformShare,
            'platformBreakdown' => $platformBreakdown,
            'campaignsBreakdown' => $campaignsBreakdown,
            'allPlatforms' => $allPlatforms,
            'period' => $period,
            'selectedPlatform' => $platformFilter,
        ]);
    }
}
