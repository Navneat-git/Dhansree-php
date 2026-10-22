<?php

namespace App\Enums;

enum OrderActorType: string
{
    case CUSTOMER = 'CUSTOMER';
    case ADMIN = 'ADMIN';
    case SYSTEM = 'SYSTEM';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
