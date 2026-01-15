<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Payments index (placeholder/list for Sprint 2.1)
     */
    public function index(Request $request)
    {
        $query = Payment::query()
            ->with(['invoice', 'customer', 'allocations']); // Eager load allocations & customer

        // Filters
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('currency')) {
            // "Effective Currency" Logic:
            // a) If Advance (invoice_id is null) -> check payments.original_currency
            // b) If Invoice Payment -> check related invoice currency
            $cur = $request->currency;
            $query->where(function ($q) use ($cur) {
                // Case A: Advances
                $q->where(function ($sub) use ($cur) {
                    $sub->whereNull('invoice_id')
                        ->where('original_currency', $cur);
                })
                // Case B: Invoice Payments
                ->orWhereHas('invoice', function ($iq) use ($cur) {
                    $iq->where('currency', $cur);
                });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->boolean('only_open')) {
            $query->whereNull('invoice_id');
            // Ensure belonging to a customer (Advances must have a customer)
            $query->whereNotNull('customer_id');

            // Check remaining amount
            if (\Illuminate\Support\Facades\Schema::hasTable('payment_allocations')) {
                // Correlated subquery for portability
                // QUALIFY columns: payments.amount, payment_allocations.amount
                // HOTFIX: use original_amount fallback or amount
                $query->whereRaw('(coalesce(payments.original_amount, payments.amount) - (select coalesce(sum(payment_allocations.amount), 0) from payment_allocations where payment_allocations.payment_id = payments.id)) > 0.001');
            }
        }

        $payments = $query
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20)
            ->appends($request->query());

        // View Data
        $customers = \App\Models\Customer::orderBy('name')->get(['id', 'name']);

        // Active currencies (fallback to list if table empty or not implies)
        // Guard: Check if currencies table exists AND has is_active column
        $availableCurrencies = collect(['TRY', 'USD', 'EUR', 'GBP']);

        if (\Illuminate\Support\Facades\Schema::hasTable('currencies')) {
            $hasActiveCol = \Illuminate\Support\Facades\Schema::hasColumn('currencies', 'is_active');

            if ($hasActiveCol) {
                $dbCurrencies = \App\Models\Currency::where('is_active', true)->pluck('code');
            } else {
                $dbCurrencies = \App\Models\Currency::pluck('code');
            }

            if ($dbCurrencies->isNotEmpty()) {
                $availableCurrencies = $dbCurrencies;
            }
        }

        return view('payments.index', compact('payments', 'customers', 'availableCurrencies'));
    }

    /**
     * Show the form for creating a new resource (Advance Payment).
     */
    public function create()
    {
        // Simple View for Advance Payment
        return view('payments.create', [
            // V2: avoid N+1 in view (acc->currency)
            'bankAccounts' => \App\Models\BankAccount::with('currency')->where('is_active', true)->get(),
            'customers' => \App\Models\Customer::orderBy('name')->get(['id', 'name']),
            'vessels' => \App\Models\Vessel::orderBy('name')->get(['id', 'name', 'customer_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage (Advance Payment).
     */
    public function storeAdvance(Request $request, \App\Services\LedgerService $ledgerService, \App\Services\PaymentAutoAllocator $allocator)
    {
        // Normalize TR number formats
        $request->merge([
            'amount' => $this->normalizeDecimalInput($request->input('amount')),
        ]);

        // Idempotency Guard (P0)
        // Key: advance:{customer_id}:{norm_amount}:{date}:{user}:{bank_id}
        // Normalized amount to prevent "100" vs "100.00" bypass
        $amountKey = number_format((float)$request->input('amount'), 2, '.', '');
        
        $idempotencyKey = sprintf(
            'advance:%s:%s:%s:%s:%s:%s',
            $request->input('customer_id'),
            $amountKey,
            $request->input('payment_date'),
            auth()->id(),
            $request->input('bank_account_id'),
            $request->input('vessel_id', '0')
        );

        // Lock for 10 seconds. Fallback to simple cache add if lock fails to acquire or driver issues.
        $lock = \Illuminate\Support\Facades\Cache::lock($idempotencyKey, 10);
        $acquired = $lock->get();
        
        $fallbackKey = $idempotencyKey . ':simple';

        if ($acquired) {
            // Success: Lock acquired.
            // Also set fallback key to prevent bypass if lock driver is ignored by subsequent requests
            \Illuminate\Support\Facades\Cache::put($fallbackKey, 1, 10);
        } else {
             // Locked or Driver Fail: Try Fallback Barrier
             // If we can ADD the key, it means it wasn't there => Driver fail or Race.
             // If we CANNOT add the key, it means it IS there => Locked by someone.
             
             if (!\Illuminate\Support\Facades\Cache::add($fallbackKey, 1, 10)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => __('İşlem zaten devam ediyor. Lütfen bekleyiniz.'),
                ]);
             }
        }

        try {
            $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vessel_id' => 'nullable|exists:vessels,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $allocationResult = DB::transaction(function () use ($request, $ledgerService, $allocator) {
            // 1) Determine Currency
            $bankAccount = \App\Models\BankAccount::with('currency')->findOrFail($request->bank_account_id);
            $currency = $bankAccount->currency->code ?? 'EUR'; // Fallback if missing, though unlikely
            $amount = (float) $request->amount;

            // V2: Security Check (stronger) - Vessel ownership (single query)
            if ($request->filled('vessel_id')) {
                $ok = \App\Models\Vessel::whereKey($request->vessel_id)
                    ->where('customer_id', $request->customer_id)
                    ->exists();

                if (!$ok) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'vessel_id' => __('Seçilen tekne bu müşteriye ait değil.'),
                    ]);
                }
            }

            // 2) Create Payment (Advance: no invoice_id, no fx_rate needed initially as it is base currency)
            // effective_amount = original_amount = amount.
            $payment = Payment::create([
                'invoice_id' => null,
                'customer_id' => $request->customer_id,
                'bank_account_id' => $request->bank_account_id,
                'amount' => $amount, // normalized amount
                'original_amount' => $amount, // same as amount since no FX target
                'original_currency' => $currency,
                'fx_rate' => 1.0,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // 3) Ledger Hook (Single Source of Truth)
            // Advance payments are not tied to an invoice -> still must create ledger credit
            $ledgerService->createCreditFromPayment($payment, auth()->id(), $request->vessel_id);

            // 4) Auto Allocate Implementation (Sprint 3.2)
            return $allocator->allocateForPayment($payment);
        });

        // Construct Message
        if ($allocationResult['allocated_total'] > 0) {
            $msg = __("Tahsilat kaydedildi. :paid adet fatura kapandı, :partial adet fatura kısmi ödendi. Kalan avans: :rem :cur", [
                'paid' => $allocationResult['paid_count'],
                'partial' => $allocationResult['partial_count'],
                'rem' => $allocationResult['payment_remaining'],
                'cur' => $allocationResult['currency']
            ]);
        } else {
            $msg = __("Tahsilat kaydedildi. Uygun açık fatura bulunamadı, tutar avans olarak işlendi.");
        }

        return redirect()->route('payments.index')->with('success', $msg);
        } finally {
            $lock->release();
        }
    }

    /**
     * Show allocate form.
     */
    public function allocate(Payment $payment)
    {
        // HOTFIX: Optimize N+1
        $payment->load(['allocations.invoice', 'customer', 'invoice']);

        // Find candidate invoices
        $customerId = $payment->customer_id ?? $payment->invoice?->customer_id;
        $currency = $payment->effective_currency;

        $invoices = Invoice::where('customer_id', $customerId)
            ->where('currency', $currency)
            ->where('payment_status', '!=', 'paid') // Only unpaid/partial
            ->orderBy('issue_date')
            // HOTFIX: Optimize invoice query with eager loading
            ->with(['payments', 'paymentAllocations'])
            ->get();

        return view('payments.allocate', compact('payment', 'invoices'));
    }

    /**
     * Store payment for an invoice
     */
    public function store(Request $request, Invoice $invoice, \App\Services\LedgerService $ledgerService)
    {
        // Normalize TR number formats (e.g. 2.393.449,73 -> 2393449.73 / 50,30 -> 50.30)
        $request->merge([
            'amount' => $this->normalizeDecimalInput($request->input('amount')),
            'fx_rate' => $this->normalizeDecimalInput($request->input('fx_rate')),
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:0.01', // original_amount
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'fx_rate' => 'nullable|numeric|min:0.00000001',
        ]);

        if ($invoice->status !== 'issued') {
            return back()->with('error', 'Sadece resmileşmiş faturalara tahsilat eklenebilir.');
        }

        $overpaidAfter = 0.0;

        DB::transaction(function () use ($request, $invoice, $ledgerService, &$overpaidAfter) {
            $freshInvoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $invoiceTotal = (float) $freshInvoice->total; // Fix: Undefined variable in overpaid calculation

            // 1) Determine Currency & FX (currency is sourced from DB, not from user input)
            $bankAccount = \App\Models\BankAccount::with('currency')->findOrFail($request->bank_account_id);

            $invoiceCurrency = $freshInvoice->currency ?? 'EUR';
            // IMPORTANT: never fallback to EUR silently; fallback to invoice currency to avoid wrong conversions
            $originalCurrency = $bankAccount->currency->code ?? $invoiceCurrency;

            $originalAmount = (float) $request->amount;
            $fxRate = 1.0;

            if ($originalCurrency !== $invoiceCurrency) {
                if (!$request->filled('fx_rate') || (float) $request->fx_rate <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fx_rate' => "Döviz kurları farklı ({$originalCurrency} → {$invoiceCurrency}), geçerli bir kur giriniz."
                    ]);
                }

                // Formula: 1 InvoiceCurrency = X OriginalCurrency
                // Equivalent (InvoiceCurrency) = Original / Rate
                $fxRate = (float) $request->fx_rate;
                $equivalentAmount = round($originalAmount / $fxRate, 2);
            } else {
                $equivalentAmount = round($originalAmount, 2);
            }

            // 2) Create Payment
            $payment = Payment::create([
                'invoice_id' => $freshInvoice->id,
                'customer_id' => $freshInvoice->customer_id, // ✅ HOTFIX: customer_id artık set ediliyor
                'bank_account_id' => $request->bank_account_id,
                'amount' => $equivalentAmount, // invoice equivalent
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency,
                'fx_rate' => $fxRate,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // 3) Update Payment Status (via Centralized Service)
            app(\App\Services\InvoiceStatusService::class)->recompute($freshInvoice);

            // Access refreshed/updated status for notification if needed
            // $freshInvoice->refresh();

            // Calculate overpaid strict for UI feedback (optional, kept for legacy compatibility)
            // Note: service uses sum(), so we can just re-sum or assume consistency.
            // Let's rely on DB truth if we really need exact overpaid amount, 
            // but for minimal diff we can keep the calculation variables if they were used for $overpaidAfter.
            // The original logic calculated $overpaidAfter using local vars. Let's keep that for the flash message.
            $totalLegacyPaid = (float) $freshInvoice->payments()->sum('amount');
            $totalAllocPaid  = (float) $freshInvoice->paymentAllocations()->sum('amount');
            $totalPaid = $totalLegacyPaid + $totalAllocPaid;
            $overpaidAfter = max(0, $totalPaid - $invoiceTotal);

            // 4) Ledger Hook
            $ledgerService->createCreditFromPayment($payment, auth()->id());
        });

        if ($overpaidAfter > 0) {
            $msg = 'Tahsilat eklendi. Fazla tahsilat: ' . number_format($overpaidAfter, 2) . ' ' . ($invoice->currency ?? 'EUR') . ' (cari alacak oluştu).';
            return back()->with('success', $msg);
        }

        return back()->with('success', 'Tahsilat eklendi.');
    }

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

        // If comma exists, assume TR format: '.' thousands and ',' decimal
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        // Strip any non-numeric chars except dot and minus
        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return $s;
    }
}
