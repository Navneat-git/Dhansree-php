<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case HIDDEN = 'HIDDEN';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
