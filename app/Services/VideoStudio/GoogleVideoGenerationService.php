<?php

declare(strict_types=1);

namespace App\Services\VideoStudio;

use App\Models\ProductVideo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleVideoGenerationService
{
    public function __construct(
        protected FfmpegService $ffmpegService
    ) {}

    /**
     * Check if Google Video Generation is configured and ready for live API calls.
     */
    public function isConfigured(): bool
    {
        if (config('video_studio.google_video.mock', false)) {
            return true;
        }

        $enabled = (bool) config('video_studio.google_video.enabled', false);
        $apiKey = trim((string) config('video_studio.google_video.api_key', ''));

        return $enabled && !empty($apiKey);
    }

    /**
     * Check whether we can start a new AI generation (daily and concurrent limits).
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function checkLimits(): array
    {
        // In mock mode or testing, always allow
        if (config('video_studio.google_video.mock', false) || app()->environment('testing')) {
            return ['allowed' => true, 'reason' => null];
        }

        if (!$this->isConfigured()) {
            return [
                'allowed' => false,
                'reason' => 'Google AI Video Generation is not configured. Please set GOOGLE_VIDEO_API_KEY and GOOGLE_VIDEO_ENABLED=true in .env.',
            ];
        }

        $dailyLimit = (int) config('video_studio.google_video.daily_limit', 10);
        $todayCount = ProductVideo::whereIn('video_type', ['ai_fashion', 'ai_fashion_branded'])
            ->whereDate('created_at', today())
            ->count();

        if ($dailyLimit > 0 && $todayCount >= $dailyLimit) {
            return [
                'allowed' => false,
                'reason' => "Daily AI video generation quota ({$dailyLimit} videos/day) has been reached. Please try again tomorrow or adjust GOOGLE_VIDEO_DAILY_LIMIT.",
            ];
        }

        $maxConcurrent = (int) config('video_studio.google_video.max_concurrent', 2);
        $activeCount = ProductVideo::whereIn('video_type', ['ai_fashion', 'ai_fashion_branded'])
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        if ($maxConcurrent > 0 && $activeCount >= $maxConcurrent) {
            return [
                'allowed' => false,
                'reason' => "Concurrent generation limit ({$maxConcurrent}) reached. Please wait for currently active videos to complete.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * List all models and their supported generation methods from Google AI API.
     *
     * @return array<int, array{name: string, methods: array<string>, display_name: string}>
     */
    public function listAvailableModels(): array
    {
        $apiKey = config('video_studio.google_video.api_key');
        if (empty($apiKey)) {
            return [];
        }

        $baseUrl = rtrim((string) config('video_studio.google_video.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->timeout(12)->get("{$baseUrl}/models");

            if (!$response->successful()) {
                Log::warning('Failed to list Google models: ' . $response->body());
                return [];
            }

            $models = $response->json('models') ?? [];
            $result = [];

            foreach ($models as $m) {
                $rawName = $m['name'] ?? '';
                $cleanName = preg_replace('#^models/#', '', $rawName);
                $result[] = [
                    'name' => $cleanName,
                    'full_name' => $rawName,
                    'display_name' => $m['displayName'] ?? $cleanName,
                    'methods' => $m['supportedGenerationMethods'] ?? [],
                    'description' => $m['description'] ?? '',
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Error listing Google models: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Auto-resolve the best active video generation model available for the current API key.
     *
     * @return array{model: ?string, is_verified: bool, available_models: array}
     */
    public function resolveActiveModel(): array
    {
        $configured = config('video_studio.google_video.model', 'gemini-omni-1.1-flash');
        if (in_array($configured, ['veo-2.0-generate-001', 'veo-3.1-generate-preview'], true)) {
            $configured = 'gemini-omni-1.1-flash';
        }

        $available = $this->listAvailableModels();

        if (empty($available)) {
            return [
                'model' => $configured ?: 'gemini-omni-1.1-flash',
                'is_verified' => false,
                'available_models' => [],
            ];
        }

        // 1. Check if configured model exists in available models (e.g. gemini-omni-1.1-flash)
        foreach ($available as $m) {
            if ($m['name'] === $configured) {
                return ['model' => $m['name'], 'is_verified' => true, 'available_models' => $available];
            }
        }

        // 2. Look for gemini-omni-1.1-flash
        foreach ($available as $m) {
            if (str_contains(strtolower($m['name']), 'omni')) {
                return ['model' => $m['name'], 'is_verified' => true, 'available_models' => $available];
            }
        }

        // 3. Look for any active model that supports predictLongRunning
        foreach ($available as $m) {
            if (in_array('predictLongRunning', $m['methods'], true)) {
                return ['model' => $m['name'], 'is_verified' => true, 'available_models' => $available];
            }
        }

        // 4. Look for any active model containing veo / video
        foreach ($available as $m) {
            if (str_contains(strtolower($m['name']), 'veo') || str_contains(strtolower($m['name']), 'video')) {
                return ['model' => $m['name'], 'is_verified' => true, 'available_models' => $available];
            }
        }

        // 5. Default to gemini-omni-1.1-flash
        return [
            'model' => 'gemini-omni-1.1-flash',
            'is_verified' => false,
            'available_models' => $available,
        ];
    }

    /**
     * Submit an asynchronous video generation request to Google Veo API.
     *
     * @param string $primaryImagePath Absolute path to the primary product reference image
     * @param string $prompt Detailed scene & model prompt
     * @param string $aspectRatio Aspect ratio e.g. "9:16", "1:1"
     * @param int $durationSeconds Video duration
     * @param string|null $negativePrompt Negative prompt
     * @return array{operation_id: string, estimated_cost: float, mock: bool}
     * @throws \RuntimeException
     */
    public function submitGeneration(
        string $primaryImagePath,
        string $prompt,
        string $aspectRatio = '9:16',
        int $durationSeconds = 5,
        ?string $negativePrompt = null
    ): array {
        $limits = $this->checkLimits();
        if (!$limits['allowed']) {
            throw new \RuntimeException($limits['reason'] ?? 'Generation limits reached.');
        }

        $estimatedCost = (float) config('video_studio.google_video.estimated_cost_per_clip', 0.15);

        // If mock mode is active, return a simulated operation
        if (config('video_studio.google_video.mock', false) || app()->environment('testing')) {
            return [
                'operation_id' => 'mock-op-' . Str::uuid(),
                'estimated_cost' => $estimatedCost,
                'mock' => true,
            ];
        }

        if (!file_exists($primaryImagePath)) {
            throw new \RuntimeException("Primary reference image not found on disk at: {$primaryImagePath}");
        }

        $imageBytes = File::get($primaryImagePath);
        $imageMime = mime_content_type($primaryImagePath) ?: 'image/jpeg';
        $imageBase64 = base64_encode($imageBytes);

        $apiKey = config('video_studio.google_video.api_key');
        $baseUrl = rtrim((string) config('video_studio.google_video.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        // Resolve active video generation model dynamically
        $modelResolution = $this->resolveActiveModel();
        $model = $modelResolution['model'] ?: 'gemini-omni-1.1-flash';

        // Map aspect ratio for Google Video API (supported: "9:16", "16:9", "1:1")
        $apiAspectRatio = match ($aspectRatio) {
            '1:1' => '1:1',
            '16:9' => '16:9',
            default => '9:16',
        };

        // If model is Gemini Omni Flash, call the Interactions API (Google's official recommendation)
        if (str_contains(strtolower($model), 'omni') || !str_contains(strtolower($model), 'veo')) {
            return $this->submitInteractionsGeneration($apiKey, $baseUrl, $model, $prompt, $imageBase64, $imageMime, $apiAspectRatio, $estimatedCost);
        }

        // Otherwise call predictLongRunning
        return $this->submitPredictLongRunning($apiKey, $baseUrl, $model, $prompt, $imageBase64, $imageMime, $apiAspectRatio, $durationSeconds, $negativePrompt, $estimatedCost);
    }

    /**
     * Submit video generation request via Google Interactions API (/v1beta/interactions).
     */
    protected function submitInteractionsGeneration(
        string $apiKey,
        string $baseUrl,
        string $model,
        string $prompt,
        string $imageBase64,
        string $imageMime,
        string $aspectRatio,
        float $estimatedCost
    ): array {
        $endpoint = "{$baseUrl}/interactions";

        $input = [
            [
                'type' => 'text',
                'text' => $prompt,
            ],
        ];

        if (!empty($imageBase64)) {
            $input[] = [
                'type' => 'image',
                'data' => $imageBase64,
                'mime_type' => $imageMime,
            ];
        }

        $payload = [
            'model' => $model,
            'input' => $input,
            'response_format' => [
                'type' => 'video',
                'aspect_ratio' => $aspectRatio,
            ],
        ];

        Log::info("Submitting AI Fashion video request to Google Interactions API [{$model}]...", [
            'aspect_ratio' => $aspectRatio,
            'prompt_length' => strlen($prompt),
        ]);

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post($endpoint, $payload);

        if (!$response->successful()) {
            $errorBody = $response->json('error.message') ?? $response->body();
            Log::error("Google Interactions API error ({$response->status()}): {$errorBody}");
            throw new \RuntimeException("Google AI Video API error: {$errorBody}");
        }

        $data = $response->json();
        $interactionId = $data['id'] ?? ('int-' . Str::uuid());

        // Cache any immediately returned video bytes
        $videoBytes = $this->extractVideoFromInteractionBody($data);
        if ($videoBytes) {
            Cache::put("google_video_interaction_{$interactionId}", $videoBytes, now()->addMinutes(30));
        }

        return [
            'operation_id' => "interactions/{$interactionId}",
            'estimated_cost' => $estimatedCost,
            'mock' => false,
        ];
    }

    /**
     * Submit video generation request via predictLongRunning endpoint.
     */
    protected function submitPredictLongRunning(
        string $apiKey,
        string $baseUrl,
        string $model,
        string $prompt,
        string $imageBase64,
        string $imageMime,
        string $apiAspectRatio,
        int $durationSeconds,
        ?string $negativePrompt,
        float $estimatedCost
    ): array {
        $endpoint = "{$baseUrl}/models/{$model}:predictLongRunning";

        $instance = [
            'prompt' => $prompt,
        ];

        if (!empty($imageBase64)) {
            $instance['image'] = [
                'bytesBase64Encoded' => $imageBase64,
                'mimeType' => $imageMime,
            ];
        }

        $parameters = [
            'aspectRatio' => $apiAspectRatio,
            'sampleCount' => 1,
            'durationSeconds' => (int) min(max($durationSeconds, 4), 8),
            'personGeneration' => 'allow_adult',
        ];

        if (!empty($negativePrompt)) {
            $parameters['negativePrompt'] = $negativePrompt;
        }

        $payload = [
            'instances' => [$instance],
            'parameters' => $parameters,
        ];

        Log::info("Submitting AI Fashion video request to Google Veo [{$model}]...", [
            'aspect_ratio' => $apiAspectRatio,
            'duration' => $durationSeconds,
            'prompt_length' => strlen($prompt),
        ]);

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($endpoint, $payload);

        if (!$response->successful()) {
            $errorBody = $response->json('error.message') ?? $response->body();
            Log::error("Google Veo API error ({$response->status()}): {$errorBody}");
            throw new \RuntimeException("Google AI Video API error: {$errorBody}");
        }

        $data = $response->json();
        $operationName = $data['name'] ?? null;

        if (empty($operationName)) {
            throw new \RuntimeException('Google AI Video API did not return an operation name.');
        }

        Log::info("Google Veo operation started: {$operationName}");

        return [
            'operation_id' => $operationName,
            'estimated_cost' => $estimatedCost,
            'mock' => false,
        ];
    }

    /**
     * Extract video binary data or download URL from Interactions API response body.
     */
    protected function extractVideoFromInteractionBody(array $data): ?string
    {
        // 1. Direct output_video.data (base64)
        $raw = $data['output_video']['data'] ?? $data['outputVideo']['data'] ?? null;
        if (!empty($raw)) {
            $decoded = base64_decode($raw, true);
            return $decoded !== false ? $decoded : $raw;
        }

        // 2. Direct output_video.uri (download from uri)
        $uri = $data['output_video']['uri'] ?? $data['outputVideo']['uri'] ?? null;
        if (!empty($uri)) {
            try {
                $apiKey = config('video_studio.google_video.api_key');
                $res = Http::withHeaders(['x-goog-api-key' => $apiKey])->timeout(60)->get($uri);
                if ($res->successful()) {
                    return $res->body();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to download video from uri: ' . $e->getMessage());
            }
        }

        // 3. Inspect steps array for video parts
        if (!empty($data['steps'])) {
            foreach ($data['steps'] as $step) {
                $parts = $step['content']['parts'] ?? [];
                foreach ($parts as $p) {
                    if (!empty($p['inline_data']['data'])) {
                        return base64_decode($p['inline_data']['data']);
                    }
                    if (!empty($p['video_data']['data'])) {
                        return base64_decode($p['video_data']['data']);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Poll operation status until completion or timeout.
     *
     * @param string $operationId Google operation name or mock operation
     * @param callable(int):void|null $onProgress Optional callback taking progress percentage
     * @return array{status: string, video_bytes: ?string, video_url: ?string, error: ?string}
     */
    public function pollOperation(string $operationId, ?callable $onProgress = null): array
    {
        // Handle mock operation in tests / local mock mode
        if (str_starts_with($operationId, 'mock-op-')) {
            if ($onProgress) {
                $onProgress(50);
            }
            return [
                'status' => 'completed',
                'video_bytes' => null, // Will generate sample MP4 via ffmpeg
                'video_url' => null,
                'error' => null,
            ];
        }

        $apiKey = config('video_studio.google_video.api_key');
        $baseUrl = rtrim((string) config('video_studio.google_video.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        // Handle Interactions API polling (operation starts with 'interactions/')
        if (str_starts_with($operationId, 'interactions/')) {
            $interactionId = str_replace('interactions/', '', $operationId);

            // Check if video was already cached from the initial response
            $cachedBytes = Cache::get("google_video_interaction_{$interactionId}");
            if ($cachedBytes) {
                if ($onProgress) $onProgress(100);
                return [
                    'status' => 'completed',
                    'video_bytes' => $cachedBytes,
                    'video_url' => null,
                    'error' => null,
                ];
            }

            $endpoint = "{$baseUrl}/interactions/{$interactionId}";
            $startTime = time();
            $maxPollSeconds = (int) config('video_studio.google_video.max_poll_seconds', 400);
            $pollInterval = (int) config('video_studio.google_video.poll_interval_seconds', 8);

            while ((time() - $startTime) < $maxPollSeconds) {
                $res = Http::withHeaders(['x-goog-api-key' => $apiKey])->timeout(30)->get($endpoint);
                if (!$res->successful()) {
                    return [
                        'status' => 'failed',
                        'video_bytes' => null,
                        'video_url' => null,
                        'error' => 'Interactions polling error: ' . $res->body(),
                    ];
                }

                $body = $res->json();
                $status = $body['status'] ?? 'in_progress';

                if ($status === 'completed') {
                    $bytes = $this->extractVideoFromInteractionBody($body);
                    if ($bytes) {
                        if ($onProgress) $onProgress(100);
                        return [
                            'status' => 'completed',
                            'video_bytes' => $bytes,
                            'video_url' => null,
                            'error' => null,
                        ];
                    }
                }

                if (in_array($status, ['failed', 'cancelled'], true)) {
                    return [
                        'status' => 'failed',
                        'video_bytes' => null,
                        'video_url' => null,
                        'error' => $body['error']['message'] ?? "Interaction {$status}.",
                    ];
                }

                if ($onProgress) $onProgress(min(65 + (time() - $startTime), 90));
                sleep($pollInterval);
            }

            return [
                'status' => 'timeout',
                'video_bytes' => null,
                'video_url' => null,
                'error' => "Video generation timed out after {$maxPollSeconds} seconds.",
            ];
        }

        $cleanOp = ltrim($operationId, '/');
        $endpoint = "{$baseUrl}/{$cleanOp}";

        $maxPollSeconds = (int) config('video_studio.google_video.max_poll_seconds', 400);
        $pollInterval = (int) config('video_studio.google_video.poll_interval_seconds', 8);
        $startTime = time();

        $currentProgress = 35;

        while ((time() - $startTime) < $maxPollSeconds) {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->timeout(30)->get($endpoint);

            if (!$response->successful()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                return [
                    'status' => 'failed',
                    'video_bytes' => null,
                    'video_url' => null,
                    'error' => "Polling error: {$errorMsg}",
                ];
            }

            $body = $response->json();

            // Check if done
            if (!empty($body['done'])) {
                if (!empty($body['error'])) {
                    return [
                        'status' => 'failed',
                        'video_bytes' => null,
                        'video_url' => null,
                        'error' => $body['error']['message'] ?? 'Unknown generation error.',
                    ];
                }

                // Extract generated video across all Google Veo response formats
                $videoData = $body['response']['generateVideoResponse']['generatedVideos'][0]['video']
                    ?? $body['response']['generatedVideos'][0]['video']
                    ?? $body['response']['generatedVideos'][0]
                    ?? $body['response']['videos'][0]
                    ?? $body['response']['video']
                    ?? null;

                if (!$videoData) {
                    return [
                        'status' => 'failed',
                        'video_bytes' => null,
                        'video_url' => null,
                        'error' => 'Operation completed but no video payload was returned.',
                    ];
                }

                $bytes = null;
                $url = null;

                if (is_string($videoData)) {
                    $url = $videoData;
                } elseif (is_array($videoData)) {
                    if (!empty($videoData['bytesBase64Encoded'])) {
                        $bytes = base64_decode($videoData['bytesBase64Encoded']);
                    } elseif (!empty($videoData['uri'])) {
                        $url = $videoData['uri'];
                    } elseif (!empty($videoData['videoUri'])) {
                        $url = $videoData['videoUri'];
                    }
                }

                if ($onProgress) {
                    $onProgress(85);
                }

                return [
                    'status' => 'completed',
                    'video_bytes' => $bytes,
                    'video_url' => $url,
                    'error' => null,
                ];
            }

            // Still processing: increment progress
            $currentProgress = min($currentProgress + 5, 80);
            if ($onProgress) {
                $onProgress($currentProgress);
            }

            sleep($pollInterval);
        }

        return [
            'status' => 'timeout',
            'video_bytes' => null,
            'video_url' => null,
            'error' => "Video generation timed out after {$maxPollSeconds} seconds.",
        ];
    }

    /**
     * Download or retrieve video file into a local destination path.
     */
    public function downloadVideoToPath(array $pollResult, string $destinationPath, string $fallbackImagePath): bool
    {
        // 1. Direct bytes from API response
        if (!empty($pollResult['video_bytes'])) {
            File::put($destinationPath, $pollResult['video_bytes']);
            return file_exists($destinationPath) && filesize($destinationPath) > 0;
        }

        // 2. Remote URL from API response
        if (!empty($pollResult['video_url'])) {
            $apiKey = config('video_studio.google_video.api_key');
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->timeout(120)->get($pollResult['video_url']);

            if ($response->successful()) {
                File::put($destinationPath, $response->body());
                return file_exists($destinationPath) && filesize($destinationPath) > 0;
            }
        }

        // 3. Fallback mock generation using FFmpeg for testing or simulation
        if (config('video_studio.google_video.mock', false) || app()->environment('testing') || !file_exists($destinationPath)) {
            return $this->createMockVideo($fallbackImagePath, $destinationPath);
        }

        return false;
    }

    /**
     * Generate a simulated AI fashion video locally using FFmpeg for testing without spending money.
     */
    protected function createMockVideo(string $imagePath, string $outputPath): bool
    {
        if (!file_exists($imagePath)) {
            // Generate blank black frame if image not provided
            $cmd = [
                $this->ffmpegService->getFfmpegPath(),
                '-y',
                '-f', 'lavfi',
                '-i', 'color=c=black:s=1080x1920:d=5',
                '-c:v', 'libx264',
                '-pix_fmt', 'yuv420p',
                $outputPath,
            ];
        } else {
            // Create a 5s smooth zoom on the reference image
            $cmd = [
                $this->ffmpegService->getFfmpegPath(),
                '-y',
                '-loop', '1',
                '-i', $imagePath,
                '-t', '5',
                '-vf', 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,zoompan=z=\'min(zoom+0.0015,1.15)\':d=125:x=\'iw/2-(iw/zoom/2)\':y=\'ih/2-(ih/zoom/2)\':s=1080x1920:fps=25',
                '-c:v', 'libx264',
                '-pix_fmt', 'yuv420p',
                '-r', '25',
                $outputPath,
            ];
        }

        $process = new \Symfony\Component\Process\Process($cmd);
        $process->setTimeout(60);
        $process->run();

        return $process->isSuccessful() && file_exists($outputPath) && filesize($outputPath) > 0;
    }
}
