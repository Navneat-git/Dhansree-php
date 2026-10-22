<?php

namespace App\Enums;

enum CollectionKind: string
{
    case LISTING = 'LISTING';
    case LANDING = 'LANDING';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
