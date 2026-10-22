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

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::VOIDED => 'Voided',
            self::REFUNDED => 'Refunded',
            self::AUTHORIZED => 'Authorized',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
