<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Email\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function __construct(
        protected EmailService $emailService,
    ) {}

    /**
     * Handle newsletter subscription.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'consent' => ['required', 'accepted'],
        ]);

        $email = strtolower(trim($validated['email']));

        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing) {
            if ($existing->status === 'UNSUBSCRIBED') {
                $existing->update([
                    'status' => 'CONFIRMED',
                    'confirmed_at' => now(),
                    'unsubscribed_at' => null,
                ]);

                try {
                    $this->emailService->sendNewsletterWelcome($existing, 'WELCOME50', '₹50 off on orders above ₹799');
                } catch (\Throwable $e) {
                    Log::warning("Could not send resubscribe email: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed to Dhansree! Use promo code WELCOME50 on your first order above ₹799.',
                'code' => 'WELCOME50',
            ]);
        }

        $source = in_array($request->input('source'), ['FOOTER', 'POPUP'], true)
            ? (string) $request->input('source')
            : 'POPUP';

        $subscriber = NewsletterSubscriber::create([
            'email' => $email,
            'status' => 'CONFIRMED',
            'source' => $source,
            'consent_text' => 'I agree to receive updates, offers, and style notes from Dhansree Trends.',
            'consent_at' => now(),
            'unsubscribe_token' => Str::random(32),
            'confirmed_at' => now(),
        ]);

        // Send Welcome Email with Coupon Voucher
        try {
            $this->emailService->sendNewsletterWelcome($subscriber, 'WELCOME50', '₹50 off on orders above ₹799');
        } catch (\Throwable $e) {
            Log::warning("Could not send newsletter welcome email: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Welcome to Dhansree! Here is your exclusive voucher for ₹50 off on orders above ₹799.',
            'code' => 'WELCOME50',
        ]);
    }

    /**
     * One-click unsubscribe from newsletter.
     */
    public function unsubscribe(Request $request): View
    {
        $token = trim((string) $request->query('token', ''));

        if (!empty($token)) {
            $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();
            if ($subscriber && $subscriber->status !== 'UNSUBSCRIBED') {
                $subscriber->update([
                    'status' => 'UNSUBSCRIBED',
                    'unsubscribed_at' => now(),
                ]);
            }
        }

        return view('store.pages.unsubscribed', [
            'store' => \App\Models\StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => 'Unsubscribed | Dhansree',
        ]);
    }
}
