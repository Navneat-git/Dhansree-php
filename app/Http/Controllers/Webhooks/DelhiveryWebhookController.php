<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DelhiveryWebhookController extends Controller
{
    public function __construct(
        protected OrderFulfillmentService $fulfillmentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            Log::info('Delhivery tracking webhook received', ['payload' => $payload]);

            $result = $this->fulfillmentService->handleDelhiveryWebhook($payload);

            return response()->json($result);
        } catch (Throwable $e) {
            Log::error('Delhivery webhook handling error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to process Delhivery webhook',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
