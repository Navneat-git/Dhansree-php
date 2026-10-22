<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Services\Customer\CustomerAuthService;
use App\Support\Auth\CustomerSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService
    ) {}

    /**
     * Display the customer passwordless OTP sign-in page.
     */
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect($this->safeNext($request->query('next')));
        }

        return view('store.auth.login', [
            'next' => $this->safeNext($request->query('next')),
        ]);
    }

    /**
     * Handle OTP generation request.
     */
    public function sendOtp(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:254',
        ]);

        $email = (string) $request->input('email');
        $result = $this->authService->requestCode($email, $request->ip());

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success($result);
        }

        return redirect()->route('storefront.account.verify', [
            'email' => $email,
            'next' => $this->safeNext($request->input('next')),
        ])->with('notice', $result['message']);
    }

    /**
     * Display the OTP verification page.
     */
    public function showVerify(Request $request): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect($this->safeNext($request->query('next')));
        }

        $email = (string) $request->query('email', '');
        if (!$email) {
            return redirect()->route('storefront.account.login');
        }

        return view('store.auth.verify-otp', [
            'email' => $email,
            'next' => $this->safeNext($request->query('next')),
        ]);
    }

    /**
     * Handle OTP code verification.
     */
    public function verifyOtp(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:254',
            'code' => 'required|string|size:6',
        ]);

        $email = (string) $request->input('email');
        $code = (string) $request->input('code');

        $result = $this->authService->verifyCode(
            $email,
            $code,
            $request->ip(),
            $request->userAgent()
        );

        $nextUrl = $this->safeNext($request->input('next'));

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()
                ->json([
                    'success' => true,
                    'data' => [
                        'customer' => [
                            'id' => $result['customer']->id,
                            'name' => $result['customer']->name,
                            'email' => $result['customer']->email,
                            'phone' => $result['customer']->phone,
                        ],
                        'token' => $result['token'],
                    ],
                ])
                ->withCookie($result['cookie']);
        }

        return redirect($nextUrl)->withCookie($result['cookie']);
    }

    /**
     * Sign out customer.
     */
    public function logout(Request $request): RedirectResponse
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();
        $token = CustomerSessionManager::extractTokenFromRequest($request);

        if ($customer && $token) {
            $this->authService->signOut($token, $customer);
        }

        Auth::guard('customer')->logout();

        $forgetCookie = CustomerSessionManager::makeForgetCookie();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true])->withCookie($forgetCookie);
        }

        return redirect('/')->withCookie($forgetCookie);
    }

    /**
     * Ensure return URL is safe within our application.
     */
    private function safeNext(?string $next): string
    {
        if (!$next) {
            return '/account';
        }

        // Allow /account* or /checkout* relative paths
        if ((str_starts_with($next, '/account') || str_starts_with($next, '/checkout')) && !str_starts_with($next, '//')) {
            return $next;
        }

        return '/account';
    }
}
