<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AdminUser;
use App\Services\Admin\AdminAuthService;
use App\Services\Admin\AdminTwoFactorService;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $authService,
        private readonly AdminTwoFactorService $twoFactorService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $prevToken = AdminSessionManager::extractTokenFromRequest($request);
        $outcome = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
            $request,
            $prevToken
        );

        if ($outcome['status'] === 'CODE_REQUIRED') {
            $cookieName = AdminSessionManager::getChallengeCookieName();
            $isSecure = ($cookieName === AdminSessionManager::CHALLENGE_COOKIE_NAME_PROD);

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

            return ApiResponse::success([
                'status' => 'CODE_REQUIRED',
                'expiresAt' => $outcome['expires_at']->toISOString(),
            ])->withCookie($challengeCookie);
        }

        return ApiResponse::success([
            'status' => 'SIGNED_IN',
            'admin' => $outcome['admin']->toProfile(),
        ])->withCookie($outcome['cookie']);
    }

    public function loginCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $challengeToken = AdminSessionManager::extractChallengeTokenFromRequest($request);
        if (!$challengeToken) {
            throw new ApiException(
                code: 'INVALID_CODE',
                message: AdminTwoFactorService::INVALID_CODE_MESSAGE,
                status: 401
            );
        }

        $prevToken = AdminSessionManager::extractTokenFromRequest($request);
        $result = $this->authService->completeLogin(
            $challengeToken,
            $request->input('code'),
            $request,
            $prevToken
        );

        $clearChallenge = cookie()->forget(AdminSessionManager::getChallengeCookieName(), '/', null);

        return ApiResponse::success([
            'status' => 'SIGNED_IN',
            'admin' => $result['admin']->toProfile(),
        ])->withCookie($result['cookie'])->withCookie($clearChallenge);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = AdminSessionManager::extractTokenFromRequest($request);
        $this->authService->logout($token, $request);

        $clearSession = cookie()->forget(AdminSessionManager::getCookieName(), '/', null);
        $clearChallenge = cookie()->forget(AdminSessionManager::getChallengeCookieName(), '/', null);

        return ApiResponse::success(['message' => 'Logged out successfully'])
            ->withCookie($clearSession)
            ->withCookie($clearChallenge);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        return ApiResponse::success($admin->toProfile());
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:10',
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $result = $this->authService->changePassword(
            $admin,
            $request->input('currentPassword'),
            $request->input('newPassword'),
            $request
        );

        return ApiResponse::success($result['admin']->toProfile())
            ->withCookie($result['cookie']);
    }

    public function twoFactorStatus(Request $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        return ApiResponse::success($this->twoFactorService->status($admin));
    }

    public function beginEnrolment(Request $request): JsonResponse
    {
        $request->validate([
            'currentPassword' => 'required|string',
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $result = $this->twoFactorService->beginEnrolment(
            $admin,
            $request->input('currentPassword'),
            $request
        );

        return ApiResponse::success($result);
    }

    public function confirmEnrolment(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $codes = $this->twoFactorService->confirmEnrolment(
            $admin,
            $request->input('code'),
            $request
        );

        return ApiResponse::success(['codes' => $codes]);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'currentPassword' => 'required|string',
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $codes = $this->twoFactorService->regenerateRecoveryCodes(
            $admin,
            $request->input('currentPassword'),
            $request
        );

        return ApiResponse::success(['codes' => $codes]);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'currentPassword' => 'required|string',
            'code' => 'required|string',
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $this->twoFactorService->disable(
            $admin,
            $request->input('currentPassword'),
            $request->input('code'),
            $request
        );

        return ApiResponse::success(['message' => 'Two-factor authentication disabled']);
    }
}
