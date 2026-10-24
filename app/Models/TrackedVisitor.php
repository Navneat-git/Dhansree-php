<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackedVisitor extends Model
{
    protected $table = 'tracked_visitors';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'total_visits' => 'integer',
        'customer_id' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class, 'customer_code', 'customer_code')
            ->orderBy('started_at', 'desc');
    }

    /**
     * Resolve or register a visitor by matching email or IP address.
     *
     * @return array{
     *   visitor: TrackedVisitor,
     *   is_new: bool,
     *   visit_number: int,
     *   customer_code: string
     * }
     */
    public static function resolveVisitor(
        string $ip,
        ?string $email = null,
        ?string $visitorUuid = null,
        ?array $geo = null,
        ?int $customerId = null
    ): array {
        $cleanEmail = !empty($email) ? strtolower(trim($email)) : null;
        $cleanIp = !empty($ip) ? trim($ip) : null;
        $cleanUuid = !empty($visitorUuid) ? trim($visitorUuid) : null;

        $visitor = null;

        // 1. If email is provided, match by email first
        if ($cleanEmail) {
            $visitor = self::where('email', $cleanEmail)->first();
        }

        // 2. If no match by email, match by IP address
        if (!$visitor && $cleanIp && $cleanIp !== '127.0.0.1' && $cleanIp !== '::1') {
            $visitor = self::where('ip_address', $cleanIp)->first();
        }

        // 3. Fallback to visitor cookie UUID if present
        if (!$visitor && $cleanUuid) {
            $visitor = self::where('visitor_uuid', $cleanUuid)->first();
        }

        $now = Carbon::now();

        if ($visitor) {
            // Existing visitor!
            $visitor->total_visits = ($visitor->total_visits ?? 0) + 1;
            $visitor->last_seen_at = $now;

            if ($cleanEmail && empty($visitor->email)) {
                $visitor->email = $cleanEmail;
            }
            if ($customerId && empty($visitor->customer_id)) {
                $visitor->customer_id = $customerId;
            }
            if (!empty($geo['city']) && empty($visitor->city)) {
                $visitor->city = $geo['city'];
                $visitor->region = $geo['region'] ?? null;
                $visitor->country = $geo['country'] ?? 'India';
                $visitor->country_code = $geo['country_code'] ?? 'IN';
            }
            if ($cleanIp && empty($visitor->ip_address)) {
                $visitor->ip_address = $cleanIp;
            }
            $visitor->save();

            return [
                'visitor' => $visitor,
                'is_new' => false,
                'visit_number' => $visitor->total_visits,
                'customer_code' => $visitor->customer_code,
            ];
        }

        // 4. Neither email nor IP matched any existing record -> Brand New Customer
        $maxId = (int) self::max('id');
        $customerCode = 'CUST-' . str_pad((string) ($maxId + 1), 5, '0', STR_PAD_LEFT);

        $visitor = self::create([
            'customer_code' => $customerCode,
            'customer_id' => $customerId,
            'email' => $cleanEmail,
            'ip_address' => $cleanIp,
            'visitor_uuid' => $cleanUuid,
            'city' => $geo['city'] ?? null,
            'region' => $geo['region'] ?? null,
            'country' => $geo['country'] ?? 'India',
            'country_code' => $geo['country_code'] ?? 'IN',
            'total_visits' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);

        return [
            'visitor' => $visitor,
            'is_new' => true,
            'visit_number' => 1,
            'customer_code' => $customerCode,
        ];
    }
}
