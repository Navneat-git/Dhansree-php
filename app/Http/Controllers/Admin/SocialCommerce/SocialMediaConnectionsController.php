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

        $oauthUrl = "https://www.facebook.com/{$apiVersion}/dialog/oauth?client_id={$appId}&redirect_uri={$redirectUri}&scope={$scopes}&response_type=code";

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

            // Fetch Facebook Pages & connected Instagram accounts
            $accountsResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/accounts", [
                'access_token' => $longToken,
                'fields' => 'id,name,instagram_business_account{id,username,name,profile_picture_url}',
            ]);

            $pages = $accountsResponse->json('data', []);
            $connectedCount = 0;

            foreach ($pages as $page) {
                // Save Facebook Page
                SocialPublishingAccount::updateOrCreate(
                    ['platform' => 'FACEBOOK', 'account_id' => (string) $page['id']],
                    [
                        'account_name' => $page['name'] ?? 'Dhansree Facebook Page',
                        'access_token' => $longToken,
                        'token_expires_at' => now()->addSeconds((int) $expiresIn),
                        'is_connected' => true,
                    ]
                );
                $connectedCount++;

                // If Instagram Business Account is connected
                if (!empty($page['instagram_business_account'])) {
                    $ig = $page['instagram_business_account'];
                    SocialPublishingAccount::updateOrCreate(
                        ['platform' => 'INSTAGRAM', 'account_id' => (string) $ig['id']],
                        [
                            'account_name' => $ig['name'] ?? $ig['username'] ?? 'Dhansree Instagram',
                            'username' => $ig['username'] ?? null,
                            'profile_picture_url' => $ig['profile_picture_url'] ?? null,
                            'access_token' => $longToken,
                            'token_expires_at' => now()->addSeconds((int) $expiresIn),
                            'is_connected' => true,
                        ]
                    );
                    $connectedCount++;
                }
            }

            return redirect()->route('admin.social-commerce.connections.index')
                ->with('success', "Meta accounts successfully connected! ({$connectedCount} accounts updated)");
        } catch (\Throwable $e) {
            Log::error('Meta OAuth callback failure: ' . $e->getMessage());
            return redirect()->route('admin.social-commerce.connections.index')
                ->with('error', 'Connection failed: ' . $e->getMessage());
        }
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
