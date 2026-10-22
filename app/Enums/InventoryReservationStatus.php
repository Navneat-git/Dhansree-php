<?php

namespace App\Enums;

enum InventoryReservationStatus: string
{
    case ACTIVE = 'ACTIVE';
    case RELEASED = 'RELEASED';
    case DEDUCTED = 'DEDUCTED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
