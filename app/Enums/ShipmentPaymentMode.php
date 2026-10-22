<?php

namespace App\Enums;

enum ShipmentPaymentMode: string
{
    case COD = 'COD';
    case PREPAID = 'PREPAID';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
