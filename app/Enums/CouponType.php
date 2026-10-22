<?php

namespace App\Enums;

enum CouponType: string
{
    case PERCENTAGE = 'PERCENTAGE';
    case FIXED_AMOUNT = 'FIXED_AMOUNT';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
