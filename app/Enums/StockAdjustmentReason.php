<?php

namespace App\Enums;

enum StockAdjustmentReason: string
{
    case RECEIVED = 'RECEIVED';
    case COUNT_CORRECTION = 'COUNT_CORRECTION';
    case DAMAGED = 'DAMAGED';
    case LOST = 'LOST';
    case RETURNED_TO_SUPPLIER = 'RETURNED_TO_SUPPLIER';
    case OTHER = 'OTHER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
