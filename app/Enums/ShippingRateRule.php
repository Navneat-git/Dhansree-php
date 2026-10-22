<?php

namespace App\Enums;

enum ShippingRateRule: string
{
    case CHEAPEST = 'CHEAPEST';
    case RECOMMENDED = 'RECOMMENDED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
