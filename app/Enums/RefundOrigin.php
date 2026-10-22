<?php

namespace App\Enums;

enum RefundOrigin: string
{
    case ADMIN = 'ADMIN';
    case PROVIDER = 'PROVIDER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
