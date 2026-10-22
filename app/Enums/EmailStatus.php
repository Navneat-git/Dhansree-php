<?php

namespace App\Enums;

enum EmailStatus: string
{
    case PENDING = 'PENDING';
    case SENT = 'SENT';
    case FAILED = 'FAILED';
    case SKIPPED = 'SKIPPED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
