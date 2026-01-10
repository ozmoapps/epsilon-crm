<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Currency;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BankAccount::class, 'bank_account');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $bankAccounts = BankAccount::query()
            ->with('currency')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('bank_accounts.index', compact('bankAccounts', 'search'));
    }

    public function create()
    {
        return view('bank_accounts.create', [
            'bankAccount' => new BankAccount(),
            'currencies' => $this->currencyOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['iban'] = strtoupper($validated['iban']);

        $bankAccount = BankAccount::create($validated);

        return redirect()->route('bank-accounts.show', $bankAccount)
            ->with('success', 'Banka hesabı oluşturuldu.');
    }

    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load('currency');

        return view('bank_accounts.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount)
    {
        $bankAccount->load('currency');

        return view('bank_accounts.edit', [
            'bankAccount' => $bankAccount,
            'currencies' => $this->currencyOptions(),
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['iban'] = strtoupper($validated['iban']);

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.show', $bankAccount)
            ->with('success', 'Banka hesabı güncellendi.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Banka hesabı silindi.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:34'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Hesap adı zorunludur.',
            'name.max' => 'Hesap adı en fazla 255 karakter olabilir.',
            'bank_name.required' => 'Banka adı zorunludur.',
            'bank_name.max' => 'Banka adı en fazla 255 karakter olabilir.',
            'branch_name.max' => 'Şube adı en fazla 255 karakter olabilir.',
            'iban.required' => 'IBAN zorunludur.',
            'iban.max' => 'IBAN en fazla 34 karakter olabilir.',
            'currency_id.exists' => 'Seçilen para birimi geçerli değil.',
        ];
    }

    private function currencyOptions()
    {
        return Currency::query()->orderBy('name')->get();
    }
}
