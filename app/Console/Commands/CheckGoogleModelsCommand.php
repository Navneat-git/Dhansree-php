<?php

namespace App\Console\Commands;

use App\Services\VideoStudio\GoogleVideoGenerationService;
use Illuminate\Console\Command;

class CheckGoogleModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video-studio:check-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect available Google Generative AI models and video generation capabilities for the current API key.';

    public function handle(GoogleVideoGenerationService $service): int
    {
        $this->info('── Checking Google Video Generation API Configuration ──');

        $apiKey = config('video_studio.google_video.api_key');
        $baseUrl = config('video_studio.google_video.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $configuredModel = config('video_studio.google_video.model');

        if (empty($apiKey)) {
            $this->error('GOOGLE_VIDEO_API_KEY (or GEMINI_API_KEY) is not configured in .env!');
            return self::FAILURE;
        }

        $maskedKey = substr($apiKey, 0, 6) . '...' . substr($apiKey, -4);
        $this->line("API Key: <comment>{$maskedKey}</comment>");
        $this->line("Base URL: <comment>{$baseUrl}</comment>");
        $this->line("Configured Model: <comment>{$configuredModel}</comment>");
        $this->newLine();

        $this->info('Connecting to Google ModelService (models.list)...');
        $models = $service->listAvailableModels();

        if (empty($models)) {
            $this->error('No models returned from Google API. Please check your API key, network, or billing status.');
            return self::FAILURE;
        }

        $this->info("Found " . count($models) . " models available to your project:");
        $this->newLine();

        $tableRows = [];
        $videoCapable = [];

        foreach ($models as $m) {
            $methodsStr = implode(', ', $m['methods']);
            $isLongRunning = in_array('predictLongRunning', $m['methods'], true);
            $isVeo = str_contains(strtolower($m['name']), 'veo') || str_contains(strtolower($m['name']), 'video');

            if ($isLongRunning || $isVeo) {
                $videoCapable[] = $m['name'];
            }

            $tableRows[] = [
                $m['name'],
                $m['display_name'],
                $methodsStr,
                $isLongRunning ? 'YES' : 'no',
            ];
        }

        $this->table(['Model Name', 'Display Name', 'Supported Methods', 'predictLongRunning'], $tableRows);

        $this->newLine();
        if (!empty($videoCapable)) {
            $this->info('Video Generation Capable Models Found:');
            foreach ($videoCapable as $vName) {
                $this->line("  ✓ <info>{$vName}</info>");
            }
        } else {
            $this->warn('No model explicitly listed predictLongRunning or veo in models.list.');
            $this->line('Google Veo may require enabling Vertex AI / Generative Media in Google Cloud Console.');
        }

        $resolution = $service->resolveActiveModel();
        $this->newLine();
        $this->info("Resolved Active Model: <comment>" . ($resolution['model'] ?? 'None') . "</comment>");

        return self::SUCCESS;
    }
}
