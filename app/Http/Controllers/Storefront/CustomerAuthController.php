<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Customer\CustomerAuthService;
use App\Support\Auth\CustomerSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

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
     * Display the customer sign-up / registration page.
     */
    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect($this->safeNext($request->query('next')));
        }

        return view('store.auth.register', [
            'next' => $this->safeNext($request->query('next')),
        ]);
    }

    /**
     * Handle Customer Registration / Sign-up request.
     */
    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $name = trim((string) $validated['name']);
        $rawPhone = !empty($validated['phone']) ? preg_replace('/\D/', '', (string) $validated['phone']) : null;
        $normalizedPhone = $rawPhone ? ('+91' . substr($rawPhone, -10)) : null;

        $next = $this->safeNext($request->input('next'));

        // Check if customer already exists (e.g. from previous guest checkout or signup)
        $existing = Customer::where('email', $email)->first();
        if (!$existing && $normalizedPhone) {
            $existing = Customer::where('phone', $normalizedPhone)->first();
        }

        if ($existing) {
            // Count past orders
            $orderCount = Order::where('customer_id', $existing->id)
                ->orWhere('customer_email', $existing->email)
                ->count();

            // Send OTP login code to their registered email
            $targetEmail = $existing->email;
            $result = $this->authService->requestCode($targetEmail, $request->ip());

            $notice = $orderCount > 0
                ? "Your account is already available from your previous order! We have sent a 6-digit sign-in code to {$targetEmail} so you can log in instantly."
                : "An account with {$targetEmail} is already registered. We have sent a 6-digit sign-in code to your email.";

            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::success([
                    'already_exists' => true,
                    'message' => $notice,
                    'resendAfterSeconds' => $result['resendAfterSeconds'] ?? 60,
                ]);
            }

            return redirect()->route('storefront.account.verify', [
                'email' => $targetEmail,
                'next' => $next,
            ])->with('notice', $notice);
        }

        // New customer: create account and send verification code
        $customer = Customer::create([
            'name' => $name,
            'email' => $email,
            'phone' => $normalizedPhone,
            'status' => CustomerStatus::ACTIVE,
            'email_verified_at' => null,
        ]);

        // Reconcile past orders if any matched this email
        try {
            Order::where('customer_email', $email)
                ->whereNull('customer_id')
                ->update(['customer_id' => $customer->id]);
        } catch (\Throwable) {}

        $result = $this->authService->requestCode($email, $request->ip());

        $welcomeNotice = "Welcome to Dhansree! We have sent a 6-digit verification code to {$email} to verify and activate your account.";

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success([
                'already_exists' => false,
                'message' => $welcomeNotice,
                'resendAfterSeconds' => $result['resendAfterSeconds'] ?? 60,
            ]);
        }

        return redirect()->route('storefront.account.verify', [
            'email' => $email,
            'next' => $next,
        ])->with('notice', $welcomeNotice);
    }

    /**
     * Handle OTP generation request.
     */
    public function sendOtp(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:254',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $result = $this->authService->requestCode($email, $request->ip());

        $customer = Customer::where('email', $email)->first();
        $orderCount = Order::where('customer_email', $email)->count();

        if ($customer && $orderCount > 0) {
            $notice = "Welcome back! Your account from your previous order is ready. We have sent a 6-digit sign-in code to {$email}.";
        } else {
            $notice = $result['message'];
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success(array_merge($result, ['message' => $notice]));
        }

        return redirect()->route('storefront.account.verify', [
            'email' => $email,
            'next' => $this->safeNext($request->input('next')),
        ])->with('notice', $notice);
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

        // Merge guest wishlist and guest orders into customer account
        try {
            app(\App\Services\Storefront\WishlistService::class)->mergeSessionToCustomer($result['customer']->id);
        } catch (\Throwable) {}

        try {
            Order::whereNull('customer_id')
                ->where('customer_email', strtolower(trim((string) $result['customer']->email)))
                ->update(['customer_id' => $result['customer']->id]);
        } catch (\Throwable) {}

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
     * Redirect customer to Social Auth Provider (Google / Facebook).
     */
    public function redirectToProvider(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }

        $next = $this->safeNext($request->query('next'));
        session(['social_auth_next' => $next]);

        $clientId = config("services.{$provider}.client_id");

        if (empty($clientId) || $clientId === 'your-client-id') {
            $providerTitle = ucfirst($provider);
            return redirect($next ?: route('storefront.account.login'))->with(
                'notice',
                "{$providerTitle} Sign-In is being activated. Please use your email below to sign in instantly with a 6-digit code!"
            );
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Throwable $e) {
            Log::error("Socialite redirect to {$provider} failed: {$e->getMessage()}");
            return redirect($next ?: route('storefront.account.login'))->with(
                'error',
                "Could not connect to {$provider}. Please use email OTP sign-in."
            );
        }
    }

    /**
     * Handle callback from Social Auth Provider.
     */
    public function handleProviderCallback(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }

        $nextUrl = session()->pull('social_auth_next', '/checkout');
        $safeNext = $this->safeNext($nextUrl);

        if ($request->has('error') || $request->has('error_reason')) {
            return redirect($safeNext)->with('error', 'Social sign-in was cancelled or encountered an error.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            $email = strtolower(trim((string) $socialUser->getEmail()));
            $name = trim((string) $socialUser->getName()) ?: substr(explode('@', $email)[0], 0, 100);
            $avatar = (string) $socialUser->getAvatar();
            $providerId = (string) $socialUser->getId();

            if (!$email) {
                return redirect($safeNext)->with(
                    'error',
                    'Unable to retrieve email from ' . ucfirst($provider) . '. Please enter your email to sign in.'
                );
            }

            $customer = Customer::where('email', $email)->first();

            if ($customer) {
                $updates = [];
                if (!$customer->email_verified_at) {
                    $updates['email_verified_at'] = now();
                }
                if ($provider === 'google' && empty($customer->google_id)) {
                    $updates['google_id'] = $providerId;
                }
                if ($provider === 'facebook' && empty($customer->facebook_id)) {
                    $updates['facebook_id'] = $providerId;
                }
                if (empty($customer->avatar) && $avatar) {
                    $updates['avatar'] = $avatar;
                }
                if (!empty($updates)) {
                    $customer->update($updates);
                }
            } else {
                $customer = Customer::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => null,
                    'google_id' => $provider === 'google' ? $providerId : null,
                    'facebook_id' => $provider === 'facebook' ? $providerId : null,
                    'avatar' => $avatar ?: null,
                    'email_verified_at' => now(),
                    'status' => CustomerStatus::ACTIVE,
                ]);
            }

            // Link any older guest orders
            try {
                Order::where('customer_email', $email)
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);
            } catch (\Throwable) {}

            // Merge guest wishlist
            try {
                app(\App\Services\Storefront\WishlistService::class)->mergeSessionToCustomer($customer->id);
            } catch (\Throwable) {}

            $customer->update(['last_login_at' => now()]);

            // Create customer session
            $sessionResult = CustomerSessionManager::createSession(
                $customer,
                $request->ip(),
                $request->userAgent()
            );

            return redirect($safeNext)
                ->withCookie($sessionResult['cookie'])
                ->with('success', "Welcome, {$customer->name}!");
        } catch (\Throwable $e) {
            Log::error("Socialite callback failed for {$provider}: {$e->getMessage()}");
            return redirect($safeNext)->with(
                'error',
                "Could not sign in with {$provider}. Please use email OTP sign-in."
            );
        }
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
