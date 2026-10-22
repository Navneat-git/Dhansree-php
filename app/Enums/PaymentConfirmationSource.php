<?php

namespace App\Enums;

enum PaymentConfirmationSource: string
{
    case CHECKOUT = 'CHECKOUT';
    case WEBHOOK = 'WEBHOOK';
    case RECONCILIATION = 'RECONCILIATION';
    case STAFF = 'STAFF';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
