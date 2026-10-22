<?php

namespace App\Enums;

enum ShipmentEventSource: string
{
    case WEBHOOK = 'WEBHOOK';
    case SYNC = 'SYNC';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
