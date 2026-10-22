<?php

namespace App\Enums;

enum AuditActorType: string
{
    case ADMIN = 'ADMIN';
    case CUSTOMER = 'CUSTOMER';
    case SYSTEM = 'SYSTEM';
    case ANONYMOUS = 'ANONYMOUS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
