<?php

namespace App\Support;

class MoneyMath
{
    /**
     * Normalize a decimal string, handling TR/EU formats.
     * "1.250,50" -> "1250.50"
     * "1250.50"  -> "1250.50"
     * "1,250.50" -> "1250.50"
     *
     * @param mixed $value
     * @param int $scale
     * @param bool $nullIfEmpty
     * @return string|null
     */
    public static function normalizeDecimalString($value, int $scale = 2, bool $nullIfEmpty = false): ?string
    {
        if ($value === null || $value === '') {
            return $nullIfEmpty ? null : number_format(0, $scale, '.', '');
        }

        $str = (string) $value;
        $str = trim($str);

        if ($str === '') {
            return $nullIfEmpty ? null : number_format(0, $scale, '.', '');
        }

        // Logic to detect and clean thousands separator.
        // If we see both . and , -> checking the last one.
        // TR: 1.250,50 -> last is , -> dot is thousand sep
        // US: 1,250.50 -> last is . -> comma is thousand sep

        $firstComma = strpos($str, ',');
        $firstDot = strpos($str, '.');

        if ($firstComma !== false && $firstDot !== false) {
            $lastComma = strrpos($str, ',');
            $lastDot = strrpos($str, '.');

            if ($lastComma > $lastDot) {
                // Formatting is likely 1.250,50 (TR/EU) -> Remove all dots, replace comma with dot
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // Formatting is likely 1,250.50 (US) -> Remove all commas
                $str = str_replace(',', '', $str);
            }
        } elseif ($firstComma !== false) {
            // Only commas present. Could be 1250,50 or 1,000,000
            // If there's more than one comma, it's likely a separator if it's not at the end calc position?
            // Or simpler rule: If strict TR input expected, assume comma is decimal.
            // But let's be safe: If only ONE comma and it's near end (2 chars), it's decimal.
            // If multiple commas, they are separators, except maybe last one?
            // For TR context usually simple: Treat comma as decimal separator if no dots.
            // CAUTION: 1,250 (US int) vs 1,25 (TR decimal).
            // Ambiguity relies on context.
            // Given "TR formatı desteklesin" requirement: 1,50 -> 1.50
            // 1.250 -> 1250
            // If we assume standard input handling:
            // "1.250,50" -> handled above.
            // "1250,50" -> replace , with .
            // "1.250" -> remove dot.
            
            // Heuristic for single separator type:
            // If it is '.', treat as thousands separator if it appears multiple times OR if it is followed by 3 digits and not end of string?
            // Actually, safe bet for TR app:
            // ',' is decimal. '.' is thousands.
            // unless pure dot usage like 10.50 (which is standard code format).
            
            // Let's go with the user prompt hint: "hem . hem , varsa son görüneni decimal say"
            // "sadece , varsa: tüm . sil (yok zaten), , -> ."
            // "sadece . varsa: olduğu gibi bırak" (Wait, 1.250 could be 1250 in TR...)
            
            // User request #5 says for JS: 
            // "sadece ',' varsa: tüm '.' sil, ',' -> '.'"
            // "sadece '.' varsa: olduğu gibi bırak" -> assumes 1.250 is 1.25? Or 1250?
            // Actually, usually in standard libs, if only '.' is present, it is decimal. 
            // If only ',' is present, it is often decimal in TR/EU.
            
            // Let's implement robustly:
            $str = str_replace(',', '.', $str);
        }

        // Final cleanup for non-numeric/dots
        // Note: we might have converted , to . above.
        // We should ensure only one dot exists and no other chars.
        // But str_replace logic above assumes clean string.
        
        // Let's adhere strictly to:
        // Input: "1.250,50"
        // 1. Remove thousands (dots)
        // 2. Replace decimal (comma) with dot
        
        // If "1250.50" (already normalized) -> kept as is.
        
        // Implementation:
        // Use regex or logic similar to the JS requirement provided by user for the frontend? 
        // Backend should be robust.
        
        // Let's re-implement checking just the characters.
        $hasDot = str_contains($value, '.');
        $hasComma = str_contains($value, ',');
        
        $clean = (string) $value;
        
        if ($hasDot && $hasComma) {
             // 1.250,50 or 1,250.50
             $lastDot = strrpos($value, '.');
             $lastComma = strrpos($value, ',');
             
             if ($lastComma > $lastDot) {
                 // TR style: remove dots, swap comma
                 $clean = str_replace('.', '', $clean);
                 $clean = str_replace(',', '.', $clean);
             } else {
                 // US style: remove commas
                 $clean = str_replace(',', '', $clean);
             }
        } elseif ($hasComma) {
            // Only comma: 1250,50 -> 1250.50 (TR decimal)
            // or 1,000 (US 1000). 
            // In TR app context, comma is almost always decimal separator.
            $clean = str_replace(',', '.', $clean);
        } elseif ($hasDot) {
             // Only dot: 1250.50 -> 1250.50
             // or 1.250 (TR 1250).
             // However, PHP/SQL float string default is dot. 
             // We will assume dot is decimal separator if only dot exists, to support "1200.00".
             // (If user enters 1.250 meaning 1250, they should use 1.250,00 or simple 1250).
             // We'll trust that dot-only is standard float string.
        }

        // Filter non-numeric chars except dot and minus
        $clean = preg_replace('/[^0-9.-]/', '', $clean);
        
        if (!is_numeric($clean)) {
            return $nullIfEmpty ? null : number_format(0, $scale, '.', '');
        }

        return number_format((float) $clean, $scale, '.', '');
    }

    public static function decimalToScaledInt($value, int $scale = 2): int
    {
        if (is_null($value)) {
            return 0;
        }
        
        // Normalize first to ensure standard valid float string
        $normalized = self::normalizeDecimalString($value, $scale);
        
        // Multiply by 10^scale and round
        return (int) round((float) $normalized * (10 ** $scale));
    }

    public static function percentToBasisPoints($value): int
    {
        return self::decimalToScaledInt($value, 2);
    }
    
    public static function divRoundHalfUp(int $num, int $den): int
    {
        if ($den === 0) return 0;
        
        // integer division with rounding half-up
        // formula: floor((2 * num + den) / (2 * den)) 
        // works for probability, but simpler: (int) round($num / $den)
        // Since input is int, we can't just divide.
        
        if ($num === 0) return 0;
        
        $sign = ($num <=> 0) * ($den <=> 0);
        $num = abs($num);
        $den = abs($den);
        
        // round($a / $b) = floor(($a + $b/2) / $b) -> floor((2*$a + $b) / (2*$b))
        $result = (int) floor((2 * $num + $den) / (2 * $den));
        
        return $sign * $result;
    }

    public static function calculateLineCents(int $qtyHundredths, int $unitCents, int $discountCents, int $vatBasisPoints): array
    {
        // 1. Base Cents = (Qty * UnitPrice)
        // Qty is scaled by 100 (e.g. 1.50 -> 150). UnitPrice is scaled by 100 (e.g. 10.00 -> 1000).
        // Result scale: 100 * 100 = 10000.
        // We want final result in cents (scale 100).
        // So we divide by 100.
        
        // Use float for intermediate multiplication to avoid overflow on very large numbers?
        // PHP int is 64-bit, quite large. 
        // 150 * 1000 = 150000. / 100 = 1500 cents (15.00). Correct. (1.5 * 10 = 15).
        
        $baseRaw = $qtyHundredths * $unitCents; 
        $baseCents = self::divRoundHalfUp($baseRaw, 100); 

        // 2. Net Cents = Base - Discount
        // Discount is fixed amount in cents.
        // Cap discount at base amount.
        $discountCents = min($discountCents, $baseCents);
        $netCents = max(0, $baseCents - $discountCents);

        // 3. VAT Cents = Net * VAT%
        // VAT% is in basis points (18% = 1800). Scale 10000 (100 * 100).
        // net * vatBp -> scale 100 * 100 = 10000.
        // divide by 10000 to get cents.
        $vatRaw = $netCents * $vatBasisPoints;
        $vatCents = self::divRoundHalfUp($vatRaw, 10000);

        $totalCents = $netCents + $vatCents;

        return [
            'base_cents' => $baseCents,
            'discount_cents' => $discountCents,
            'net_cents' => $netCents,
            'vat_cents' => $vatCents,
            'total_cents' => $totalCents,
        ];
    }

    public static function centsToDecimalString(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Format given value to TR format "1.250,50"
     * Handles string, int (cents if specified?), float.
     * Note: If input is int, and we assume it is cents? No, parameter name $decimalLike implies standard numeric.
     * To format cents, use centsToDecimalString first then this, or just pass float.
     */
    public static function formatTR($decimalLike): string
    {
        if (is_null($decimalLike) || $decimalLike === '') {
            return '0,00';
        }
        
        // Ensure float
        $val = (float) $decimalLike;
        
        // TR format: decimals=2, dec_point=',', thousands_sep='.'
        return number_format($val, 2, ',', '.');
    }
}
