<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\GstRegistrationType;
use App\Enums\GstSupplyType;
use App\Models\StoreSettings;

class TaxService
{
    /**
     * Determine GST supply type for a customer destination.
     */
    public function resolveSupplyType(StoreSettings $store, ?string $destinationStateCode): GstSupplyType
    {
        if ($store->gst_registration_type !== GstRegistrationType::REGULAR) {
            return GstSupplyType::NO_GST;
        }

        if (!$destinationStateCode || $destinationStateCode === $store->state_code) {
            return GstSupplyType::INTRA_STATE;
        }

        return GstSupplyType::INTER_STATE;
    }

    /**
     * Calculate tax breakdown for an amount and GST rate.
     *
     * @return array{
     *     taxable_paise: int,
     *     tax_paise: int,
     *     cgst_paise: int,
     *     sgst_paise: int,
     *     igst_paise: int,
     *     total_paise: int
     * }
     */
    public function calculateLineTax(
        int $amountPaise,
        int $gstRateBps,
        GstSupplyType $supplyType,
        bool $pricesIncludeTax = true
    ): array {
        if ($supplyType === GstSupplyType::NO_GST || $gstRateBps <= 0 || $amountPaise <= 0) {
            return [
                'taxable_paise' => $amountPaise,
                'tax_paise' => 0,
                'cgst_paise' => 0,
                'sgst_paise' => 0,
                'igst_paise' => 0,
                'total_paise' => $amountPaise,
            ];
        }

        if ($pricesIncludeTax) {
            // Taxable = Amount * 10000 / (10000 + gstRateBps)
            $taxable = (int) round(($amountPaise * 10000) / (10000 + $gstRateBps));
            $tax = $amountPaise - $taxable;
            $total = $amountPaise;
        } else {
            $taxable = $amountPaise;
            $tax = (int) round(($taxable * $gstRateBps) / 10000);
            $total = $taxable + $tax;
        }

        $cgst = 0;
        $sgst = 0;
        $igst = 0;

        if ($supplyType === GstSupplyType::INTRA_STATE) {
            $cgst = (int) intdiv($tax, 2);
            $sgst = $tax - $cgst;
        } elseif ($supplyType === GstSupplyType::INTER_STATE) {
            $igst = $tax;
        }

        return [
            'taxable_paise' => $taxable,
            'tax_paise' => $tax,
            'cgst_paise' => $cgst,
            'sgst_paise' => $sgst,
            'igst_paise' => $igst,
            'total_paise' => $total,
        ];
    }
}
