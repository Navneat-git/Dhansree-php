<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialPublishingAccount;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SocialMediaConnectionsController extends Controller
{
    /**
     * Display connected Meta accounts (Instagram & Facebook), status, and configuration.
     */
    public function index(): View
    {
        $accounts = SocialPublishingAccount::all();

        $instagramAccount = $accounts->where('platform', 'INSTAGRAM')->first();
        $facebookAccount = $accounts->where('platform', 'FACEBOOK')->first();

        $metaAppId = SocialSetting::get('meta_app_id', '');
        $metaAppSecret = SocialSetting::get('meta_app_secret', '');
        $isConfigured = !empty($metaAppId) && !empty($metaAppSecret);

        $redirectUri = route('admin.social-commerce.connections.callback');

        return view('admin.social-commerce.connections.index', [
            'instagramAccount' => $instagramAccount,
            'facebookAccount' => $facebookAccount,
            'metaAppId' => $metaAppId,
            'metaAppSecret' => $metaAppSecret,
            'isConfigured' => $isConfigured,
            'redirectUri' => $redirectUri,
        ]);
    }

    /**
     * Start Meta OAuth flow.
     */
    public function connect(Request $request): RedirectResponse
    {
        $appId = SocialSetting::get('meta_app_id');
        if (empty($appId)) {
            return redirect()->back()->with('error', 'Please configure your Meta App ID in Settings before connecting.');
        }

        $redirectUri = urlencode(route('admin.social-commerce.connections.callback'));
        $scopes = urlencode('instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement,pages_manage_posts');
        $apiVersion = SocialSetting::get('meta_api_version', 'v20.0');

        // auth_type=rerequest forces Facebook to re-prompt the user to select their Pages and Instagram accounts
        $oauthUrl = "https://www.facebook.com/{$apiVersion}/dialog/oauth?client_id={$appId}&redirect_uri={$redirectUri}&scope={$scopes}&response_type=code&auth_type=rerequest";

        return redirect()->away($oauthUrl);
    }

    /**
     * Handle Meta OAuth Callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');
        $error = $request->query('error_message') ?: $request->query('error_description');

        if ($error) {
            return redirect()->route('admin.social-commerce.connections.index')
                ->with('error', "Meta OAuth Error: {$error}");
        }

        if (empty($code)) {
            return redirect()->route('admin.social-commerce.connections.index')
                ->with('error', 'Authorization cancelled or invalid.');
        }

        $appId = SocialSetting::get('meta_app_id');
        $appSecret = SocialSetting::get('meta_app_secret');
        $redirectUri = route('admin.social-commerce.connections.callback');
        $apiVersion = SocialSetting::get('meta_api_version', 'v20.0');

        try {
            // Exchange code for short-lived access token
            $response = Http::get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException($response->json('error.message', 'Token exchange failed'));
            }

            $shortToken = $response->json('access_token');

            // Exchange for long-lived 60-day token
            $longTokenResponse = Http::get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]);

            $longToken = $longTokenResponse->successful() ? $longTokenResponse->json('access_token') : $shortToken;
            $expiresIn = $longTokenResponse->json('expires_in', 5184000); // default 60 days

            // Get logged-in Meta user info for diagnostics
            $userProfile = Http::get("https://graph.facebook.com/{$apiVersion}/me", [
                'access_token' => $longToken,
                'fields' => 'id,name',
            ])->json();
            $userName = $userProfile['name'] ?? 'User';

            // Fetch Facebook Pages & connected Instagram accounts
            $accountsResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/accounts", [
                'access_token' => $longToken,
                'fields' => 'id,name,access_token,instagram_business_account{id,username,name,profile_picture_url}',
            ]);

            $pages = $accountsResponse->json('data', []);

            // If /me/accounts is empty, check Business Portfolios
            if (empty($pages)) {
                $bizResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/businesses", [
                    'access_token' => $longToken,
                    'fields' => 'id,name',
                ]);
                foreach ($bizResponse->json('data', []) as $biz) {
                    $owned = Http::get("https://graph.facebook.com/{$apiVersion}/{$biz['id']}/owned_pages", [
                        'access_token' => $longToken,
                        'fields' => 'id,name,access_token,instagram_business_account{id,username,name,profile_picture_url}',
                    ]);
                    if (!empty($owned->json('data'))) {
                        $pages = array_merge($pages, $owned->json('data'));
                    }
                    $client = Http::get("https://graph.facebook.com/{$apiVersion}/{$biz['id']}/client_pages", [
                        'access_token' => $longToken,
                        'fields' => 'id,name,access_token,instagram_business_account{id,username,name,profile_picture_url}',
                    ]);
                    if (!empty($client->json('data'))) {
                        $pages = array_merge($pages, $client->json('data'));
                    }
                }
            }

            Log::info("Meta OAuth authorized for user {$userName}", [
                'user' => $userProfile,
                'pages_count' => count($pages),
                'pages_raw' => $pages,
            ]);

            $connectedCount = 0;
            $connectedNames = [];

            foreach ($pages as $page) {
                $pageAccessToken = $page['access_token'] ?? $longToken;

                // Save Facebook Page
                SocialPublishingAccount::updateOrCreate(
                    ['platform' => 'FACEBOOK', 'account_id' => (string) $page['id']],
                    [
                        'account_name' => $page['name'] ?? 'Dhansree Facebook Page',
                        'access_token' => $pageAccessToken,
                        'token_expires_at' => now()->addSeconds((int) $expiresIn),
                        'is_connected' => true,
                    ]
                );
                $connectedCount++;
                $connectedNames[] = "Facebook: " . ($page['name'] ?? $page['id']);

                // If nested instagram_business_account was not provided, look it up via Page endpoint
                $ig = $page['instagram_business_account'] ?? null;
                if (empty($ig)) {
                    $igLookup = Http::get("https://graph.facebook.com/{$apiVersion}/{$page['id']}", [
                        'access_token' => $pageAccessToken,
                        'fields' => 'instagram_business_account{id,username,name,profile_picture_url}',
                    ]);
                    $ig = $igLookup->json('instagram_business_account');
                }

                // If Instagram Business Account is linked to this Page
                if (!empty($ig)) {
                    SocialPublishingAccount::updateOrCreate(
                        ['platform' => 'INSTAGRAM', 'account_id' => (string) $ig['id']],
                        [
                            'account_name' => $ig['name'] ?? $ig['username'] ?? 'Dhansree Instagram',
                            'username' => $ig['username'] ?? null,
                            'profile_picture_url' => $ig['profile_picture_url'] ?? null,
                            'access_token' => $pageAccessToken,
                            'token_expires_at' => now()->addSeconds((int) $expiresIn),
                            'is_connected' => true,
                        ]
                    );
                    $connectedCount++;
                    $connectedNames[] = "Instagram: @" . ($ig['username'] ?? $ig['id']);
                }
            }

            if ($connectedCount === 0) {
                // Fetch granted permissions for logging & diagnostics
                $permsRes = Http::get("https://graph.facebook.com/{$apiVersion}/me/permissions", [
                    'access_token' => $longToken,
                ]);
                $permsData = $permsRes->json('data', []);
                $grantedPerms = collect($permsData)->where('status', 'granted')->pluck('permission')->all();
                $declinedPerms = collect($permsData)->where('status', 'declined')->pluck('permission')->all();

                Log::warning("Meta OAuth: 0 pages returned for user {$userName}", [
                    'granted_permissions' => $grantedPerms,
                    'declined_permissions' => $declinedPerms,
                ]);

                // Automatically revoke the cached grant from Facebook so next connect attempts force a clean consent prompt
                try {
                    Http::delete("https://graph.facebook.com/{$apiVersion}/me/permissions", [
                        'access_token' => $longToken,
                    ]);
                    Log::info("Revoked cached Meta permissions for {$userName} to reset selection dialog.");
                } catch (\Throwable $revEx) {
                    Log::warning("Could not revoke permissions: " . $revEx->getMessage());
                }

                $errMsg = "Connected to Facebook as '{$userName}', but 0 Pages or Instagram accounts were selected or found. We have automatically reset the connection grant so you can re-select. Please click 'Connect Instagram Account' again. In the Facebook popup, make sure to check the box next to your Dhansree Page and Instagram account. (Alternatively, you can connect directly using a Page Access Token below).";

                return redirect()->route('admin.social-commerce.connections.index')
                    ->with('error', $errMsg);
            }

            $namesStr = implode(', ', $connectedNames);
            return redirect()->route('admin.social-commerce.connections.index')
                ->with('success', "Meta accounts successfully connected! ({$namesStr})");
        } catch (\Throwable $e) {
            Log::error('Meta OAuth callback failure: ' . $e->getMessage());
            return redirect()->route('admin.social-commerce.connections.index')
                ->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Connect Facebook Page and Instagram Account manually via Page Access Token.
     */
    public function connectManual(Request $request): RedirectResponse
    {
        $request->validate([
            'page_id' => 'required|string',
            'access_token' => 'required|string',
        ]);

        $pageId = trim((string) $request->input('page_id'));
        $token = trim((string) $request->input('access_token'));
        $apiVersion = SocialSetting::get('meta_api_version', 'v20.0');

        try {
            $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$pageId}", [
                'access_token' => $token,
                'fields' => 'id,name,instagram_business_account{id,username,name,profile_picture_url}',
            ]);

            if (!$response->successful()) {
                $err = $response->json('error.message', 'Invalid Page ID or Access Token.');
                return redirect()->back()->with('error', "Meta API verification failed: {$err}");
            }

            $pageData = $response->json();
            $pageName = $pageData['name'] ?? 'Dhansree Facebook Page';

            // Save Facebook Page
            SocialPublishingAccount::updateOrCreate(
                ['platform' => 'FACEBOOK', 'account_id' => (string) $pageId],
                [
                    'account_name' => $pageName,
                    'access_token' => $token,
                    'token_expires_at' => null,
                    'is_connected' => true,
                ]
            );

            $connected = ["Facebook: {$pageName}"];

            // Check linked Instagram account
            $ig = $pageData['instagram_business_account'] ?? null;
            if (!empty($ig)) {
                SocialPublishingAccount::updateOrCreate(
                    ['platform' => 'INSTAGRAM', 'account_id' => (string) $ig['id']],
                    [
                        'account_name' => $ig['name'] ?? $ig['username'] ?? 'Dhansree Instagram',
                        'username' => $ig['username'] ?? null,
                        'profile_picture_url' => $ig['profile_picture_url'] ?? null,
                        'access_token' => $token,
                        'token_expires_at' => null,
                        'is_connected' => true,
                    ]
                );
                $connected[] = "Instagram: @" . ($ig['username'] ?? $ig['id']);
            }

            $msg = "Successfully connected via token! (" . implode(', ', $connected) . ")";
            if (empty($ig)) {
                $msg .= " (Note: No linked Instagram Business Account was found on this Facebook Page. To publish to Instagram, link your Instagram Professional account to this Facebook Page in Instagram app settings).";
            }

            return redirect()->route('admin.social-commerce.connections.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Manual connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear / revoke Meta OAuth session so the next login prompt forces fresh page selection.
     */
    public function resetAuth(): RedirectResponse
    {
        $accounts = SocialPublishingAccount::whereIn('platform', ['INSTAGRAM', 'FACEBOOK'])->get();
        foreach ($accounts as $acc) {
            if ($acc->access_token) {
                try {
                    $apiVersion = SocialSetting::get('meta_api_version', 'v20.0');
                    Http::delete("https://graph.facebook.com/{$apiVersion}/me/permissions", [
                        'access_token' => $acc->access_token,
                    ]);
                } catch (\Throwable $e) {}
            }
            $acc->update([
                'is_connected' => false,
                'access_token' => null,
                'token_expires_at' => null,
            ]);
        }

        return redirect()->route('admin.social-commerce.connections.index')
            ->with('success', "Meta connection reset. You can also visit https://www.facebook.com/settings?tab=applications and remove the app to ensure Facebook completely prompts for your Pages and Instagram accounts.");
    }

    /**
     * Disconnect an account.
     */
    public function disconnect(SocialPublishingAccount $account): RedirectResponse
    {
        $account->update([
            'is_connected' => false,
            'access_token' => null,
            'token_expires_at' => null,
        ]);

        return redirect()->back()->with('success', "Disconnected {$account->platform} account.");
    }
}
