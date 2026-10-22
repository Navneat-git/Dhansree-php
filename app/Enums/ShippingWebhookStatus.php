<?php

namespace App\Enums;

enum ShippingWebhookStatus: string
{
    case PROCESSED = 'PROCESSED';
    case IGNORED = 'IGNORED';
    case FAILED = 'FAILED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
