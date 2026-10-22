<?php

namespace App\Enums;

enum CouponType: string
{
    case PERCENTAGE = 'PERCENTAGE';
    case FIXED_AMOUNT = 'FIXED_AMOUNT';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage Discount (%)',
            self::FIXED_AMOUNT => 'Fixed Amount Discount (₹)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
