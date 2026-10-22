<?php

namespace App\Enums;

enum CollectionSlideKind: string
{
    case OFFER = 'OFFER';
    case PRODUCTS = 'PRODUCTS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
