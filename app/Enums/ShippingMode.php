<?php

namespace App\Enums;

enum ShippingMode: string
{
    case FLAT_RATE = 'FLAT_RATE';
    case SHIPROCKET_RATE = 'SHIPROCKET_RATE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
