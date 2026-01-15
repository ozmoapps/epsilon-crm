<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceStatusService
{
    /**
     * Recompute and update the payment status of an invoice.
     * Centralized logic to prevent drift between PaymentController and PaymentAllocationController.
     */
    public function recompute(Invoice $invoice): void
    {
        // Guard: Only issued invoices track payment status
        if ($invoice->status !== 'issued') {
            return;
        }

        // 1. Calculate Total Paid
        // Legacy Payments (Direct Invoice link)
        $totalLegacyPaid = (float) $invoice->payments()->sum('amount');
        
        // Allocations (From Advance Payments)
        $totalAllocPaid = (float) $invoice->paymentAllocations()->sum('amount');
        
        $totalPaid = $totalLegacyPaid + $totalAllocPaid;
        $invoiceTotal = (float) $invoice->total;

        // 2. Determine Status
        // Tolerance 0.01 for floating point comparisons
        if ($totalPaid >= $invoiceTotal - 0.01) {
            $status = 'paid';
        } elseif ($totalPaid > 0.01) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        // 3. Update Invoice
        // Only update if status changed to avoid unnecessary DB writes? 
        // For safety/idempotency, we just update.
        $invoice->update(['payment_status' => $status]);
    }
}
