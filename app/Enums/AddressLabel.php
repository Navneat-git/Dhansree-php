<?php

namespace App\Enums;

enum AddressLabel: string
{
    case HOME = 'HOME';
    case WORK = 'WORK';
    case OTHER = 'OTHER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
