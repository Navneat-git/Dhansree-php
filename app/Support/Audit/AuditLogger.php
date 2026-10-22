<?php

namespace App\Support\Audit;

use App\Enums\AuditActorType;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public const SENSITIVE_KEY_REGEX = '/pass(word|wd|phrase)?|secret|token|hash|cookie|authori[sz]ation|api[-_]?key|private[-_]?key|otp|signature|cvv|card[-_]?number/i';
    public const MAX_DEPTH = 5;
    public const MAX_STRING_LENGTH = 500;
    public const MAX_ARRAY_ITEMS = 50;

    /**
     * Record an audit event with automatic redaction and bounding.
     */
    public static function log(
        string $action,
        AuditActorType $actorType,
        ?int $actorAdminUserId = null,
        ?int $actorCustomerId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?Request $request = null,
        ?int $actorId = null
    ): AuditLog {
        if ($actorAdminUserId === null && $actorId !== null && $actorType === AuditActorType::ADMIN) {
            $actorAdminUserId = $actorId;
        }

        $occurredAt = Carbon::now('UTC');
        $sanitizedMetadata = $metadata ? self::sanitizeMetadata($metadata) : null;

        $ip = null;
        $userAgent = null;
        $requestId = null;

        if ($request) {
            $ip = $request->ip();
            $userAgent = substr($request->userAgent() ?? '', 0, 255);
            $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();
        }

        return AuditLog::create([
            'occurred_at' => $occurredAt,
            'action' => substr($action, 0, 64),
            'actor_type' => $actorType,
            'actor_admin_user_id' => $actorAdminUserId,
            'actor_customer_id' => $actorCustomerId,
            'entity_type' => $entityType ? substr($entityType, 0, 64) : null,
            'entity_id' => $entityId,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent,
            'request_id' => $requestId,
            'metadata' => $sanitizedMetadata,
        ]);
    }

    /**
     * Recursively redact sensitive keys and bound depth/lengths.
     */
    public static function sanitizeMetadata(mixed $data, int $depth = 1): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return '[truncated]';
        }

        if (is_string($data)) {
            return mb_strlen($data) > self::MAX_STRING_LENGTH
                ? mb_substr($data, 0, self::MAX_STRING_LENGTH) . '...[truncated]'
                : $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        $count = 0;

        foreach ($data as $key => $value) {
            if ($count >= self::MAX_ARRAY_ITEMS) {
                $result['__truncated__'] = '[array truncated: max 50 items]';
                break;
            }

            if (is_string($key) && preg_match(self::SENSITIVE_KEY_REGEX, $key)) {
                $result[$key] = '[redacted]';
            } else {
                $result[$key] = self::sanitizeMetadata($value, $depth + 1);
            }

            $count++;
        }

        return $result;
    }
}
