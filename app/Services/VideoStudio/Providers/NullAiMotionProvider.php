<?php

namespace App\Services\VideoStudio\Providers;

use App\Models\ProductVideo;
use App\Services\VideoStudio\Contracts\AiMotionProviderInterface;

class NullAiMotionProvider implements AiMotionProviderInterface
{
    public function getId(): string
    {
        return 'null';
    }

    public function getName(): string
    {
        return 'AI Fashion Motion (Disabled)';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function generateMotion(ProductVideo $video, array $options = []): string
    {
        throw new \RuntimeException(
            'AI Fashion Motion is disabled. An image-to-video AI provider is not yet configured. ' .
            'Please use the FFmpeg Product Slideshow studio or configure an AI video provider in settings.'
        );
    }
}
