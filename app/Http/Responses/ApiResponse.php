<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200, ?array $meta = null): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status, [
            'Content-Type' => 'application/json',
        ]);
    }

    public static function paginated(
        mixed $items,
        int $page,
        int $pageSize,
        int $total,
        int $status = 200,
        ?array $extraMeta = null
    ): JsonResponse {
        $meta = array_merge([
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => (int) ceil($total / max(1, $pageSize)),
            ],
        ], $extraMeta ?? []);

        return self::success($items, $status, $meta);
    }

    public static function error(
        string $code,
        string $message,
        int $status = 400,
        ?array $details = null,
        ?string $requestId = null
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
            'requestId' => $requestId ?? (string) Str::uuid(),
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $status, [
            'Content-Type' => 'application/json',
        ]);
    }
}
