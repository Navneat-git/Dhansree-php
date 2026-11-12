<?php

declare(strict_types=1);

namespace App\Services\SocialCommerce;

use App\Models\SocialContentCaption;
use App\Models\SocialProduct;
use App\Models\SocialSetting;

class CaptionGeneratorService
{
    /**
     * Default hashtags for Dhansree Women's Fashion.
     */
    public const DEFAULT_HASHTAGS = [
        '#Dhansree',
        '#WomenFashion',
        '#LatestSuits',
        '#EthnicWear',
        '#FashionCollection',
        '#IndianWear',
        '#DesignerSuits',
        '#FestiveWear',
    ];

    /**
     * Available tone templates.
     */
    public const TONES = [
        'new_arrival' => 'New Arrival Alert',
        'trending' => 'Trending & Viral Designs',
        'festive' => 'Royal Festive Edit',
        'special_offer' => 'Special Price Offer',
        'limited_stock' => 'Limited Stock Urgency',
        'daily_wear' => 'Daily Comfort Collection',
    ];

    /**
     * Generate structured caption for a product and platform.
     */
    public function generateCaption(
        SocialProduct $product,
        string $tone = 'new_arrival',
        string $platform = 'INSTAGRAM'
    ): array {
        $brandName = SocialSetting::get('brand_name', 'Dhansree');
        $tagline = SocialSetting::get('tagline', 'STYLE. TECH. EVERYDAY.');
        $whatsappNumber = SocialSetting::get('whatsapp_number', '+919887776777');
        $whatsappCta = SocialSetting::get('whatsapp_order_cta', 'DM or WhatsApp us to order');

        $category = $product->category_name ?: "Women's Fashion";
        $name = $product->name;
        $fabric = $product->fabric;
        $colors = $product->available_colors ?? [];
        $sizes = $product->available_sizes ?? [];

        // Pricing strictly from product fields without hallucination
        $offerPrice = $product->special_offer_price_rupees;
        $suggestedPrice = $product->suggested_selling_price_rupees;

        $lines = [];

        // 1. Hook Header
        switch ($tone) {
            case 'trending':
                $lines[] = "🔥 Most Loved & Trending Now 🔥";
                $lines[] = "Elevate your wardrobe with this exquisite design!";
                break;
            case 'festive':
                $lines[] = "👑 Royal Festive Edit 👑";
                $lines[] = "Handpicked festive perfection for special occasions.";
                break;
            case 'special_offer':
                $lines[] = "💥 Special Price Drop Alert 💥";
                $lines[] = "Grab our signature collection at an unbeatable price!";
                break;
            case 'limited_stock':
                $lines[] = "⏳ Limited Pieces Only ⏳";
                $lines[] = "Fastest fingers first! Highly requested design in limited quantities.";
                break;
            case 'daily_wear':
                $lines[] = "🌸 Effortless Daily Elegance 🌸";
                $lines[] = "Breezy comfort meets modern everyday styling.";
                break;
            case 'new_arrival':
            default:
                $lines[] = "✨ New Fashion Collection ✨";
                $lines[] = "Beautiful designs with elegant detailing!";
                break;
        }

        $lines[] = "";

        // 2. Product Name & Category
        $lines[] = "👗 {$name}";
        $lines[] = "📁 Collection: {$category}";

        // 3. Fabric (only if present)
        if (!empty($fabric)) {
            $lines[] = "🧵 Fabric: {$fabric}";
        }

        // 4. Colours (only if present)
        if (!empty($colors) && count($colors) > 0) {
            $lines[] = "🎨 Available Colours: " . implode(', ', $colors);
        }

        // 5. Sizes (only if present)
        if (!empty($sizes) && count($sizes) > 0) {
            $lines[] = "📏 Sizes: " . implode(', ', $sizes);
        }

        // 6. Pricing (strict - only what was entered)
        if ($offerPrice !== null) {
            if ($suggestedPrice !== null && $suggestedPrice > $offerPrice) {
                $lines[] = "💰 Special Price: ₹" . number_format($offerPrice, 0) . " (MRP: ₹" . number_format($suggestedPrice, 0) . ")";
            } else {
                $lines[] = "💰 Special Price: ₹" . number_format($offerPrice, 0);
            }
        } elseif ($suggestedPrice !== null) {
            $lines[] = "💰 Price: ₹" . number_format($suggestedPrice, 0);
        }

        $lines[] = "🚚 Fast Shipping Pan India Available";
        $lines[] = "✅ Product Code: {$product->product_code}";
        $lines[] = "";

        // 7. CTA
        $cleanPhone = preg_replace('/[^0-9]/', '', $whatsappNumber);
        if ($platform === 'WHATSAPP') {
            $lines[] = "📲 Tap to reply or message {$whatsappNumber} to book yours now!";
        } else {
            $lines[] = "📲 {$whatsappCta}";
            $lines[] = "💬 WhatsApp: +{$cleanPhone}";
        }

        $lines[] = "✨ {$brandName} — {$tagline}";
        $lines[] = "";

        // 8. Hashtags
        $hashtagList = self::DEFAULT_HASHTAGS;
        $categoryTag = '#' . str_replace([' ', "'", '-'], '', $category);
        if (!in_array($categoryTag, $hashtagList, true)) {
            $hashtagList[] = $categoryTag;
        }
        if (!empty($fabric)) {
            $fabricTag = '#' . str_replace([' ', "'", '-'], '', $fabric) . 'Suits';
            if (!in_array($fabricTag, $hashtagList, true)) {
                $hashtagList[] = $fabricTag;
            }
        }

        $captionText = implode("\n", $lines);
        $hashtagsString = implode(' ', $hashtagList);

        return [
            'tone' => $tone,
            'platform' => $platform,
            'caption_text' => $captionText,
            'hashtags' => $hashtagsString,
            'full_caption' => $captionText . "\n" . $hashtagsString,
        ];
    }

    /**
     * Save generated caption for a product.
     */
    public function saveCaption(
        SocialProduct $product,
        string $captionText,
        string $hashtags,
        string $tone = 'new_arrival',
        string $platform = 'ALL'
    ): SocialContentCaption {
        return SocialContentCaption::create([
            'social_product_id' => $product->id,
            'tone' => $tone,
            'platform' => $platform,
            'caption_text' => $captionText,
            'hashtags' => $hashtags,
        ]);
    }
}
