<?php

namespace App\Enums;

enum ShippingRateSource: string
{
    case FLAT_RATE = 'FLAT_RATE';
    case PROVIDER = 'PROVIDER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
