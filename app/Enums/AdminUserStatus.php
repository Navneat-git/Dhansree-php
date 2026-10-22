<?php

namespace App\Enums;

enum AdminUserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case DISABLED = 'DISABLED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
