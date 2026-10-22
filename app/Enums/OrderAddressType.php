<?php

namespace App\Enums;

enum OrderAddressType: string
{
    case SHIPPING = 'SHIPPING';
    case BILLING = 'BILLING';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
