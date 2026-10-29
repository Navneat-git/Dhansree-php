<?php

namespace App\Services\VideoStudio\Contracts;

use App\Models\ProductVideo;

interface AiMotionProviderInterface
{
    /**
     * Get the unique identifier of the provider.
     */
    public function getId(): string;

    /**
     * Get human-readable provider name.
     */
    public function getName(): string;

    /**
     * Check if provider credentials/keys are configured and active.
     */
    public function isAvailable(): bool;

    /**
     * Generate an AI motion video from product imagery.
     *
     * @param ProductVideo $video
     * @param array $options
     * @return string Path to generated temporary MP4 file
     * @throws \RuntimeException
     */
    public function generateMotion(ProductVideo $video, array $options = []): string;
}
