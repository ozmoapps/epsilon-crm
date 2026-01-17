<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

use App\Support\TenantGuard;

class CustomerController extends Controller
{
    use TenantGuard;

    public function index(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::query()
            ->where('tenant_id', app(\App\Services\TenantContext::class)->id()) // Explicit scope
            ->withCount('vessels')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Customer::class);
        return view('customers.create', [
            'customer' => new Customer(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['created_by'] = $request->user()->id;
        $validated['tenant_id'] = app(\App\Services\TenantContext::class)->id(); // Explicit set (model hook also handles this)
        $customer = Customer::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Oluşturuldu.',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                ]
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Oluşturuldu.');
    }

    public function show(Customer $customer, Request $request)
    {
        $this->checkTenant($customer);
        $this->authorize('view', $customer);
        $customer->load(['vessels', 'workOrders.vessel']);

        // Fetch activity logs for the customer (subject)
        $timeline = \App\Models\ActivityLog::with(['actor', 'subject'])
            ->where('subject_type', \App\Models\Customer::class)
            ->where('subject_id', $customer->id)
            ->latest()
            ->get();

        // Ledger Entries
        $ledgerQuery = \App\Models\LedgerEntry::where('customer_id', $customer->id)
            ->with(['vessel', 'source']);

        if ($request->filled('ledger_start_date')) {
            $ledgerQuery->whereDate('occurred_at', '>=', $request->ledger_start_date);
        }
        if ($request->filled('ledger_end_date')) {
            $ledgerQuery->whereDate('occurred_at', '<=', $request->ledger_end_date);
        }
        if ($request->filled('ledger_type')) {
            $ledgerQuery->where('type', $request->ledger_type);
        }
        if ($request->filled('ledger_vessel_id')) {
            $ledgerQuery->where('vessel_id', $request->ledger_vessel_id);
        }
        if ($request->filled('ledger_currency')) {
            $ledgerQuery->where('currency', $request->ledger_currency);
        }

        $ledgerEntries = $ledgerQuery->latest('occurred_at')->latest('id')->paginate(10, ['*'], 'ledger_page');

        // Calculate Balances (Global)
        $balances = \App\Models\LedgerEntry::where('customer_id', $customer->id)
            ->selectRaw('currency, sum(case when direction = ? then amount else -amount end) as balance', ['debit'])
            ->groupBy('currency')
            ->pluck('balance', 'currency');

        return view('customers.show', compact('customer', 'timeline', 'ledgerEntries', 'balances'));
    }

    public function edit(Customer $customer)
    {
        $this->checkTenant($customer);
        $this->authorize('update', $customer);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->checkTenant($customer);
        $this->authorize('update', $customer);
        $validated = $request->validate($this->rules(), $this->messages());

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Güncellendi.');
    }

    public function destroy(Customer $customer)
    {
        $this->checkTenant($customer);
        $this->authorize('delete', $customer);

        if ($customer->vessels()->exists()) {
            return back()->with('error', 'Bu müşteriye bağlı tekne(ler) olduğu için silinemez.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Silindi.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:customers,id'],
        ]);

        $count = 0;
        // Efficient bulk scope check
        $customers = Customer::whereIn('id', $ids)
                     ->where('tenant_id', app(\App\Services\TenantContext::class)->id())
                     ->get();

        foreach ($customers as $customer) {
            if ($request->user()->can('delete', $customer)) {
                $customer->delete();
                $count++;
            }
        }

        if ($count === 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Seçilen kayıtlar silinemedi veya yetkiniz yok.');
        }

        return redirect()->route('customers.index')
            ->with('success', 'Silindi.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'İsim alanı zorunludur.',
            'name.max' => 'İsim alanı en fazla 255 karakter olabilir.',
            'phone.max' => 'Telefon alanı en fazla 50 karakter olabilir.',
            'email.email' => 'E-posta formatı geçerli değil.',
            'email.max' => 'E-posta alanı en fazla 255 karakter olabilir.',
            'address.max' => 'Adres alanı en fazla 255 karakter olabilir.',
        ];
    }
}
