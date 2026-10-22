<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAuthService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Cookie;

class AdminSessionWebController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $authService
    ) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (auth('admin')->check()) {
            return redirect()->intended('/admin/dashboard');
        }

        return view('admin.auth.login');
    }

    public function submitLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $prevToken = AdminSessionManager::extractTokenFromRequest($request);

        try {
            $outcome = $this->authService->login(
                $request->input('email'),
                $request->input('password'),
                $request,
                $prevToken
            );
        } catch (ApiException $e) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $e->getMessage()]);
        }

        if ($outcome['status'] === 'CODE_REQUIRED') {
            $cookieName = AdminSessionManager::getChallengeCookieName();
            $isSecure = ($cookieName === AdminSessionManager::CHALLENGE_COOKIE_NAME_PROD && $request->isSecure());

            // Always store challenge token in session for reliable web flow across HTTP/HTTPS
            $request->session()->put('admin_2fa_challenge_token', $outcome['challenge_token']);

            $challengeCookie = new Cookie(
                name: $cookieName,
                value: $outcome['challenge_token'],
                expire: $outcome['expires_at']->timestamp,
                path: '/',
                domain: null,
                secure: $isSecure,
                httpOnly: true,
                raw: false,
                sameSite: Cookie::SAMESITE_LAX
            );

            return redirect('/admin/two-factor')
                ->withCookie($challengeCookie)
                ->with('status', 'Please enter the 6-digit code from your authenticator app or a recovery code.');
        }

        $request->session()->put('admin_session_token', $outcome['token']);

        return redirect()->intended('/admin/dashboard')
            ->withCookie($outcome['cookie'])
            ->with('success', 'Welcome back, ' . $outcome['admin']->name . '!');
    }

    public function showTwoFactor(Request $request): View|RedirectResponse
    {
        $challengeToken = AdminSessionManager::extractChallengeTokenFromRequest($request);
        if (!$challengeToken) {
            return redirect('/admin/login')->withErrors(['email' => 'Your sign-in challenge has expired. Please sign in again.']);
        }

        return view('admin.auth.two-factor');
    }

    public function submitTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $challengeToken = AdminSessionManager::extractChallengeTokenFromRequest($request);
        if (!$challengeToken) {
            return redirect('/admin/login')->withErrors(['email' => 'Your sign-in challenge has expired. Please sign in again.']);
        }

        $prevToken = AdminSessionManager::extractTokenFromRequest($request);

        try {
            $result = $this->authService->completeLogin(
                $challengeToken,
                $request->input('code'),
                $request,
                $prevToken
            );
        } catch (ApiException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $request->session()->forget('admin_2fa_challenge_token');
        $request->session()->put('admin_session_token', $result['token']);

        $clearChallenge = cookie()->forget(AdminSessionManager::getChallengeCookieName(), '/', null);

        return redirect()->intended('/admin/dashboard')
            ->withCookie($result['cookie'])
            ->withCookie($clearChallenge)
            ->with('success', 'Two-step verification successful. Welcome back!');
    }

    public function showChangePassword(Request $request): View
    {
        return view('admin.auth.change-password');
    }

    public function submitChangePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:10|confirmed',
        ]);

        $admin = auth('admin')->user();
        if (!$admin) {
            return redirect('/admin/login');
        }

        try {
            $result = $this->authService->changePassword(
                $admin,
                $request->input('currentPassword'),
                $request->input('newPassword'),
                $request
            );
        } catch (ApiException $e) {
            return back()->withErrors(['currentPassword' => $e->getMessage()]);
        }

        $request->session()->put('admin_session_token', $result['token']);

        return redirect()->intended('/admin/dashboard')
            ->withCookie($result['cookie'])
            ->with('success', 'Password updated successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = AdminSessionManager::extractTokenFromRequest($request);
        $this->authService->logout($token, $request);

        $request->session()->forget(['admin_session_token', 'admin_2fa_challenge_token']);

        $clearSession = cookie()->forget(AdminSessionManager::getCookieName(), '/', null);
        $clearChallenge = cookie()->forget(AdminSessionManager::getChallengeCookieName(), '/', null);

        return redirect('/admin/login')
            ->withCookie($clearSession)
            ->withCookie($clearChallenge)
            ->with('status', 'You have been signed out securely.');
    }
}
