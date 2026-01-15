<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\LedgerEntry;

class LedgerService
{
    private function isDuplicateLedgerEntry(\Illuminate\Database\QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->getCode();
        $message = $e->getMessage();

        if ($sqlState === '23505') return true;
        if ($sqlState !== '23000') return false;
        if ($errorCode === 1062) return true;

        if (str_contains($message, 'ledger_unique_entry')) return true;
        if (str_contains($message, 'UNIQUE constraint failed')) return true;

        return false;
    }

    public function createDebitFromInvoice(Invoice $invoice, ?int $createdBy = null): ?LedgerEntry
    {
        $vesselId = $invoice->salesOrder?->vessel_id;

        try {
            return LedgerEntry::create([
                'customer_id' => $invoice->customer_id,
                'vessel_id' => $vesselId,
                'type' => 'invoice',
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'direction' => 'debit',
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'occurred_at' => $invoice->issue_date ?? now(),
                'description' => 'Fatura No: ' . ($invoice->invoice_no ?? '-'),
                'created_by' => $createdBy ?? auth()->id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDuplicateLedgerEntry($e)) return null;
            throw $e;
        }
    }

    /**
     * Single Source of Truth:
     * - Invoice-linked payments: ledger currency = invoice currency, amount = payment.amount (equivalent)
     * - Advance payments (invoice_id null): ledger currency = payment.original_currency, amount = payment.original_amount
     * - Optional vesselId for advances (or to override if needed)
     */
    public function createCreditFromPayment(Payment $payment, ?int $createdBy = null, ?int $vesselId = null): ?LedgerEntry
    {
        $invoice = $payment->invoice;

        $customerId = $payment->customer_id ?? $invoice?->customer_id;
        if (!$customerId) {
            return null;
        }

        // Vessel precedence: explicit param > invoice->salesOrder->vessel_id
        $resolvedVesselId = $vesselId;
        if (!$resolvedVesselId && $invoice) {
            $resolvedVesselId = $invoice->salesOrder?->vessel_id;
        }

        // Decide ledger currency/amount based on whether payment is linked to invoice or is advance.
        if ($invoice) {
            $invoiceCurrency = $invoice->currency ?? 'EUR';

            $origAmount = $payment->original_amount ?? $payment->amount;
            $origCurr = $payment->original_currency ?? $invoiceCurrency;
            $fxRate = $payment->fx_rate ?? 1.0;

            $desc = "Tahsilat: " . number_format((float)$origAmount, 2) . " " . $origCurr;

            if ($origCurr !== $invoiceCurrency) {
                $fx = $this->formatFxRate($fxRate);
                $desc .= " | Kur: 1 {$invoiceCurrency} = {$fx} {$origCurr}";
                $desc .= " | Eşdeğer: " . number_format((float)$payment->amount, 2) . " {$invoiceCurrency}";
            }

            if (!empty($payment->payment_method)) {
                $desc .= ' (' . $payment->payment_method . ')';
            }
            if (!empty($payment->reference_number)) {
                $desc .= ' - Ref: ' . $payment->reference_number;
            }

            try {
                return LedgerEntry::create([
                    'customer_id' => $customerId,
                    'vessel_id' => $resolvedVesselId,
                    'type' => 'payment',
                    'source_type' => Payment::class,
                    'source_id' => $payment->id,
                    'direction' => 'credit',
                    'amount' => $payment->amount,          // invoice equivalent
                    'currency' => $invoiceCurrency,         // ledger currency is invoice currency
                    'occurred_at' => $payment->payment_date ?? now(),
                    'description' => $desc,
                    'created_by' => $createdBy ?? auth()->id(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($this->isDuplicateLedgerEntry($e)) return null;
                throw $e;
            }
        }

        // ADVANCE (invoice_id null)
        $advAmount = $payment->original_amount ?? $payment->amount;
        $advCurrency = $payment->original_currency ?? $payment->effective_currency ?? 'EUR';

        $desc = "Avans: " . number_format((float)$advAmount, 2) . " " . $advCurrency;

        if (!empty($payment->payment_method)) {
            $desc .= ' (' . $payment->payment_method . ')';
        }
        if (!empty($payment->reference_number)) {
            $desc .= ' - Ref: ' . $payment->reference_number;
        }
        if (!empty($payment->notes)) {
            $notes = trim((string)$payment->notes);
            if ($notes !== '') {
                $desc .= ' | ' . $notes;
            }
        }

        try {
            return LedgerEntry::create([
                'customer_id' => $customerId,
                'vessel_id' => $resolvedVesselId,
                'type' => 'payment',
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'direction' => 'credit',
                'amount' => $advAmount,                 // original advance amount
                'currency' => $advCurrency,             // original advance currency
                'occurred_at' => $payment->payment_date ?? now(),
                'description' => $desc,
                'created_by' => $createdBy ?? auth()->id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDuplicateLedgerEntry($e)) return null;
            throw $e;
        }
    }

    private function formatFxRate($fxRate): string
    {
        // Standardize to 8 decimals but trim trailing zeros for readability
        $s = number_format((float)$fxRate, 8, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return $s === '' ? '1' : $s;
    }
}
