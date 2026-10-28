<?php

namespace App\Enums;

enum HomeBlockType: string
{
    case PROMO_CAROUSEL = 'PROMO_CAROUSEL';
    case HERO = 'HERO';
    case TILE_GRID = 'TILE_GRID';
    case PRODUCT_RAIL = 'PRODUCT_RAIL';
    case PROMO_BANNER = 'PROMO_BANNER';
    case FEATURES = 'FEATURES';
    case INSTAGRAM = 'INSTAGRAM';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
