<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentAutoAllocator
{
    /**
     * Automatically allocate the given payment to available invoices.
     *
     * @param Payment $payment
     * @return array Result summary
     */
    public function allocateForPayment(Payment $payment): array
    {
        return DB::transaction(function () use ($payment) {
            // Lock payment to ensure strict consistency
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (!$payment) {
                return [
                    'paid_count' => 0,
                    'partial_count' => 0,
                    'allocated_total' => 0,
                    'payment_remaining' => 0,
                    'currency' => 'N/A'
                ];
            }
            
            // Guard Rail: Standard payments (linked to an invoice) should not have allocations.
            // They are implicitly allocated to their linked invoice.
            if ($payment->invoice_id) {
                return [
                    'paid_count' => 0,
                    'partial_count' => 0,
                    'allocated_total' => 0,
                    'payment_remaining' => 0,
                    'currency' => $payment->invoice->currency ?? 'N/A'
                ];
            }

            $customerId = $payment->customer_id;
            $currency = $payment->effective_currency;

            // Only proceed if we have a customer and currency
            if (!$customerId || !$currency) {
                return [
                    'paid_count' => 0,
                    'partial_count' => 0,
                    'allocated_total' => 0,
                    'payment_remaining' => $payment->amount, // assuming simple amount for now
                    'currency' => $currency
                ];
            }

            // Find candidate invoices: issued, not paid, same customer, same currency
            // Order by Due Date ASC -> Issue Date ASC -> ID ASC (FIFO)
            $invoices = Invoice::where('customer_id', $customerId)
                ->where('status', 'issued')
                ->where('payment_status', '!=', 'paid')
                ->where('currency', $currency)
                ->orderBy('due_date')
                ->orderBy('issue_date')
                ->orderBy('id')
                ->lockForUpdate() // Lock invoices for processing
                ->get();

            // Calculate Payment Remaining
            // Allocations are usually 0 at this point for a new payment, but we calculate safely
            $allocatedSum = (float) PaymentAllocation::where('payment_id', $payment->id)->sum('amount');

            // Payment Base: For advances, we use original_amount or amount
            // As per PaymentController, storeAdvance sets amount = original_amount = input
            $paymentBase = (float) ($payment->original_amount ?? $payment->amount);

            $paymentRemaining = max(0, $paymentBase - $allocatedSum);

            $paidCount = 0;
            $partialCount = 0;
            $allocatedTotal = 0;

            foreach ($invoices as $invoice) {
                if ($paymentRemaining <= 0.001) {
                    break;
                }

                // Idempotency guard: if allocation already exists for this (payment, invoice), do not overwrite
                $existingAlloc = PaymentAllocation::where('payment_id', $payment->id)
                    ->where('invoice_id', $invoice->id)
                    ->first();

                if ($existingAlloc) {
                    continue;
                }

                // Calculate Invoice Remaining
                $legacyPaid = (float) $invoice->payments()->sum('amount');

                // Double-count guard: only count allocations that belong to ADVANCE payments (payments.invoice_id IS NULL)
                $allocPaid = $this->sumAllocPaidGuardedForInvoice($invoice->id);

                // Invoice total
                $invTotal = (float) $invoice->total;
                $invRemaining = max(0, $invTotal - ($legacyPaid + $allocPaid));

                if ($invRemaining <= 0.001) {
                    // Start of edge case: invoice status says partial/unpaid but math says 0?
                    // Just update status to paid and continue
                    $this->recomputeInvoiceStatus($invoice);
                    continue;
                }

                // Determine allocation amount
                $allocateAmount = min($paymentRemaining, $invRemaining);

                if ($allocateAmount > 0.001) {
                    // Create Allocation (do not overwrite existing)
                    try {
                        PaymentAllocation::create([
                            'payment_id' => $payment->id,
                            'invoice_id' => $invoice->id,
                            'amount' => $allocateAmount,
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // If another process created it in the meantime, ignore; otherwise rethrow
                        if (!$this->isDuplicateAllocation($e)) {
                            throw $e;
                        }
                    }

                    $allocatedTotal += $allocateAmount;
                    $paymentRemaining -= $allocateAmount;

                    // Update Invoice Status
                    $status = $this->recomputeInvoiceStatus($invoice);

                    if ($status === 'paid') {
                        $paidCount++;
                    } else {
                        $partialCount++;
                    }
                }
            }

            return [
                'paid_count' => $paidCount,
                'partial_count' => $partialCount,
                'allocated_total' => $allocatedTotal,
                'payment_remaining' => number_format($paymentRemaining, 2),
                'currency' => $currency
            ];
        });
    }

    /**
     * Double-Count Guard helper:
     * Only count allocations coming from ADVANCE payments (payments.invoice_id IS NULL).
     */
    protected function sumAllocPaidGuardedForInvoice(int $invoiceId): float
    {
        if (!Schema::hasTable('payment_allocations')) {
            return 0.0;
        }

        return (float) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->where('payment_allocations.invoice_id', $invoiceId)
            ->whereNull('payments.invoice_id')
            ->sum('payment_allocations.amount');
    }

    /**
     * Recompute and update the payment status of an invoice.
     * Logic copied from PaymentAllocationController (guarded).
     *
     * @param Invoice $invoice
     * @return string The new status
     */
    protected function recomputeInvoiceStatus(Invoice $invoice)
    {
        $totalLegacyPaid = (float) $invoice->payments()->sum('amount');
        $totalAllocPaid  = $this->sumAllocPaidGuardedForInvoice($invoice->id);
        $totalPaid = $totalLegacyPaid + $totalAllocPaid;

        $invoiceTotal = (float) $invoice->total;

        if ($totalPaid >= $invoiceTotal - 0.01) {
            $status = 'paid';
        } elseif ($totalPaid > 0.01) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        $invoice->update(['payment_status' => $status]);

        return $status;
    }

    protected function isDuplicateAllocation(\Illuminate\Database\QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->getCode();
        $message = $e->getMessage();

        if ($sqlState === '23505') return true; // pg unique
        if ($sqlState !== '23000') return false; // mysql/sqlite unique class
        if ($errorCode === 1062) return true; // mysql duplicate

        if (str_contains($message, 'UNIQUE constraint failed')) return true; // sqlite
        if (str_contains($message, 'Duplicate entry')) return true; // mysql
        if (str_contains($message, 'unique')) return true; // generic-ish

        return false;
    }
}
