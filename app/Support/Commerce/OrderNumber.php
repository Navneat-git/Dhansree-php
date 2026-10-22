<?php

declare(strict_types=1);

namespace App\Support\Commerce;

final class OrderNumber
{
    public const PREFIX = 'DS-';
    public const LENGTH = 8;
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Generate an unambiguous, cryptographically secure 8-character order number.
     * E.g. DS-4K9W72PX
     */
    public static function generate(): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $result = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $randomIndex = random_int(0, $alphabetLength - 1);
            $result .= self::ALPHABET[$randomIndex];
        }

        return self::PREFIX . $result;
    }
}
