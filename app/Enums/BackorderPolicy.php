<?php

namespace App\Enums;

enum BackorderPolicy: string
{
    case STOP_SALE = 'STOP_SALE';
    case ALLOW_BACKORDER = 'ALLOW_BACKORDER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
