<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
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
            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed to Dhansree! Use promo code WELCOME50 on your first order above ₹799.',
                'code' => 'WELCOME50',
            ]);
        }

        $source = in_array($request->input('source'), ['FOOTER', 'POPUP'], true)
            ? (string) $request->input('source')
            : 'POPUP';

        NewsletterSubscriber::create([
            'email' => $email,
            'status' => 'CONFIRMED',
            'source' => $source,
            'consent_text' => 'I agree to receive updates, offers, and style notes from Dhansree Trends.',
            'consent_at' => now(),
            'unsubscribe_token' => Str::random(32),
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Welcome to Dhansree! Here is your exclusive voucher for ₹50 off on orders above ₹799.',
            'code' => 'WELCOME50',
        ]);
    }
}
