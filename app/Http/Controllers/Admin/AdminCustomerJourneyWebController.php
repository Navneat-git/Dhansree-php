<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrackedVisitor;
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
        $visitorTypeFilter = (string) $request->input('visitor_type', 'all'); // 'all', 'new', 'repeat'
        $search = trim((string) $request->input('search', ''));

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

        if ($visitorTypeFilter === 'new') {
            $baseQuery->where('is_repeat_visitor', false);
        } elseif ($visitorTypeFilter === 'repeat') {
            $baseQuery->where('is_repeat_visitor', true);
        }

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('utm_campaign', 'like', "%{$search}%")
                  ->orWhere('landing_path', 'like', "%{$search}%");
            });
        }

        // 1. KPI Aggregations
        $totalSessions = (clone $baseQuery)->count();
        $uniqueCustomers = (clone $baseQuery)->whereNotNull('customer_code')->distinct('customer_code')->count('customer_code');
        if ($uniqueCustomers === 0) {
            $uniqueCustomers = (clone $baseQuery)->distinct('visitor_uuid')->count('visitor_uuid');
        }

        $newVisitorsCount = (clone $baseQuery)->where('is_repeat_visitor', false)->count();
        $repeatVisitorsCount = (clone $baseQuery)->where('is_repeat_visitor', true)->count();
        $repeatRate = $totalSessions > 0 ? round(($repeatVisitorsCount / $totalSessions) * 100, 1) : 0;

        $totalPageViews = (clone $baseQuery)->sum('page_views_count');
        $avgPagesPerSession = $totalSessions > 0 ? round($totalPageViews / $totalSessions, 1) : 0;

        // 2. Acquisition Platform Breakdown (GA4 channels)
        $platformBreakdown = (clone $baseQuery)
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->orderByDesc('count')
            ->get();

        $topPlatform = $platformBreakdown->first();
        $topPlatformShare = ($totalSessions > 0 && $topPlatform)
            ? round(($topPlatform->count / $totalSessions) * 100, 1)
            : 0;

        // 3. Device Breakdown (Mobile vs Desktop vs Tablet)
        $deviceBreakdown = (clone $baseQuery)
            ->select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get();

        // 4. Locations Breakdown (City & Country) - Expanded to top 10
        $locationsBreakdown = (clone $baseQuery)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->select('city', 'region', 'country', DB::raw('count(*) as count'))
            ->groupBy('city', 'region', 'country')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        // 4.1 Country Breakdown
        $countriesBreakdown = (clone $baseQuery)
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->select('country', DB::raw('count(*) as count'))
            ->groupBy('country')
            ->orderByDesc('count')
            ->take(8)
            ->get();

        // 5. UTM Campaigns breakdown (if any)
        $campaignsBreakdown = (clone $baseQuery)
            ->whereNotNull('utm_campaign')
            ->select('utm_campaign', 'utm_source', DB::raw('count(*) as count'))
            ->groupBy('utm_campaign', 'utm_source')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // 6. Detailed Customer Journeys Feed (Paginated)
        $journeys = (clone $baseQuery)
            ->with(['pageViews', 'trackedVisitor'])
            ->latest('started_at')
            ->paginate(25)
            ->withQueryString();

        // List of all platforms for filter dropdown
        $allPlatforms = VisitorSession::distinct()->pluck('platform')->filter()->values();

        return view('admin.analytics.customer-journeys', [
            'journeys' => $journeys,
            'totalSessions' => $totalSessions,
            'uniqueCustomers' => $uniqueCustomers,
            'newVisitorsCount' => $newVisitorsCount,
            'repeatVisitorsCount' => $repeatVisitorsCount,
            'repeatRate' => $repeatRate,
            'avgPagesPerSession' => $avgPagesPerSession,
            'topPlatform' => $topPlatform?->platform ?? 'Direct',
            'topPlatformShare' => $topPlatformShare,
            'platformBreakdown' => $platformBreakdown,
            'deviceBreakdown' => $deviceBreakdown,
            'locationsBreakdown' => $locationsBreakdown,
            'countriesBreakdown' => $countriesBreakdown,
            'campaignsBreakdown' => $campaignsBreakdown,
            'allPlatforms' => $allPlatforms,
            'period' => $period,
            'selectedPlatform' => $platformFilter,
            'selectedVisitorType' => $visitorTypeFilter,
            'search' => $search,
        ]);
    }
}
