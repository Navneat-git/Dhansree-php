<?php

namespace App\Enums;

enum EmailType: string
{
    case ORDER_CONFIRMATION = 'ORDER_CONFIRMATION';
    case PAYMENT_CONFIRMATION = 'PAYMENT_CONFIRMATION';
    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case ORDER_CANCELLED = 'ORDER_CANCELLED';
    case SHIPMENT_DISPATCHED = 'SHIPMENT_DISPATCHED';
    case SHIPMENT_DELIVERED = 'SHIPMENT_DELIVERED';
    case LOGIN_CODE = 'LOGIN_CODE';
    case NEWSLETTER_CONFIRM = 'NEWSLETTER_CONFIRM';
    case NEWSLETTER_WELCOME = 'NEWSLETTER_WELCOME';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
