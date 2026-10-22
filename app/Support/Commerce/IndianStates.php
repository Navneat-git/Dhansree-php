<?php

declare(strict_types=1);

namespace App\Support\Commerce;

final class IndianStates
{
    /**
     * Indian states and union territories with their 2-digit GST state codes.
     *
     * @var array<string, string>
     */
    public const STATES = [
        '01' => 'Jammu and Kashmir',
        '02' => 'Himachal Pradesh',
        '03' => 'Punjab',
        '04' => 'Chandigarh',
        '05' => 'Uttarakhand',
        '06' => 'Haryana',
        '07' => 'Delhi',
        '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh',
        '10' => 'Bihar',
        '11' => 'Sikkim',
        '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland',
        '14' => 'Manipur',
        '15' => 'Mizoram',
        '16' => 'Tripura',
        '17' => 'Meghalaya',
        '18' => 'Assam',
        '19' => 'West Bengal',
        '20' => 'Jharkhand',
        '21' => 'Odisha',
        '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh',
        '24' => 'Gujarat',
        '26' => 'Dadra and Nagar Haveli and Daman and Diu',
        '27' => 'Maharashtra',
        '29' => 'Karnataka',
        '30' => 'Goa',
        '31' => 'Lakshadweep',
        '32' => 'Kerala',
        '33' => 'Tamil Nadu',
        '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
        '38' => 'Ladakh',
    ];

    /**
     * Get all states mapped as code => name.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::STATES;
    }

    /**
     * Get state name by GST code.
     */
    public static function getName(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        return self::STATES[$code] ?? null;
    }

    /**
     * Check if a state code is valid.
     */
    public static function isValid(string $code): bool
    {
        return isset(self::STATES[$code]);
    }
}
