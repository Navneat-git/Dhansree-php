<?php

namespace App\Enums;

enum CustomerSessionRevokeReason: string
{
    case LOGOUT = 'LOGOUT';
    case IDLE_TIMEOUT = 'IDLE_TIMEOUT';
    case EXPIRED = 'EXPIRED';
    case ACCOUNT_DISABLED = 'ACCOUNT_DISABLED';
    case REVOKED_BY_ADMIN = 'REVOKED_BY_ADMIN';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
