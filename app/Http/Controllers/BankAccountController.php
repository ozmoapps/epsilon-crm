<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Support\TenantGuard;

class BankAccountController extends Controller
{
    use TenantGuard;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BankAccount::query()
            ->with(['currency'])
            ->where('tenant_id', app(\App\Services\TenantContext::class)->id())
            ->orderBy('is_active', 'desc')
            ->orderBy('type')
            ->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $accounts = $query->paginate(20);

        return view('bank-accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        return view('bank-accounts.create', compact('currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ... Validation ...
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['bank', 'cash'])],
            'currency_id' => 'required|exists:currencies,id',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',

            // Bank only required fields
            'bank_name' => [Rule::requiredIf(fn() => $request->input('type') === 'bank'), 'nullable', 'string', 'max:255'],
            'iban' => [Rule::requiredIf(fn() => $request->input('type') === 'bank'), 'nullable', 'string', 'max:50'],

            // Optional
            'branch_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['is_active'] = $request->has('is_active');

        // If it's a CASH account, wipe bank fields to avoid accidental stale input
        if (($data['type'] ?? null) === 'cash') {
            $data['bank_name'] = null;
            $data['branch_name'] = null;
            $data['iban'] = null;
        }

        BankAccount::create($data); // Model hook sets tenant_id

        return redirect()->route('bank-accounts.index')->with('success', 'Hesap başarıyla oluşturuldu.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BankAccount $bankAccount, Request $request)
    {
        $this->checkTenant($bankAccount);

        $pQuery = $bankAccount->payments()->with(['invoice.customer']);

        if ($bankAccount->opening_balance_date) {
            $pQuery->where('payment_date', '>', $bankAccount->opening_balance_date);
        }

        $payments = $pQuery->latest('payment_date')->latest('id')->paginate(50);

        $bankAccount->load('currency');

        return view('bank-accounts.show', compact('bankAccount', 'payments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BankAccount $bankAccount)
    {
        $this->checkTenant($bankAccount);

        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        return view('bank-accounts.edit', compact('bankAccount', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->checkTenant($bankAccount);

        $hasTx = $bankAccount->payments()->exists();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['bank', 'cash'])],
            'currency_id' => 'required|exists:currencies,id',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',

            'bank_name' => [Rule::requiredIf(fn() => $request->input('type') === 'bank'), 'nullable', 'string', 'max:255'],
            'iban' => [Rule::requiredIf(fn() => $request->input('type') === 'bank'), 'nullable', 'string', 'max:50'],

            'branch_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        // ✅ GUARD: If there are transactions, lock critical identity fields
        if ($hasTx) {
            if (($data['currency_id'] ?? null) != $bankAccount->currency_id) {
                return back()->withInput()->with('error', 'Bu hesapta işlem hareketi var. Para birimi değiştirilemez.');
            }
            if (($data['type'] ?? null) !== $bankAccount->type) {
                return back()->withInput()->with('error', 'Bu hesapta işlem hareketi var. Hesap tipi (Banka/Kasa) değiştirilemez.');
            }
        }

        // If it's a CASH account, wipe bank fields
        if (($data['type'] ?? null) === 'cash') {
            $data['bank_name'] = null;
            $data['branch_name'] = null;
            $data['iban'] = null;
        }

        $bankAccount->update($data);

        return redirect()->route('bank-accounts.index')->with('success', 'Hesap güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankAccount $bankAccount)
    {
        $this->checkTenant($bankAccount);

        if ($bankAccount->payments()->exists()) {
            return back()->with('error', 'Bu hesaba ait işlem hareketleri var, silinemez.');
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('success', 'Hesap silindi.');
    }
}
