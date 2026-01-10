<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::query()
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
        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Müşteri kaydı oluşturuldu.');
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer->load(['vessels', 'workOrders.vessel']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $validated = $request->validate($this->rules(), $this->messages());

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Müşteri kaydı güncellendi.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Müşteri kaydı silindi.');
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
