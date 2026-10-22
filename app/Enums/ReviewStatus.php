<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case HIDDEN = 'HIDDEN';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Waiting for review',
            self::APPROVED => 'Published',
            self::REJECTED => 'Rejected',
            self::HIDDEN => 'Hidden',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
