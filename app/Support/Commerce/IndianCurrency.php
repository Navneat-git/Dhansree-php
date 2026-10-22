<?php

declare(strict_types=1);

namespace App\Support\Commerce;

class IndianCurrency
{
    private static array $units = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];

    private static array $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    ];

    /**
     * Convert integer paise to Indian Rupee Words.
     * E.g. 549900 => "Indian Rupees Five Thousand Four Hundred Ninety-Nine Only"
     */
    public static function formatWords(int $paise): string
    {
        $rupees = (int) floor($paise / 100);
        $remainderPaise = $paise % 100;

        if ($rupees === 0 && $remainderPaise === 0) {
            return 'Zero Rupees Only';
        }

        $rupeeStr = self::convertNumber($rupees);
        $result = 'Indian Rupees ' . ($rupeeStr ?: 'Zero');

        if ($remainderPaise > 0) {
            $paiseStr = self::convertNumber($remainderPaise);
            $result .= ' and ' . $paiseStr . ' Paise';
        }

        return trim($result) . ' Only';
    }

    private static function convertNumber(int $num): string
    {
        if ($num === 0) {
            return '';
        }

        if ($num < 20) {
            return self::$units[$num];
        }

        if ($num < 100) {
            $ten = (int) floor($num / 10);
            $unit = $num % 10;
            return self::$tens[$ten] . ($unit > 0 ? '-' . self::$units[$unit] : '');
        }

        if ($num < 1000) {
            $hundred = (int) floor($num / 100);
            $rem = $num % 100;
            return self::$units[$hundred] . ' Hundred' . ($rem > 0 ? ' and ' . self::convertNumber($rem) : '');
        }

        if ($num < 100000) { // Thousands (up to 99 thousand)
            $thousand = (int) floor($num / 1000);
            $rem = $num % 1000;
            return self::convertNumber($thousand) . ' Thousand' . ($rem > 0 ? ' ' . self::convertNumber($rem) : '');
        }

        if ($num < 10000000) { // Lakhs (up to 99 lakh)
            $lakh = (int) floor($num / 100000);
            $rem = $num % 100000;
            return self::convertNumber($lakh) . ' Lakh' . ($rem > 0 ? ' ' . self::convertNumber($rem) : '');
        }

        // Crores
        $crore = (int) floor($num / 10000000);
        $rem = $num % 10000000;
        return self::convertNumber($crore) . ' Crore' . ($rem > 0 ? ' ' . self::convertNumber($rem) : '');
    }
}
