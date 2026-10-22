<?php

namespace App\Enums;

enum AdminSessionRevokeReason: string
{
    case LOGOUT = 'LOGOUT';
    case REPLACED = 'REPLACED';
    case IDLE_TIMEOUT = 'IDLE_TIMEOUT';
    case EXPIRED = 'EXPIRED';
    case ACCOUNT_DISABLED = 'ACCOUNT_DISABLED';
    case REVOKED_BY_ADMIN = 'REVOKED_BY_ADMIN';
    case PASSWORD_CHANGED = 'PASSWORD_CHANGED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
