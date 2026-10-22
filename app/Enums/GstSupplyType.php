<?php

namespace App\Enums;

enum GstSupplyType: string
{
    case INTRA_STATE = 'INTRA_STATE';
    case INTER_STATE = 'INTER_STATE';
    case NO_GST = 'NO_GST';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
