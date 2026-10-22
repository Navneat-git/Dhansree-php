<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'PENDING';
    case CREATED = 'CREATED';
    case AWB_ASSIGNED = 'AWB_ASSIGNED';
    case PICKUP_SCHEDULED = 'PICKUP_SCHEDULED';
    case SHIPPED = 'SHIPPED';
    case IN_TRANSIT = 'IN_TRANSIT';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case UNDELIVERED = 'UNDELIVERED';
    case DELIVERED = 'DELIVERED';
    case RTO_IN_TRANSIT = 'RTO_IN_TRANSIT';
    case RTO_DELIVERED = 'RTO_DELIVERED';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
