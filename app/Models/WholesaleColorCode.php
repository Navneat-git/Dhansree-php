<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WholesaleColorCode extends Model
{
    protected $table = 'wholesale_color_codes';

    protected $fillable = [
        'color_name',
        'color_code',
        'hex_code',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Standard Indian fashion color code dictionary fallback.
     *
     * @var array<string, string>
     */
    public const CODE_DICTIONARY = [
        'pink' => 'PNK',
        'blue' => 'BLU',
        'brown' => 'BRW',
        'yellow' => 'YLW',
        'mus yellow' => 'YLW',
        'mustard yellow' => 'YLW',
        'mustard' => 'MST',
        'black' => 'BLK',
        'white' => 'WHT',
        'off white' => 'OFWHT',
        'off-white' => 'OFWHT',
        'offwhite' => 'OFWHT',
        'red' => 'RED',
        'green' => 'GRN',
        'parrot green' => 'PRGRN',
        'parrot-green' => 'PRGRN',
        'maroon' => 'MRN',
        'purple' => 'PPL',
        'peach' => 'PCH',
        'orange' => 'ORG',
        'beige' => 'BEG',
        'grey' => 'GRE',
        'gray' => 'GRE',
        'magenta' => 'MGT',
        'teal' => 'TEA',
        'navy' => 'NVY',
        'navy blue' => 'BLU',
        'sky blue' => 'BLU',
        'royal blue' => 'BLU',
        'wine' => 'WIN',
        'rust' => 'RST',
        'gold' => 'GLD',
        'golden' => 'GLD',
        'silver' => 'SLV',
        'olive' => 'OLV',
        'coral' => 'CRL',
        'lavender' => 'LAV',
    ];

    /**
     * Resolve standard 3-4 letter uppercase color code for wholesale/fashion SKUs.
     */
    public static function resolveCode(?string $colorName): string
    {
        $normalized = strtolower(trim((string) $colorName));
        if ($normalized === '') {
            return '';
        }

        // 1. Direct DB lookup
        try {
            $code = self::whereRaw('LOWER(color_name) = ?', [$normalized])->value('color_code');
            if ($code) {
                return strtoupper(trim($code));
            }
        } catch (\Throwable $e) {
            // DB query fallback
        }

        // 2. Direct dictionary match
        if (isset(self::CODE_DICTIONARY[$normalized])) {
            return self::CODE_DICTIONARY[$normalized];
        }

        // 3. Substring match (e.g. "Dark Pink" -> "pink" -> PNK, "Powder Blue" -> "blue" -> BLU)
        foreach (self::CODE_DICTIONARY as $name => $c) {
            if (str_contains($normalized, $name)) {
                return $c;
            }
        }

        // 4. Consonant contraction / clean uppercase slug fallback
        $clean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $normalized));
        $noVowels = strtoupper(preg_replace('/[AEIOU]/i', '', $clean));
        if (strlen($noVowels) >= 3) {
            return substr($noVowels, 0, 3);
        }

        return substr($clean, 0, 4) ?: 'CLR';
    }

    /**
     * Resolve hex code for a color name.
     */
    public static function resolveHex(?string $colorName): string
    {
        $normalized = strtolower(trim((string) $colorName));
        if ($normalized === '') {
            return '#94a3b8';
        }

        try {
            $hex = self::whereRaw('LOWER(color_name) = ?', [$normalized])->value('hex_code');
            if ($hex) {
                return $hex;
            }
        } catch (\Throwable $e) {
            // DB query fallback
        }

        $fallbacks = [
            'pink' => '#ec4899',
            'blue' => '#3b82f6',
            'brown' => '#8b4513',
            'yellow' => '#eab308',
            'black' => '#0f172a',
            'white' => '#ffffff',
            'off white' => '#faf9f6',
            'off-white' => '#faf9f6',
            'red' => '#ef4444',
            'green' => '#22c55e',
            'maroon' => '#800000',
            'purple' => '#a855f7',
            'peach' => '#ffcba4',
            'orange' => '#f97316',
            'beige' => '#f5f5dc',
            'grey' => '#6b7280',
            'gray' => '#6b7280',
            'magenta' => '#ff00ff',
            'teal' => '#0d9488',
            'navy' => '#1e3a8a',
            'rust' => '#b45309',
            'gold' => '#d97706',
        ];

        return $fallbacks[$normalized] ?? '#94a3b8';
    }
}
