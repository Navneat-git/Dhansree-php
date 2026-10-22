<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case ADJUSTMENT = 'ADJUSTMENT';
    case RESERVATION = 'RESERVATION';
    case RELEASE = 'RELEASE';
    case DEDUCTION = 'DEDUCTION';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
