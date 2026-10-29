<?php

declare(strict_types=1);

namespace App\Services\VideoStudio;

use App\Models\Product;
use Illuminate\Support\Str;

class FashionVideoPromptService
{
    /**
     * Return all predefined fashion styles from configuration.
     *
     * @return array<string, array{name: string, badge: string, description: string, camera: string, lighting: string}>
     */
    public function getFashionStyles(): array
    {
        return config('video_studio.fashion_styles', []);
    }

    /**
     * Generate a rich, photorealistic fashion prompt tailored for Google Veo video generation.
     * Enforces strict garment fidelity to the catalog reference image.
     */
    public function buildPrompt(Product $product, string $styleKey = 'runway_walk', ?string $customNotes = null): string
    {
        $styles = $this->getFashionStyles();
        $style = $styles[$styleKey] ?? ($styles['runway_walk'] ?? [
            'name' => 'Runway Walk',
            'camera' => 'Smooth frontal tracking shot, eye-level, gliding backwards as the model walks forward.',
            'lighting' => 'Soft diffused runway lighting with gentle rim lights highlighting fabric textures.',
        ]);

        $garmentType = $this->detectGarmentType($product);
        $productTitle = clean_string($product->name);

        $promptParts = [];

        // 1. Scene core & model description
        $promptParts[] = "High-fashion cinematic video of a graceful South Asian female model showcasing a luxury designer {$garmentType} ({$productTitle}).";

        // 2. Strict garment fidelity directive
        $promptParts[] = "GARMENT FIDELITY: The model is wearing the EXACT garment from the reference image. Preserve the exact colors, neckline cut, sleeve design, border lace, embroidery threadwork, fabric drape, and dupatta styling shown in the reference image. Zero alteration of garment patterns, colors, or motifs.";

        // 3. Style-specific camera direction & movement
        $promptParts[] = "CAMERA & MOTION: {$style['camera']} Smooth, fluid human motion at natural 24fps.";

        // 4. Studio lighting & ambiance
        $promptParts[] = "LIGHTING & ATMOSPHERE: {$style['lighting']} Clean, distraction-free premium fashion backdrop.";

        // 5. Cinematic technical specs
        $promptParts[] = "VISUAL QUALITY: Shot on 35mm anamorphic cinema lens, shallow depth of field, photorealistic fabric texture, natural skin tones, professional fashion campaign color grading, 4K UHD resolution.";

        // 6. Custom user additions if provided
        if (!empty($customNotes)) {
            $cleanedNotes = clean_string($customNotes);
            $promptParts[] = "DIRECTOR NOTES: {$cleanedNotes}";
        }

        return implode(' ', $promptParts);
    }

    /**
     * Default negative prompt to eliminate common video generation artifacts.
     */
    public function getNegativePrompt(): string
    {
        return 'blurry, low resolution, distorted face, deformed limbs, extra fingers, missing limbs, morphing clothes, changing colors, altering garment embroidery, cartoon, 3d animation, CGI, plastic skin, flickering, jittery movement, watermark, text, logos, amateur video, poorly lit, ugly, mutated.';
    }

    /**
     * Detect specific ethnic wear category (Anarkali, Suit set, Co-ord, Kurti, Saree, Lehenga)
     * to provide the model with contextually accurate movement cues.
     */
    protected function detectGarmentType(Product $product): string
    {
        $text = strtolower($product->name . ' ' . ($product->category?->name ?? ''));

        if (Str::contains($text, 'anarkali')) {
            return "Anarkali suit with flared silhouette and draped dupatta";
        }

        if (Str::contains($text, ['sharara', 'gharara'])) {
            return "Sharara suit set with flowing flared trousers and dupatta";
        }

        if (Str::contains($text, ['co-ord', 'coord', 'co ord'])) {
            return "contemporary ethnic co-ord set";
        }

        if (Str::contains($text, ['three piece', '3 piece', '3-piece', 'suit set'])) {
            return "three-piece ethnic suit set with kurti, pants, and matching draped dupatta";
        }

        if (Str::contains($text, ['two piece', '2 piece', '2-piece'])) {
            return "two-piece ethnic kurti set";
        }

        if (Str::contains($text, ['lehenga', 'lehnga'])) {
            return "regal lehenga choli with ornate embroidery and sheer dupatta";
        }

        if (Str::contains($text, ['kurta', 'kurti'])) {
            return "designer kurti";
        }

        return "women's designer ethnic suit";
    }
}

/**
 * Basic sanitize helper if not defined
 */
if (!function_exists('App\Services\VideoStudio\clean_string')) {
    function clean_string(?string $str): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $str)));
    }
}
