<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Enums\AuditActorType;
use App\Enums\CustomerStatus;
use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\CustomerLoginChallenge;
use App\Models\EmailMessage;
use App\Models\Order;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\CustomerSessionManager;
use App\Support\Security\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerAuthService
{
    public const OTP_CODE_LENGTH = 6;
    public const OTP_MAX_ATTEMPTS = 5;
    public const OTP_TTL_MINUTES = 10;
    public const OTP_RESEND_COOLDOWN_SECONDS = 60;
    public const OTP_REQUESTS_PER_EMAIL_PER_HOUR = 5;
    public const OTP_REQUEST_MESSAGE = 'If that address can receive email, a code is on its way.';
    public const OTP_INVALID_MESSAGE = 'That code is not valid or has expired. Request a new one.';

    public function __construct(
        private readonly PasswordHasher $hasher = new PasswordHasher()
    ) {}

    /**
     * Request a one-time sign-in code.
     *
     * @return array{message: string, resendAfterSeconds: int, code?: string|null}
     */
    public function requestCode(string $email, ?string $ip = null): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Please enter a valid email address.'],
            ]);
        }

        $now = now();
        $existing = CustomerLoginChallenge::where('email', $email)->first();

        if ($existing) {
            $since = (int) $existing->last_sent_at->diffInSeconds($now, false);
            if ($since < self::OTP_RESEND_COOLDOWN_SECONDS) {
                $remaining = max(1, min(self::OTP_RESEND_COOLDOWN_SECONDS, self::OTP_RESEND_COOLDOWN_SECONDS - max(0, $since)));
                return [
                    'message' => self::OTP_REQUEST_MESSAGE,
                    'resendAfterSeconds' => $remaining,
                ];
            }

            $windowOpen = $now->diffInSeconds($existing->window_start) < 3600;
            if ($windowOpen && $existing->send_count >= self::OTP_REQUESTS_PER_EMAIL_PER_HOUR) {
                // Silently refused to prevent user enumeration
                return [
                    'message' => self::OTP_REQUEST_MESSAGE,
                    'resendAfterSeconds' => self::OTP_RESEND_COOLDOWN_SECONDS,
                ];
            }
        }

        $code = sprintf('%06d', random_int(0, 999999));
        $codeHash = $this->hasher->hash($code);

        $windowStart = ($existing && $now->diffInSeconds($existing->window_start) < 3600)
            ? $existing->window_start
            : $now;

        $sendCount = ($existing && $windowStart->equalTo($existing->window_start))
            ? $existing->send_count + 1
            : 1;

        $expiresAt = $now->copy()->addMinutes(self::OTP_TTL_MINUTES);

        $challenge = CustomerLoginChallenge::updateOrCreate(
            ['email' => $email],
            [
                'code_hash' => $codeHash,
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'consumed_at' => null,
                'last_sent_at' => $now,
                'send_count' => $sendCount,
                'window_start' => $windowStart,
                'ip_address' => $ip ? substr($ip, 0, 45) : null,
            ]
        );

        // Record in email outbox
        try {
            EmailMessage::create([
                'type' => EmailType::LOGIN_CODE,
                'dedupe_key' => "LOGIN_CODE:{$challenge->id}:{$challenge->send_count}",
                'recipient' => $email,
                'subject' => "Your Dhansree sign-in code: {$code}",
                'status' => EmailStatus::SENT,
                'sent_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Could not persist login code email message: {$e->getMessage()}");
        }

        Log::info("Customer OTP code generated for [{$email}]: [{$code}]");

        return [
            'message' => self::OTP_REQUEST_MESSAGE,
            'resendAfterSeconds' => self::OTP_RESEND_COOLDOWN_SECONDS,
            'code' => app()->environment('local', 'testing') ? $code : null,
        ];
    }

    /**
     * Verify sign-in code, authenticate or create customer account, and issue session.
     *
     * @return array{customer: Customer, token: string, cookie: \Symfony\Component\HttpFoundation\Cookie, session: \App\Models\CustomerSession}
     */
    public function verifyCode(
        string $email,
        string $code,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $email = strtolower(trim($email));
        $code = trim($code);
        $now = now();

        $challenge = CustomerLoginChallenge::where('email', $email)->first();

        if (!$challenge) {
            $this->hasher->verifyAgainstDummy($code);
            $this->recordFailure($email, 'NO_CHALLENGE', $ip, $userAgent);
            throw new ApiException('INVALID_CODE', self::OTP_INVALID_MESSAGE, 401);
        }

        if (
            $challenge->consumed_at !== null ||
            $challenge->expires_at->isPast() ||
            $challenge->attempts >= self::OTP_MAX_ATTEMPTS
        ) {
            $this->hasher->verifyAgainstDummy($code);
            $this->recordFailure($email, 'UNUSABLE', $ip, $userAgent);
            throw new ApiException('INVALID_CODE', self::OTP_INVALID_MESSAGE, 401);
        }

        $matches = $this->hasher->verify($challenge->code_hash, $code);

        if (!$matches) {
            $attempts = $challenge->attempts + 1;
            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                $challenge->delete();
                AuditLogger::log(
                    action: 'CUSTOMER_LOGIN_BLOCKED',
                    actorType: AuditActorType::ANONYMOUS,
                    entityType: 'customer',
                    metadata: ['reason' => 'TOO_MANY_ATTEMPTS']
                );
            } else {
                $challenge->update(['attempts' => $attempts]);
                $this->recordFailure($email, 'WRONG_CODE', $ip, $userAgent);
            }

            throw new ApiException('INVALID_CODE', self::OTP_INVALID_MESSAGE, 401);
        }

        // Valid code: execute atomic sign-in
        return DB::transaction(function () use ($challenge, $email, $now, $ip, $userAgent) {
            // Mark challenge consumed
            $consumed = CustomerLoginChallenge::where('id', $challenge->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => $now]);

            if ($consumed === 0) {
                throw new ApiException('INVALID_CODE', self::OTP_INVALID_MESSAGE, 401);
            }

            // Find or create customer
            $customer = Customer::where('email', $email)->first();

            if ($customer) {
                if (!$customer->email_verified_at) {
                    $customer->update(['email_verified_at' => $now]);
                }
            } else {
                // Determine name from last guest order or email handle
                $lastOrder = Order::where('customer_email', $email)
                    ->orderBy('placed_at', 'desc')
                    ->first();

                $name = $lastOrder?->customer_name
                    ?? substr(explode('@', $email)[0], 0, 100);

                $customer = Customer::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => null,
                    'email_verified_at' => $now,
                    'status' => CustomerStatus::ACTIVE,
                ]);

                AuditLogger::log(
                    action: 'CUSTOMER_CREATED',
                    actorType: AuditActorType::CUSTOMER,
                    actorCustomerId: $customer->id,
                    entityType: 'customer',
                    entityId: $customer->id
                );

                // Reconcile and link past guest orders
                $linkedCount = Order::where('customer_email', $email)
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);

                if ($linkedCount > 0) {
                    AuditLogger::log(
                        action: 'CUSTOMER_ORDERS_LINKED',
                        actorType: AuditActorType::CUSTOMER,
                        actorCustomerId: $customer->id,
                        entityType: 'customer',
                        entityId: $customer->id,
                        metadata: ['orders' => $linkedCount]
                    );
                }
            }

            if ($customer->status !== CustomerStatus::ACTIVE) {
                throw new ApiException(
                    'ACCOUNT_DISABLED',
                    'This account has been deactivated. Please contact us if you need help.',
                    403
                );
            }

            $customer->update(['last_login_at' => $now]);

            // Create session
            $sessionResult = CustomerSessionManager::createSession($customer, $ip, $userAgent);

            AuditLogger::log(
                action: 'CUSTOMER_LOGIN_SUCCEEDED',
                actorType: AuditActorType::CUSTOMER,
                actorCustomerId: $customer->id,
                entityType: 'customer',
                entityId: $customer->id
            );

            return [
                'customer' => $customer,
                'token' => $sessionResult['token'],
                'cookie' => $sessionResult['cookie'],
                'session' => $sessionResult['session'],
            ];
        });
    }

    /**
     * Sign out customer session.
     */
    public function signOut(string $token, Customer $customer): void
    {
        CustomerSessionManager::revokeSession($token);

        AuditLogger::log(
            action: 'CUSTOMER_LOGOUT',
            actorType: AuditActorType::CUSTOMER,
            actorCustomerId: $customer->id,
            entityType: 'customer',
            entityId: $customer->id
        );
    }

    private function recordFailure(string $email, string $reason, ?string $ip, ?string $userAgent): void
    {
        $domain = substr(strrchr($email, '@') ?: '', 1);

        AuditLogger::log(
            action: 'CUSTOMER_LOGIN_FAILED',
            actorType: AuditActorType::ANONYMOUS,
            entityType: 'customer',
            metadata: [
                'reason' => $reason,
                'domain' => $domain,
            ]
        );
    }
}
