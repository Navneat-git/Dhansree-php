<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\VisitorPageView;
use App\Models\VisitorSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackCustomerJourney
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests on the public storefront
        if (!$request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return $response;
        }

        $path = trim($request->path(), '/');

        // Ignore admin, internal API, health checks, webhook, and asset paths
        if ($this->shouldIgnorePath($path) || $request->ajax() || $request->wantsJson()) {
            return $response;
        }

        try {
            $visitorUuid = $request->cookie('dhansree_visitor_id');
            $setCookie = false;

            if (empty($visitorUuid) || !Str::isUuid($visitorUuid)) {
                $visitorUuid = (string) Str::uuid();
                $setCookie = true;
            }

            $now = Carbon::now();
            $sessionId = (string) $request->session()->get('customer_journey_session_id');

            // Find recent active session within 30 minutes
            $session = null;
            if (!empty($sessionId)) {
                $session = VisitorSession::where('session_id', $sessionId)
                    ->where('last_activity_at', '>=', $now->copy()->subMinutes(30))
                    ->first();
            }

            if (!$session) {
                // Check if visitor has any recent active session
                $session = VisitorSession::where('visitor_uuid', $visitorUuid)
                    ->where('last_activity_at', '>=', $now->copy()->subMinutes(30))
                    ->latest('last_activity_at')
                    ->first();
            }

            if (!$session) {
                // Start a brand new exploration session
                $sessionId = Str::random(40);
                $request->session()->put('customer_journey_session_id', $sessionId);

                $platformInfo = $this->detectAcquisitionPlatform($request);
                $deviceInfo = $this->detectDevice($request->userAgent() ?? '');

                $session = VisitorSession::create([
                    'session_id' => $sessionId,
                    'visitor_uuid' => $visitorUuid,
                    'platform' => $platformInfo['platform'],
                    'referrer' => $request->headers->get('referer'),
                    'referrer_domain' => $platformInfo['referrer_domain'],
                    'landing_page' => Str::limit($request->fullUrl(), 500),
                    'landing_path' => Str::limit('/' . $path, 255),
                    'utm_source' => $request->input('utm_source'),
                    'utm_medium' => $request->input('utm_medium'),
                    'utm_campaign' => $request->input('utm_campaign'),
                    'utm_term' => $request->input('utm_term'),
                    'utm_content' => $request->input('utm_content'),
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit($request->userAgent() ?? '', 500),
                    'device_type' => $deviceInfo['device_type'],
                    'browser' => $deviceInfo['browser'],
                    'os' => $deviceInfo['os'],
                    'page_views_count' => 1,
                    'started_at' => $now,
                    'last_activity_at' => $now,
                ]);

                $stepNumber = 1;
            } else {
                $sessionId = $session->session_id;
                $request->session()->put('customer_journey_session_id', $sessionId);

                // Update previous step duration if present
                $lastView = VisitorPageView::where('session_id', $sessionId)
                    ->latest('step_number')
                    ->first();

                if ($lastView && $lastView->created_at) {
                    $duration = max(1, $now->diffInSeconds($lastView->created_at));
                    if ($duration < 1800) {
                        $lastView->update(['duration_seconds' => $duration]);
                    }
                }

                $stepNumber = ($session->page_views_count ?? 0) + 1;
                $session->increment('page_views_count');
                $session->update(['last_activity_at' => $now]);
            }

            // Record this page view step
            VisitorPageView::create([
                'session_id' => $sessionId,
                'visitor_uuid' => $visitorUuid,
                'step_number' => $stepNumber,
                'url' => Str::limit($request->fullUrl(), 500),
                'path' => Str::limit('/' . $path, 255),
                'page_title' => $this->resolvePageTitle($path),
                'duration_seconds' => null,
                'created_at' => $now,
            ]);

            if ($setCookie) {
                // Set visitor cookie for 1 year
                $response->headers->setCookie(
                    Cookie::forever('dhansree_visitor_id', $visitorUuid, '/', null, false, false, false, 'Lax')
                );
            }
        } catch (Throwable) {
            // Fail silently so customer browsing is never interrupted
        }

        return $response;
    }

    /**
     * Check if path should be ignored from journey tracking.
     */
    private function shouldIgnorePath(string $path): bool
    {
        $prefixes = [
            'admin',
            'api',
            '_',
            'up',
            'media',
            'storage',
            'favicon',
            'manifest',
            'robots',
            'sitemap',
            'css',
            'js',
            'livewire',
        ];

        foreach ($prefixes as $p) {
            if ($path === $p || str_starts_with($path, $p . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect acquisition platform from referrer and UTM parameters.
     *
     * @return array{platform: string, referrer_domain: ?string}
     */
    private function detectAcquisitionPlatform(Request $request): array
    {
        $utmSource = strtolower(trim((string) $request->input('utm_source', '')));
        $referer = (string) $request->headers->get('referer', '');
        $domain = null;

        if (!empty($referer)) {
            $parsed = parse_url($referer);
            $domain = strtolower($parsed['host'] ?? '');
        }

        // 1. Direct UTM match
        if (str_contains($utmSource, 'instagram') || $utmSource === 'ig') {
            return ['platform' => 'Instagram', 'referrer_domain' => $domain];
        }
        if (str_contains($utmSource, 'facebook') || $utmSource === 'fb') {
            return ['platform' => 'Facebook', 'referrer_domain' => $domain];
        }
        if (str_contains($utmSource, 'whatsapp') || $utmSource === 'wa') {
            return ['platform' => 'WhatsApp', 'referrer_domain' => $domain];
        }
        if (str_contains($utmSource, 'google') || str_contains($utmSource, 'adwords') || str_contains($utmSource, 'gads')) {
            return ['platform' => 'Google', 'referrer_domain' => $domain];
        }
        if (str_contains($utmSource, 'youtube')) {
            return ['platform' => 'YouTube', 'referrer_domain' => $domain];
        }
        if (str_contains($utmSource, 'pinterest')) {
            return ['platform' => 'Pinterest', 'referrer_domain' => $domain];
        }
        if (str_contains($utmSource, 'twitter') || $utmSource === 'x') {
            return ['platform' => 'Twitter / X', 'referrer_domain' => $domain];
        }

        // 2. Referrer Domain detection
        if (!empty($domain)) {
            if (str_contains($domain, 'instagram.com')) {
                return ['platform' => 'Instagram', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 'facebook.com') || str_contains($domain, 'fb.me')) {
                return ['platform' => 'Facebook', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 'whatsapp.com')) {
                return ['platform' => 'WhatsApp', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 'google.')) {
                return ['platform' => 'Google', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 'youtube.com') || str_contains($domain, 'youtu.be')) {
                return ['platform' => 'YouTube', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 'pinterest.')) {
                return ['platform' => 'Pinterest', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 't.co') || str_contains($domain, 'twitter.com') || str_contains($domain, 'x.com')) {
                return ['platform' => 'Twitter / X', 'referrer_domain' => $domain];
            }
            if (str_contains($domain, 'bing.com')) {
                return ['platform' => 'Bing Search', 'referrer_domain' => $domain];
            }

            // Exclude own domain referral
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($appHost && str_contains($domain, $appHost)) {
                return ['platform' => 'Direct', 'referrer_domain' => null];
            }

            return ['platform' => 'Referral', 'referrer_domain' => $domain];
        }

        if (!empty($utmSource)) {
            return ['platform' => 'Campaign (' . ucfirst($utmSource) . ')', 'referrer_domain' => null];
        }

        return ['platform' => 'Direct', 'referrer_domain' => null];
    }

    /**
     * Detect device type, browser, and OS.
     *
     * @return array{device_type: string, browser: string, os: string}
     */
    private function detectDevice(string $ua): array
    {
        $deviceType = 'desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|kindle)/i', $ua)) {
            $deviceType = 'mobile';
        }

        $browser = 'Other';
        if (str_contains($ua, 'Edge') || str_contains($ua, 'Edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg/')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        }

        $os = 'Other';
        if (str_contains($ua, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $os = 'iOS';
        } elseif (str_contains($ua, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($ua, 'Linux')) {
            $os = 'Linux';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * Resolve a friendly label for the page path.
     */
    private function resolvePageTitle(string $path): string
    {
        if ($path === '' || $path === '/') {
            return 'Home Page';
        }

        if (str_starts_with($path, 'product/')) {
            $slug = substr($path, 8);
            return 'Product: ' . Str::title(str_replace('-', ' ', $slug));
        }

        if ($path === 'cart') {
            return 'Shopping Bag';
        }

        if ($path === 'checkout') {
            return 'Checkout';
        }

        if ($path === 'wishlist') {
            return 'Wishlist';
        }

        if ($path === 'smart-living') {
            return 'Smart Living Hub';
        }

        if ($path === 'search') {
            return 'Search Catalog';
        }

        return 'Category: ' . Str::title(str_replace('-', ' ', $path));
    }
}
