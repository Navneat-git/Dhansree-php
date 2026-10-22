<?php

namespace App\Enums;

enum HomepageSectionKey: string
{
    case HERO = 'HERO';
    case MARQUEE = 'MARQUEE';
    case FEATURES = 'FEATURES';
    case BUDGET = 'BUDGET';
    case BENEFITS = 'BENEFITS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
