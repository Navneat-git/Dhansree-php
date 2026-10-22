<?php

namespace App\Enums;

enum ContentPageKey: string
{
    case ABOUT = 'ABOUT';
    case CONTACT = 'CONTACT';
    case FAQ = 'FAQ';
    case SHIPPING_POLICY = 'SHIPPING_POLICY';
    case RETURN_POLICY = 'RETURN_POLICY';
    case PRIVACY_POLICY = 'PRIVACY_POLICY';
    case TERMS = 'TERMS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
