<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'COD';
    case ONLINE = 'ONLINE';

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Cash on Delivery',
            self::ONLINE => 'Online Payment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
