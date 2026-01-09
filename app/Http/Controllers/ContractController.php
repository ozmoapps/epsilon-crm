<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $customer = $request->input('customer');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $contracts = Contract::query()
            ->with(['salesOrder.customer'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('contract_no', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->when($customer, fn ($query) => $query->where('customer_name', 'like', "%{$customer}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('issued_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('issued_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = Contract::statusOptions();

        return view('contracts.index', compact(
            'contracts',
            'search',
            'status',
            'customer',
            'dateFrom',
            'dateTo',
            'statuses'
        ));
    }

    public function create(SalesOrder $salesOrder)
    {
        if ($salesOrder->contract) {
            return redirect()->route('contracts.show', $salesOrder->contract)
                ->with('warning', 'Bu satış siparişi için sözleşme zaten oluşturuldu.');
        }

        $salesOrder->load(['customer', 'items', 'vessel']);

        $contract = new Contract($this->prefillFromSalesOrder($salesOrder));

        return view('contracts.create', [
            'contract' => $contract,
            'salesOrder' => $salesOrder,
            'locales' => config('contracts.locales', []),
        ]);
    }

    public function store(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->contract) {
            return redirect()->route('contracts.show', $salesOrder->contract)
                ->with('warning', 'Bu satış siparişi için sözleşme zaten oluşturuldu.');
        }

        $validated = $request->validate($this->rules(), $this->messages());

        $salesOrder->load('customer');

        $data = array_merge($this->prefillFromSalesOrder($salesOrder), $validated, [
            'sales_order_id' => $salesOrder->id,
            'created_by' => $request->user()->id,
        ]);

        $contract = Contract::create($data);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Sözleşme oluşturuldu.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['salesOrder.customer', 'salesOrder.items', 'creator']);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        if (! $contract->isEditable()) {
            return redirect()->route('contracts.show', $contract)
                ->with('warning', 'Sadece taslak sözleşmeler düzenlenebilir.');
        }

        $contract->load(['salesOrder.customer', 'salesOrder.items', 'salesOrder.vessel']);

        return view('contracts.edit', [
            'contract' => $contract,
            'salesOrder' => $contract->salesOrder,
            'locales' => config('contracts.locales', []),
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        if (! $contract->isEditable()) {
            return redirect()->route('contracts.show', $contract)
                ->with('warning', 'Sadece taslak sözleşmeler düzenlenebilir.');
        }

        $validated = $request->validate($this->rules(), $this->messages());

        $contract->update($validated);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Sözleşme güncellendi.');
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Sözleşme silindi.');
    }

    public function markSent(Contract $contract)
    {
        return $this->transitionStatus($contract, 'draft', 'sent', null, 'Sözleşme gönderildi olarak işaretlendi.');
    }

    public function markSigned(Contract $contract)
    {
        return $this->transitionStatus($contract, 'sent', 'signed', now(), 'Sözleşme imzalandı olarak işaretlendi.');
    }

    public function cancel(Contract $contract)
    {
        if ($contract->status === 'cancelled') {
            return back()->with('warning', 'Sözleşme zaten iptal edildi.');
        }

        $contract->update([
            'status' => 'cancelled',
        ]);

        return back()->with('success', 'Sözleşme iptal edildi.');
    }

    public function pdf(Contract $contract)
    {
        $contract->load(['salesOrder.customer', 'salesOrder.items']);

        return response()
            ->view('contracts.pdf', ['contract' => $contract])
            ->header('Content-Type', 'application/pdf');
    }

    private function transitionStatus(Contract $contract, string $from, string $to, $signedAt, string $message)
    {
        if ($contract->status !== $from) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        $contract->update([
            'status' => $to,
            'signed_at' => $signedAt,
        ]);

        return back()->with('success', $message);
    }

    private function rules(): array
    {
        $locales = array_keys(config('contracts.locales', []));

        return [
            'issued_at' => ['required', 'date'],
            'locale' => ['required', 'string', Rule::in($locales)],
            'payment_terms' => ['nullable', 'string'],
            'warranty_terms' => ['nullable', 'string'],
            'scope_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
            'delivery_terms' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'issued_at.required' => 'Düzenleme tarihi zorunludur.',
            'issued_at.date' => 'Düzenleme tarihi geçerli değil.',
            'locale.required' => 'Dil seçimi zorunludur.',
            'locale.in' => 'Dil seçimi geçersiz.',
        ];
    }

    private function prefillFromSalesOrder(SalesOrder $salesOrder): array
    {
        $defaults = config('contracts.defaults', []);
        $customer = $salesOrder->customer;

        return [
            'sales_order_id' => $salesOrder->id,
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $customer?->name ?? $salesOrder->title,
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $customer?->address,
            'customer_email' => $customer?->email,
            'customer_phone' => $customer?->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'payment_terms' => $salesOrder->payment_terms ?: ($defaults['payment_terms'] ?? null),
            'warranty_terms' => $salesOrder->warranty_text ?: ($defaults['warranty_terms'] ?? null),
            'scope_text' => $defaults['scope_text'] ?? null,
            'exclusions_text' => $salesOrder->exclusions ?: ($defaults['exclusions_text'] ?? null),
            'delivery_terms' => $defaults['delivery_terms'] ?? null,
        ];
    }
}
