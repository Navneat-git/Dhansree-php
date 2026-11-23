<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\Product;
use App\Models\SocialSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaAdCopyService
{
    /**
     * Supported Ad Angles.
     */
    public const ANGLES = [
        'festive' => [
            'label' => '👑 Royal Festive Edit',
            'tagline' => 'Jaipur artisanal royal wear for festive & special celebrations',
            'badge' => 'Festive Elegance',
        ],
        'comfort' => [
            'label' => '🌿 Pure Cotton Comfort',
            'tagline' => '100% breathable cotton for effortless all-day ease & workwear',
            'badge' => 'Daily Comfort',
        ],
        'offer' => [
            'label' => '💥 D2C Manufacturer Value',
            'tagline' => 'Factory-direct price with zero retail middleman markup',
            'badge' => 'Big Savings',
        ],
        'viral' => [
            'label' => '🔥 Viral Bestseller & Social Proof',
            'tagline' => 'Rave 5-star customer reviews and high demand across India',
            'badge' => 'Trending Bestseller',
        ],
        'fit' => [
            'label' => '👗 Flattering Silhouette & Fit',
            'tagline' => 'Tailored for Indian body contours in inclusive sizes S to 2XL',
            'badge' => 'Flattering Fit',
        ],
    ];

    /**
     * Generate full suite or specific angle ad copy for a product.
     *
     * @param Product $product
     * @param array{
     *     provider?: string,
     *     angle?: string,
     *     custom_instructions?: ?string,
     *     utm_campaign?: ?string,
     *     utm_content?: ?string,
     *     image_url?: ?string,
     * } $options
     * @return array{
     *     product: array,
     *     provider_used: string,
     *     angles: array<string, array{
     *         angle_key: string,
     *         angle_label: string,
     *         angle_tagline: string,
     *         badge: string,
     *         primary_text: string,
     *         headline: string,
     *         description: string,
     *         cta: string,
     *         destination_url: string,
     *         creative_image_url: string,
     *         full_copy_bundle: string
     *     }>
     * }
     */
    public function generateAdSuite(Product $product, array $options = []): array
    {
        $provider = $options['provider'] ?? 'smart_ai';
        $selectedAngle = $options['angle'] ?? 'all';
        $customInstructions = trim((string) ($options['custom_instructions'] ?? ''));
        $utmCampaign = trim((string) ($options['utm_campaign'] ?? ''));

        // 1. Extract and sanitize product metadata
        $productData = $this->extractProductMetadata($product, $options);

        // 2. Determine which angles to generate
        $anglesToGenerate = ($selectedAngle === 'all' || !isset(self::ANGLES[$selectedAngle]))
            ? array_keys(self::ANGLES)
            : [$selectedAngle];

        $results = [];
        $providerUsed = 'smart_ai';

        // 3. Attempt External AI (Gemini or ChatGPT) if requested
        if ($provider === 'gemini') {
            $geminiResults = $this->generateWithGemini($productData, $anglesToGenerate, $customInstructions);
            if (!empty($geminiResults)) {
                $results = $geminiResults;
                $providerUsed = 'gemini';
            }
        } elseif ($provider === 'chatgpt') {
            $chatGptResults = $this->generateWithChatGpt($productData, $anglesToGenerate, $customInstructions);
            if (!empty($chatGptResults)) {
                $results = $chatGptResults;
                $providerUsed = 'chatgpt';
            }
        }

        // 4. Fill any missing angles with our Built-in Smart D2C Copy Engine
        foreach ($anglesToGenerate as $angleKey) {
            if (!isset($results[$angleKey])) {
                $results[$angleKey] = $this->generateSmartCopy($productData, $angleKey, $customInstructions);
            }

            // Ensure destination URL and complete copy package bundle are always attached
            $destUrl = $this->buildDestinationUrl($productData['url'], $productData['slug'], $utmCampaign, $angleKey);
            $results[$angleKey]['destination_url'] = $destUrl;
            $results[$angleKey]['creative_image_url'] = $productData['primary_image_url'];
            $results[$angleKey]['cta'] = 'Shop Now';
            $results[$angleKey]['angle_key'] = $angleKey;
            $results[$angleKey]['angle_label'] = self::ANGLES[$angleKey]['label'];
            $results[$angleKey]['angle_tagline'] = self::ANGLES[$angleKey]['tagline'];
            $results[$angleKey]['badge'] = self::ANGLES[$angleKey]['badge'];
            $results[$angleKey]['full_copy_bundle'] = $this->formatFullCopyBundle(
                $productData['name'],
                self::ANGLES[$angleKey]['label'],
                $results[$angleKey]['primary_text'],
                $results[$angleKey]['headline'],
                $results[$angleKey]['description'],
                $destUrl
            );
        }

        return [
            'product' => $productData,
            'provider_used' => $providerUsed,
            'angles' => $results,
        ];
    }

    /**
     * Extract rich, reliable product metadata for marketing.
     */
    public function extractProductMetadata(Product $product, array $options = []): array
    {
        $product->loadMissing(['category', 'variants', 'images', 'productAttributes', 'options.values']);

        $brandName = SocialSetting::get('brand_name', 'Dhansree Trends');
        $minPrice = $product->min_price_rupees ?: 1299;
        $mrp = $product->mrp_rupees ?: ($minPrice * 1.8);

        $discountPct = 0;
        $discountAmount = 0;
        if ($mrp > $minPrice) {
            $discountAmount = (int) round($mrp - $minPrice);
            $discountPct = (int) round((($mrp - $minPrice) / $mrp) * 100);
        }

        // Fabric lookup
        $fabric = 'Pure Breathable Cotton';
        $attrFabric = $product->productAttributes->first(fn($a) => in_array(strtolower($a->label), ['fabric', 'material']));
        if ($attrFabric && !empty($attrFabric->value)) {
            $fabric = $attrFabric->value;
        }

        // Sizes lookup
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $sizeOption = $product->options->first(fn($o) => strtolower($o->name) === 'size');
        if ($sizeOption && $sizeOption->values->isNotEmpty()) {
            $sizes = $sizeOption->values->pluck('value')->all();
        }

        // Colors lookup
        $colors = [];
        $colorOption = $product->options->first(fn($o) => in_array(strtolower($o->name), ['color', 'colour']));
        if ($colorOption && $colorOption->values->isNotEmpty()) {
            $colors = $colorOption->values->pluck('value')->all();
        }

        // Images collection
        $allImages = $product->images->pluck('url')->filter()->values()->all();
        $primaryImg = $options['image_url'] ?? ($product->primary_image_url ?: ($allImages[0] ?? asset('images/logo.png')));

        $categoryName = $product->category?->name ?: "Women's Ethnic Fashion";

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $categoryName,
            'fabric' => $fabric,
            'sizes' => $sizes,
            'sizes_text' => implode(', ', $sizes),
            'colors' => $colors,
            'colors_text' => !empty($colors) ? implode(', ', $colors) : 'Vibrant Jaipur Hues',
            'price_rupees' => (int) round($minPrice),
            'mrp_rupees' => (int) round($mrp),
            'discount_pct' => $discountPct,
            'discount_amount' => $discountAmount,
            'formatted_price' => '₹' . number_format($minPrice, 0),
            'formatted_mrp' => '₹' . number_format($mrp, 0),
            'primary_image_url' => $primaryImg,
            'all_images' => $allImages,
            'url' => url('/product/' . $product->slug),
            'brand' => $brandName,
        ];
    }

    /**
     * Built-in direct response copywriting engine tuned for Indian D2C Meta Ads.
     * Always generates compelling, highly readable, structured ad copy.
     */
    public function generateSmartCopy(array $p, string $angle, string $customInstructions = ''): array
    {
        $price = $p['formatted_price'];
        $mrp = $p['formatted_mrp'];
        $pct = $p['discount_pct'] > 0 ? "{$p['discount_pct']}% OFF" : 'Special Introductory Price';
        $savings = $p['discount_amount'] > 0 ? "Save ₹" . number_format($p['discount_amount'], 0) : 'Direct Value';
        $fabric = $p['fabric'];
        $sizes = $p['sizes_text'];
        $name = $p['name'];
        $category = $p['category'];

        $extraNote = !empty($customInstructions) ? "\n\n💡 {$customInstructions}" : '';

        switch ($angle) {
            case 'festive':
                $primaryText = "👑 Turn Heads At Every Festive Occasion! ✨\n\n" .
                    "Step into royalty with our {$name}. Handcrafted in the heritage city of Jaipur, this ensemble brings timeless grace, delicate artistry, and rich ethnic detailing directly to your wardrobe.\n\n" .
                    "Why women across India are in love with this piece:\n" .
                    "🌸 Fabric: Exquisite {$fabric} – lightweight & breathable\n" .
                    "🪡 Artisanal Craft: Flattering yoke detailing with graceful flare\n" .
                    "📏 Sizes: {$sizes} (tailored for comfortable movement)\n" .
                    "💵 Special Offer: {$price} (MRP: {$mrp} | {$pct})\n\n" .
                    "✅ Cash on Delivery (COD) Available\n" .
                    "✅ Free All-India Express Shipping (2–4 Days)\n" .
                    "✅ 5-Day Hassle-Free Size Exchanges\n\n" .
                    "⚡ Limited festive stock crafted per batch.\n" .
                    "👉 Tap 'Shop Now' below to order yours before your size sells out!" . $extraNote;

                $headline = "Jaipur {$category} - {$pct}";
                $description = "Cash on Delivery | Free Shipping";
                break;

            case 'comfort':
                $primaryText = "🌿 Beat The Heat With 100% Breathable Jaipur Cotton! 🌸\n\n" .
                    "Tired of stiff ethnic wear that shrinks or feels itchy after a few hours? Experience all-day feather-light comfort with our {$name}.\n\n" .
                    "Designed for long workdays, casual brunches, and relaxed festive gatherings:\n" .
                    "✨ Pure {$fabric} that feels ultra-soft on skin\n" .
                    "✨ Non-transparent weave with 100% color-fast dye\n" .
                    "✨ Comfort-fit trousers with easy elasticated waist\n" .
                    "✨ Available in inclusive sizes: {$sizes}\n\n" .
                    "🔥 Deal of the Day: Only {$price} (Usually {$mrp})\n\n" .
                    "🚚 Pay on Delivery Available across India\n" .
                    "🔄 Easy 5-Day Exchanges if size doesn't fit\n\n" .
                    "👉 Click 'Shop Now' below to claim your pure cotton comfort set today!" . $extraNote;

                $headline = "Pure Breathable Cotton - Only {$price}";
                $description = "Sizes S to 2XL | COD Available";
                break;

            case 'offer':
                $primaryText = "💥 Factory-Direct Jaipur Fashion — Why Pay 3X In Retail Malls? 💥\n\n" .
                    "At Dhansree Trends, we design, handcraft, and ship directly from our Jaipur workshop to your doorstep. Zero middlemen markups. Zero showroom commissions.\n\n" .
                    "Get this designer {$name} at genuine wholesale-direct pricing:\n" .
                    "🏷️ Price: {$price} (MRP: {$mrp})\n" .
                    "🎁 {$pct} | You {$savings} instantly!\n" .
                    "🧵 Authentic {$fabric} with hand-finished hemlines\n" .
                    "📐 Sizes in stock: {$sizes}\n\n" .
                    "🛡️ Dhansree Buyer Guarantee:\n" .
                    "• Cash On Delivery Available All Over India\n" .
                    "• Free Doorstep Delivery\n" .
                    "• 100% Genuine Jaipur Artisanal Quality\n\n" .
                    "⏳ Special price valid while ongoing batch stock lasts.\n" .
                    "👉 Tap 'Shop Now' below to grab your discount before it expires!" . $extraNote;

                $headline = "Flat {$pct} | Direct From Jaipur";
                $description = "Pay On Delivery | Free Shipping";
                break;

            case 'viral':
                $primaryText = "🔥 Trending Alert: Our Most Requested Jaipur Kurta Set Is Back In Stock! 🔥\n\n" .
                    "Over 12,000+ women have rated our Jaipur collections 4.9/5 stars for quality, fitting, and luxurious feel.\n\n" .
                    "The {$name} is our signature bestseller:\n" .
                    "⭐ \"The fabric is so soft and the fit is perfection! Got compliments all evening.\" – Verified Shopper\n\n" .
                    "Key Highlights:\n" .
                    "👗 Rich drape in premium {$fabric}\n" .
                    "📏 Flattering silhouette tailored for Indian shapes ({$sizes})\n" .
                    "💰 Special Limited-Time Offer: {$price} ({$pct})\n\n" .
                    "📦 Cash On Delivery (COD) Available\n" .
                    "🚚 Fast 2–4 Day Dispatch Across India\n" .
                    "🔄 5-Day Easy Exchanges\n\n" .
                    "⚠️ Selling out fast! Once sizes run out, restocking takes 3 weeks.\n" .
                    "👉 Click 'Shop Now' below to claim yours right away!" . $extraNote;

                $headline = "The Viral Jaipur Set Everyone's Buying";
                $description = "⭐ Rated 4.9/5 by 12,000+ Women";
                break;

            case 'fit':
            default:
                $primaryText = "👗 Finally, An Ethnic Silhouette Tailored For Real Indian Body Contours! 👗\n\n" .
                    "No tight armholes, no pulling across the chest, and no awkward bunching. Our {$name} is engineered with generous seam allowances and graceful drape.\n\n" .
                    "Why you'll feel radiant wearing this:\n" .
                    "🌸 Thoughtfully tailored fit in sizes {$sizes}\n" .
                    "🌸 Feather-light {$fabric} that moves with you effortlessly\n" .
                    "🌸 Elegant neckline and side slits for effortless grace\n" .
                    "🌸 Introductory Offer: {$price} (MRP: {$mrp})\n\n" .
                    "✅ Pay Cash on Delivery\n" .
                    "✅ Try at home with 5-Day Hassle-Free Size Exchanges\n" .
                    "✅ Handcrafted with love in Jaipur, Rajasthan\n\n" .
                    "👉 Tap 'Shop Now' below to discover your perfect fit today!" . $extraNote;

                $headline = "Flattering Jaipur Fit - Sizes {$sizes}";
                $description = "5-Day Easy Exchanges | COD Available";
                break;
        }

        // Truncate headline and description if slightly long for Meta ad standards
        $headline = Str::limit($headline, 45, '...');
        $description = Str::limit($description, 35, '');

        return [
            'primary_text' => $primaryText,
            'headline' => $headline,
            'description' => $description,
            'cta' => 'Shop Now',
        ];
    }

    /**
     * Generate creative copy via Google Gemini REST API.
     */
    protected function generateWithGemini(array $p, array $angles, string $customInstructions): array
    {
        $apiKey = trim((string) (SocialSetting::get('gemini_api_key') ?: config('video_studio.google_video.api_key') ?: env('GEMINI_API_KEY', '')));
        if (empty($apiKey)) {
            Log::info('MetaAdCopyService: No Gemini API key configured, using Smart AI.');
            return [];
        }

        $systemPrompt = $this->buildSystemPrompt($p, $angles, $customInstructions);

        try {
            // Using Gemini 1.5 Flash
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text) {
                    $json = json_decode($text, true);
                    if (is_array($json)) {
                        return $this->normalizeAiResponse($json, $p);
                    }
                }
            } else {
                Log::warning('MetaAdCopyService: Gemini API error', ['status' => $response->status(), 'response' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('MetaAdCopyService: Gemini exception, falling back to Smart AI: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Generate creative copy via OpenAI ChatGPT REST API.
     */
    protected function generateWithChatGpt(array $p, array $angles, string $customInstructions): array
    {
        $apiKey = trim((string) (SocialSetting::get('openai_api_key') ?: env('OPENAI_API_KEY', '')));
        if (empty($apiKey)) {
            Log::info('MetaAdCopyService: No OpenAI API key configured, using Smart AI.');
            return [];
        }

        $systemPrompt = $this->buildSystemPrompt($p, $angles, $customInstructions);

        try {
            $url = 'https://api.openai.com/v1/chat/completions';
            $response = Http::timeout(15)
                ->withToken($apiKey)
                ->post($url, [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an elite Meta Ads direct-response copywriter for Indian D2C ethnic fashion brands like Shopdeck and Scalio. Always respond with strict valid JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $systemPrompt,
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['choices'][0]['message']['content'] ?? null;
                if ($text) {
                    $json = json_decode($text, true);
                    if (is_array($json)) {
                        return $this->normalizeAiResponse($json, $p);
                    }
                }
            } else {
                Log::warning('MetaAdCopyService: ChatGPT API error', ['status' => $response->status(), 'response' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('MetaAdCopyService: ChatGPT exception, falling back to Smart AI: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Build the structured copywriting prompt for external AI models.
     */
    protected function buildSystemPrompt(array $p, array $angles, string $customInstructions): string
    {
        $anglesList = implode(', ', $angles);

        return <<<PROMPT
You are an expert direct-response copywriter specializing in high-converting Meta (Facebook & Instagram) ads for Indian D2C Women's Ethnic Fashion brands.

PRODUCT INFORMATION:
- Brand Name: {$p['brand']} (Jaipur, Rajasthan, India)
- Product Title: {$p['name']}
- Category: {$p['category']}
- Fabric: {$p['fabric']}
- Sizes Available: {$p['sizes_text']}
- Available Colors: {$p['colors_text']}
- Selling Price: {$p['formatted_price']}
- Original MRP: {$p['formatted_mrp']}
- Discount: {$p['discount_pct']}% OFF (Save ₹{$p['discount_amount']})
- Trust Factors: Cash on Delivery (COD) Available, Free All-India Shipping (2-4 Days), 5-Day Easy Size Exchanges, 100% Handcrafted in Jaipur.
- Call To Action (CTA): Shop Now
- Custom Instructions: {$customInstructions}

GENERATE AD COPY FOR THESE ANGLES: {$anglesList}
(Available angles: 'festive', 'comfort', 'offer', 'viral', 'fit')

REQUIREMENTS FOR EACH ANGLE:
1. "primary_text": 150-250 words formatted with engaging emojis, line breaks, hook in the first 2 lines (before "See more" truncation), clear fabric/benefit points, pricing, trust factors (COD, Free Shipping, 5-Day Exchanges), and strong closing CTA: "👉 Tap 'Shop Now' below to order yours before sizes run out!".
2. "headline": Maximum 40 characters. Punchy, action-oriented (e.g. "Jaipur Kurti Sets - 45% Off", "Pure Breathable Cotton - ₹{$p['price_rupees']}").
3. "description": Maximum 30 characters. E.g. "Cash on Delivery | Free Shipping", "Rated 4.9/5 by 12,000+ Women".
4. "cta": "Shop Now".

RESPONSE FORMAT:
Strictly return a JSON object with keys for each requested angle:
{
  "festive": {
    "primary_text": "...",
    "headline": "...",
    "description": "...",
    "cta": "Shop Now"
  },
  ...
}
PROMPT;
    }

    /**
     * Normalize AI JSON response to ensure all required fields exist.
     */
    protected function normalizeAiResponse(array $json, array $p): array
    {
        $normalized = [];
        // Support nested 'angles' key if model wrapped it
        $data = isset($json['angles']) && is_array($json['angles']) ? $json['angles'] : $json;

        foreach ($data as $key => $angleData) {
            if (is_array($angleData) && isset($angleData['primary_text'])) {
                $angleKey = strtolower((string) $key);
                $normalized[$angleKey] = [
                    'primary_text' => (string) ($angleData['primary_text'] ?? ''),
                    'headline' => Str::limit((string) ($angleData['headline'] ?? "Jaipur Handcrafted - {$p['formatted_price']}"), 45, '...'),
                    'description' => Str::limit((string) ($angleData['description'] ?? 'Cash on Delivery | Free Shipping'), 35, ''),
                    'cta' => 'Shop Now',
                ];
            }
        }

        return $normalized;
    }

    /**
     * Build destination product URL with tracking UTM tags.
     */
    public function buildDestinationUrl(string $productUrl, string $slug, string $customCampaign = '', string $angle = 'festive'): string
    {
        $campaign = !empty($customCampaign) ? Str::slug($customCampaign) : "meta_dhansree_{$slug}";
        $content = Str::slug($angle);

        $params = [
            'utm_source' => 'meta',
            'utm_medium' => 'paid_social',
            'utm_campaign' => $campaign,
            'utm_content' => $content,
            'utm_term' => $slug,
        ];

        $separator = str_contains($productUrl, '?') ? '&' : '?';
        return $productUrl . $separator . http_build_query($params);
    }

    /**
     * Pre-format full copy package for one-click copy into Meta Ads Manager.
     */
    protected function formatFullCopyBundle(
        string $productName,
        string $angleLabel,
        string $primaryText,
        string $headline,
        string $description,
        string $destinationUrl
    ): string {
        return "=== META ADS MANAGER COPY PACKAGE ===\n" .
            "Product: {$productName}\n" .
            "Angle: {$angleLabel}\n" .
            "Call To Action (CTA): Shop Now\n" .
            "Website URL (with UTM):\n{$destinationUrl}\n\n" .
            "---------------- [PRIMARY TEXT (Ad Caption)] ----------------\n" .
            "{$primaryText}\n\n" .
            "---------------- [HEADLINE (Bold)] ----------------\n" .
            "{$headline}\n\n" .
            "---------------- [DESCRIPTION] ----------------\n" .
            "{$description}\n" .
            "============================================================";
    }
}
