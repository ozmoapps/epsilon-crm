<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocationController extends Controller
{
    /**
     * Store (Create/Update) an allocation.
     * POST /payments/{payment}/allocations
     */
    public function store(Request $request, Payment $payment)
    {
        // TR sayı formatı normalize (50,30 -> 50.30)
        $request->merge([
            'amount' => $this->normalizeDecimalInput($request->input('amount')),
        ]);

        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Safety: Invoice'e bağlı tahsilat dağıtıma girmemeli (Advance flow için)
        if (!is_null($payment->invoice_id)) {
            throw ValidationException::withMessages([
                'invoice_id' => __('Faturaya bağlı tahsilatlar dağıtılamaz. Bu ekran yalnızca avans tahsilatlar içindir.'),
            ]);
        }

        return DB::transaction(function () use ($request, $payment) {
            // Lock payment to prevent race conditions on balance
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            // Lock invoice to prevent race conditions on status update
            $invoice = Invoice::where('id', $request->invoice_id)->lockForUpdate()->firstOrFail();

            $amountToAllocate = (float) $request->amount;

            // 1) Customer Check: Advance payment customer MUST match invoice customer
            $paymentCustomerId = $payment->customer_id; // advance payments must have customer_id

            if (!$paymentCustomerId || (int)$paymentCustomerId !== (int)$invoice->customer_id) {
                throw ValidationException::withMessages([
                    'invoice_id' => __('Bu fatura, ödemenin yapıldığı müşteriye ait değil.'),
                ]);
            }

            // 2) Currency Check: Payment effective currency must match invoice currency
            $paymentCurrency = $payment->effective_currency;
            if ($paymentCurrency !== $invoice->currency) {
                throw ValidationException::withMessages([
                    'invoice_id' => __("Para birimi uyuşmazlığı. Ödeme (:pCurr), Fatura (:iCurr).", [
                        'pCurr' => $paymentCurrency,
                        'iCurr' => $invoice->currency,
                    ]),
                ]);
            }

            // 3) Payment Balance Check (robust):
            // DB'den allocation sum al (accessor relation lazy-load riskini azaltır)
            $existing = PaymentAllocation::where('payment_id', $payment->id)
                ->where('invoice_id', $invoice->id)
                ->first();

            $currentAllocatedAmount = $existing ? (float)$existing->amount : 0.0;

            $allocSum = (float) PaymentAllocation::where('payment_id', $payment->id)->sum('amount');

            // Avanslarda kontrat gereği base: original_amount (bugün amount==original_amount ama future-proof)
            $paymentBase = (float) ($payment->original_amount ?? $payment->amount);

            // available = base - (all_allocations - current_alloc_of_this_invoice)
            $availableInPayment = max(0, $paymentBase - ($allocSum - $currentAllocatedAmount));

            if ($amountToAllocate > $availableInPayment + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => __("Yetersiz bakiye. Dağıtılabilir avans tutarı: :amount :currency", [
                        'amount' => number_format($availableInPayment, 2, ',', '.'),
                        'currency' => $paymentCurrency,
                    ]),
                ]);
            }

            // 4) Invoice Remaining Limit Check (prevent overpayment)
            $legacyPaid = (float) $invoice->payments()->sum('amount');

            // Bu ödeme için mevcut allocation'ı hariç tut (update senaryosunda doğru limit)
            $allocPaidOthers = (float) $invoice->paymentAllocations()
                ->where('payment_id', '!=', $payment->id)
                ->sum('amount');

            $totalPaidOthers = $legacyPaid + $allocPaidOthers;
            $invoiceRemaining = max(0, (float)$invoice->total - $totalPaidOthers);

            if ($amountToAllocate > $invoiceRemaining + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => __("Fatura tutarını aşamazsınız. Fatura için kalan: :amount :currency", [
                        'amount' => number_format($invoiceRemaining, 2, ',', '.'),
                        'currency' => $invoice->currency,
                    ]),
                ]);
            }

            // 5) Upsert Allocation
            PaymentAllocation::updateOrCreate(
                ['payment_id' => $payment->id, 'invoice_id' => $invoice->id],
                ['amount' => $amountToAllocate]
            );

            // 6) Update Invoice Status
            // 6) Update Invoice Status
            app(\App\Services\InvoiceStatusService::class)->recompute($invoice);

            return back()->with('success', __('Ödeme faturaya dağıtıldı.'));
        });
    }

    /**
     * Remove an allocation.
     * DELETE /payments/{payment}/allocations/{allocation}
     */
    public function destroy(Payment $payment, PaymentAllocation $allocation)
    {
        if ((int)$allocation->payment_id !== (int)$payment->id) {
            abort(404);
        }

        return DB::transaction(function () use ($allocation) {
            $invoice = Invoice::where('id', $allocation->invoice_id)->lockForUpdate()->firstOrFail();

            $allocation->delete();

            app(\App\Services\InvoiceStatusService::class)->recompute($invoice);

            return back()->with('success', __('Dağıtım kaldırıldı.'));
        });
    }

    /**
     * Bulk Store (Multi-Allocate)
     * POST /payments/{payment}/allocations/bulk
     * Sprint 3.2
     */
    public function storeBulk(Request $request, Payment $payment)
    {
        // 1) Input Parsing
        $rawInvoices = $request->input('allocations.invoice_id', []);
        $rawAmounts = $request->input('allocations.amount', []);
        $mode = $request->input('mode', 'replace');

        $allocations = [];
        foreach ($rawInvoices as $idx => $invId) {
            $amtStr = $rawAmounts[$idx] ?? 0;
            $amt = (float) $this->normalizeDecimalInput($amtStr);

            if ($amt > 0.001) {
                // If distinct invoice_id repeated, sum up or take last?
                // Taking last is safer for specific input row mapping, or Sum if UI creates multiple rows for same invoice.
                // Let's assume UI prevents duplicates or we take last for simplicity.
                // Actually, let's SUM if duplicates exist to be robust.
                if (!isset($allocations[$invId])) {
                    $allocations[$invId] = 0.0;
                }
                $allocations[$invId] += $amt;
            }
        }

        return DB::transaction(function () use ($payment, $allocations, $mode) {
            // Lock payment
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            // 1) Validation: Payment Customer
            $paymentCustomerId = $payment->customer_id;
            if (!$paymentCustomerId) {
                 throw ValidationException::withMessages(['base' => __('Bu tahsilatın müşterisi belirsiz.')]);
            }

            // 2) Validation: Payment Total Limit
            $paymentBase = (float) ($payment->original_amount ?? $payment->amount);
            $totalNewAlloc = array_sum($allocations);

            // Note: In 'replace' mode, we ignore old allocations EXCEPT those not touched if we weren't replacing.
            // But we ARE replacing. So total used = sum(new allocations).
            // Be careful if we support 'append' mode later. For now 'replace' is all-or-nothing for this payment.
            
            // Wait, if mode is replace, we remove ALL old allocations for this payment?
            // Yes, effectively rewriting the plan.
            // So available = paymentBase.
            
            if ($totalNewAlloc > $paymentBase + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => __("Toplam dağıtılan tutar (:total), tahsilat tutarını (:base) aşamaz.", [
                        'total' => number_format($totalNewAlloc, 2, ',', '.'),
                        'base' => number_format($paymentBase, 2, ',', '.')
                    ])
                ]);
            }

            // 3) Process Allocations
            $touchedInvoiceIds = [];
            $paymentCurrency = $payment->effective_currency;

            // Existing allocations (to delete untouched ones if replace)
            $existingAllocs = PaymentAllocation::where('payment_id', $payment->id)->get();
            $existingInvoiceIds = $existingAllocs->pluck('invoice_id')->toArray();

            // Lock Invoices involved
            $targetInvoiceIds = array_keys($allocations);
            // We need to lock ALL currently allocated invoices too, to safely update/revert their statuses.
            $allRelatedInvoiceIds = array_unique(array_merge($targetInvoiceIds, $existingInvoiceIds));

            $invoices = Invoice::whereIn('id', $allRelatedInvoiceIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($allocations as $invId => $amount) {
                $invoice = $invoices->get($invId);
                if (!$invoice) {
                    throw ValidationException::withMessages(["invoice_{$invId}" => "Fatura bulunamadı (ID: $invId)"]);
                }

                // Check Customer
                if ((int)$invoice->customer_id !== (int)$paymentCustomerId) {
                    throw ValidationException::withMessages(["invoice_{$invId}" => "Fatura müşterisi uyuşmuyor: {$invoice->invoice_no}"]);
                }
                // Check Currency
                if ($invoice->currency !== $paymentCurrency) {
                    throw ValidationException::withMessages(["invoice_{$invId}" => "Para birimi uyuşmuyor: {$invoice->invoice_no} ($invoice->currency vs $paymentCurrency)"]);
                }

                // Check Invoice Remaining Limit
                // remaining = total - (legacyPaid + allocPaidOthers)
                // We exclude THIS payment's allocation beause we are setting it fresh.
                $legacyPaid = (float) $invoice->payments()->sum('amount');
                $allocPaidOthers = (float) $invoice->paymentAllocations()
                    ->where('payment_id', '!=', $payment->id)
                    ->sum('amount');
                
                $remaining = max(0, (float)$invoice->total - $legacyPaid - $allocPaidOthers);

                if ($amount > $remaining + 0.01) {
                     throw ValidationException::withMessages([
                        "invoice_{$invId}" => __(":invNo nolu fatura için tutar (:amt), kalan tutarı (:rem) aşıyor.", [
                            'invNo' => $invoice->invoice_no,
                            'amt' => number_format($amount, 2, ',', '.'),
                            'rem' => number_format($remaining, 2, ',', '.')
                        ])
                    ]);
                }

                // Upsert Allocation
                PaymentAllocation::updateOrCreate(
                    ['payment_id' => $payment->id, 'invoice_id' => $invId],
                    ['amount' => $amount]
                );
                
                $touchedInvoiceIds[] = $invId;
            }

            // 4) Clean up old allocations?
            if ($mode === 'replace') {
                // Delete allocations for this payment where invoice_id NOT in $targetInvoiceIds
                 $toDelete = $existingAllocs->whereNotIn('invoice_id', $targetInvoiceIds);
                 foreach ($toDelete as $del) {
                     $del->delete();
                     $touchedInvoiceIds[] = $del->invoice_id; // Need to recalc status for deleted ones
                 }
            }

            // 5) Recompute Statuses
            $touchedInvoiceIds = array_unique($touchedInvoiceIds);
            foreach ($touchedInvoiceIds as $tid) {
                if ($inv = $invoices->get($tid)) {
                    // Safe to call on object because we locked it
                    $inv->refresh(); // refresh relations (payments/allocations)
                    app(\App\Services\InvoiceStatusService::class)->recompute($inv);
                }
            }

            return back()->with('success', __('Dağıtım planı güncellendi.'));
        });
    }

    /**
     * Recompute and update the payment status of an invoice.
     */
    /**
     * Recompute and update the payment status of an invoice.
     * DEPRECATED: Use InvoiceStatusService
     */
    /*
    protected function recomputeInvoiceStatus(Invoice $invoice)
    {
        // ... logic moved to InvoiceStatusService
    }
    */

    /**
     * Normalize TR formatted decimals to dot-decimal numeric string.
     * Examples:
     *  - "2.393.449,73" => "2393449.73"
     *  - "50,30" => "50.30"
     */
    private function normalizeDecimalInput($value): ?string
    {
        if ($value === null) return null;

        $s = trim((string) $value);
        if ($s === '') return null;

        $s = str_replace(' ', '', $s);

        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return $s;
    }
}
