<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case FAILED = 'FAILED';
    case VOIDED = 'VOIDED';
    case REFUNDED = 'REFUNDED';
    case AUTHORIZED = 'AUTHORIZED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
