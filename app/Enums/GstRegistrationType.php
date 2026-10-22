<?php

namespace App\Enums;

enum GstRegistrationType: string
{
    case REGULAR = 'REGULAR';
    case COMPOSITION = 'COMPOSITION';
    case UNREGISTERED = 'UNREGISTERED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
