<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Currency;
use App\Models\Vessel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerLedgerManualEntryController extends Controller
{
    /**
     * Show the form for creating a new manual ledger entry.
     */
    public function create(Customer $customer)
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get();

        $vessels = Vessel::where('customer_id', $customer->id)
            ->orderBy('name')
            ->get(['id', 'name', 'customer_id']);

        return view('customers.ledger.create', [
            'customer' => $customer,
            'currencies' => $currencies,
            'vessels' => $vessels,
        ]);
    }

    /**
     * Store a newly created manual ledger entry.
     */
    public function store(Request $request, Customer $customer)
    {
        $request->merge([
            'amount' => $this->normalizeDecimalInput($request->input('amount')),
        ]);

        $request->validate([
            'occurred_at' => ['required', 'date'],
            'currency' => [
                'required',
                'string',
                \Illuminate\Support\Facades\Schema::hasColumn('currencies', 'is_active')
                    ? Rule::exists('currencies', 'code')->where(fn ($q) => $q->where('is_active', true))
                    : 'exists:currencies,code'
            ],
            'direction' => ['required', Rule::in(['debit', 'credit'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'vessel_id' => ['nullable', 'exists:vessels,id'],
        ]);

        // Security check: Vessel must belong to customer
        if ($request->filled('vessel_id')) {
            $isOwner = Vessel::where('id', $request->vessel_id)
                ->where('customer_id', $customer->id)
                ->exists();

            if (!$isOwner) {
                return back()->withErrors(['vessel_id' => __('Seçilen tekne bu müşteriye ait değil.')])->withInput();
            }
        }

        LedgerEntry::create([
            'customer_id' => $customer->id,
            'vessel_id' => $request->vessel_id,
            'type' => 'manual',
            'source_type' => null,
            'source_id' => null,
            'direction' => $request->direction,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'occurred_at' => $request->occurred_at,
            'description' => $request->description,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('customers.ledger', $customer)
            ->with('success', __('Cari işlem başarıyla eklendi.'));
    }

    /**
     * Remove the specified manual ledger entry.
     */
    public function destroy(Customer $customer, LedgerEntry $entry)
    {
        // Security checks
        if ($entry->customer_id !== $customer->id) {
            abort(404);
        }

        if ($entry->type !== 'manual') {
            return back()->with('error', __('Sadece manuel eklenen kayıtlar silinebilir.'));
        }

        $entry->delete();

        return back()->with('success', __('Cari işlem silindi.'));
    }

    /**
     * Normalize TR formatted decimals to dot-decimal numeric string.
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
