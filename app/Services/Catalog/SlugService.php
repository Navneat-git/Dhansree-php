<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use Illuminate\Support\Str;

class SlugService
{
    public const SLUG_MAX_LENGTH = 100;

    /**
     * Categories live at the site root (e.g. `/smart-gadgets`), so their slugs must never
     * shadow a page or system path, now or planned.
     */
    public const RESERVED_CATEGORY_SLUGS = [
        'about',
        'account',
        'admin',
        'api',
        'blog',
        'brands',
        'cart',
        'categories',
        'category',
        'checkout',
        'collections',
        'contact',
        'faq',
        'healthz',
        'help',
        'login',
        'logout',
        'media',
        'offers',
        'order',
        'orders',
        'pages',
        'privacy-policy',
        'product',
        'products',
        'refund-policy',
        'register',
        'return-policy',
        'search',
        'shipping-policy',
        'shop',
        'signin',
        'signup',
        'sitemap',
        'static',
        'terms',
        'track-order',
        'wishlist',
    ];

    /**
     * Turns a name into a URL slug: "Home & Lifestyle" → "home-and-lifestyle",
     * "Café Décor" → "cafe-decor".
     */
    public static function slugify(string $text, int $maxLength = self::SLUG_MAX_LENGTH): string
    {
        // Replace & with and
        $text = str_replace('&', ' and ', $text);

        // Remove apostrophes
        $text = str_replace(["'", '’'], '', $text);

        $slug = Str::slug($text, '-');

        if (mb_strlen($slug) > $maxLength) {
            $slug = mb_substr($slug, 0, $maxLength);
            $slug = rtrim($slug, '-');
        }

        return $slug ?: 'item';
    }

    /**
     * Generate candidate slug: base, base-2, base-3, ...
     */
    public static function candidate(string $base, int $attempt, int $maxLength = self::SLUG_MAX_LENGTH): string
    {
        if ($attempt <= 1) {
            return $base;
        }

        $suffix = '-' . $attempt;
        $maxBaseLen = $maxLength - mb_strlen($suffix);
        $trimmedBase = rtrim(mb_substr($base, 0, $maxBaseLen), '-');

        return $trimmedBase . $suffix;
    }

    /**
     * Generate unique slug using a check closure.
     *
     * @param callable(string): bool $isTaken
     */
    public static function generateUnique(
        string $text,
        callable $isTaken,
        array $reservedList = [],
        int $maxLength = self::SLUG_MAX_LENGTH
    ): string {
        $base = self::slugify($text, $maxLength);
        $attempt = 1;

        while (true) {
            $candidate = self::candidate($base, $attempt, $maxLength);

            $isReserved = in_array(strtolower($candidate), $reservedList, true);
            if (!$isReserved && !$isTaken($candidate)) {
                return $candidate;
            }

            $attempt++;
            if ($attempt > 1000) {
                // Failsafe with random suffix
                return self::slugify($base . '-' . Str::random(6), $maxLength);
            }
        }
    }
}
