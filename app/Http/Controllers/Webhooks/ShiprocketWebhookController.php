<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderFulfillmentService;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShiprocketWebhookController extends Controller
{
    public function __construct(
        protected ShiprocketService $shiprocketService,
        protected OrderFulfillmentService $fulfillmentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $token = $request->header('x-api-key')
            ?? $request->header('HTTP_X_API_KEY')
            ?? $request->query('token');

        if (!$this->shiprocketService->verifyWebhookToken($token)) {
            Log::warning('Shiprocket webhook unauthorized token attempt', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized token'], 401);
        }

        try {
            $payload = $request->all();
            Log::info('Shiprocket webhook received', ['payload' => $payload]);

            $result = $this->fulfillmentService->handleShiprocketWebhook($payload);

            return response()->json($result);
        } catch (Throwable $e) {
            Log::error('Shiprocket webhook handling error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to process webhook',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
