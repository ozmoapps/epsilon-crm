<?php

namespace App\Services;

use App\Support\MoneyMath;

class TotalsCalculator
{
    public function calculate(iterable $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $vatTotal = 0;
        $grandTotal = 0;

        foreach ($items as $item) {
            // Skip optional items completely
            if ($item->is_optional) {
                continue;
            }
            
            // Map item fields: qty, unit_price, discount_amount, vat_rate
            $qty = MoneyMath::decimalToScaledInt($item->qty);
            $unitPrice = MoneyMath::decimalToScaledInt($item->unit_price);
            $discountAmount = MoneyMath::decimalToScaledInt($item->discount_amount ?? 0);
            $vatRate = MoneyMath::percentToBasisPoints($item->vat_rate ?? 0);

            $line = MoneyMath::calculateLineCents($qty, $unitPrice, $discountAmount, $vatRate);

            $subtotal += $line['base_cents'];
            $discountTotal += $line['discount_cents'];
            $vatTotal += $line['vat_cents'];
            // Grand total is sum of line totals (or net + vat totals)
            // Mathematically sum(total) = sum(net) + sum(vat)
            $grandTotal += $line['total_cents'];
        }

        return [
            'subtotal' => MoneyMath::centsToDecimalString($subtotal),
            'discount_total' => MoneyMath::centsToDecimalString($discountTotal),
            'vat_total' => MoneyMath::centsToDecimalString($vatTotal),
            'grand_total' => MoneyMath::centsToDecimalString($grandTotal),
        ];
    }
}
