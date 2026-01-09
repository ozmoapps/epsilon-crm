<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Vessel;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $customerId = $request->input('customer_id');
        $vesselId = $request->input('vessel_id');

        $salesOrders = SalesOrder::query()
            ->with(['customer', 'vessel'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('order_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('order_date', '<=', $dateTo))
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($vesselId, fn ($query) => $query->where('vessel_id', $vesselId))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = SalesOrder::statusOptions();
        $customers = Customer::orderBy('name')->get();
        $vessels = Vessel::with('customer')->orderBy('name')->get();

        return view('sales_orders.index', compact(
            'salesOrders',
            'search',
            'status',
            'statuses',
            'dateFrom',
            'dateTo',
            'customerId',
            'vesselId',
            'customers',
            'vessels'
        ));
    }

    public function create()
    {
        return view('sales_orders.create', [
            'salesOrder' => new SalesOrder([
                'status' => 'draft',
                'currency' => 'EUR',
                'order_date' => now()->toDateString(),
            ]),
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->orderBy('name')->get(),
            'workOrders' => WorkOrder::orderByDesc('id')->get(),
            'statuses' => SalesOrder::statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['created_by'] = $request->user()->id;

        $salesOrder = SalesOrder::create($validated);

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Satış siparişi oluşturuldu.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'vessel', 'workOrder', 'creator', 'items', 'quote']);

        return view('sales_orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        return view('sales_orders.edit', [
            'salesOrder' => $salesOrder,
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->orderBy('name')->get(),
            'workOrders' => WorkOrder::orderByDesc('id')->get(),
            'statuses' => SalesOrder::statusOptions(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate($this->deliveryRules(), $this->deliveryMessages());

        $salesOrder->fill($validated);
        $salesOrder->save();

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Teslim bilgileri güncellendi.');
    }

    public function confirm(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canConfirm()) {
            return back()->with('error', 'Sipariş onaylanamaz.');
        }

        $salesOrder->markConfirmed();

        return back()->with('success', 'Sipariş onaylandı.');
    }

    public function start(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canStart()) {
            return back()->with('error', 'Sipariş devam ettirilemez.');
        }

        $salesOrder->markInProgress();

        return back()->with('success', 'Sipariş devam ediyor.');
    }

    public function complete(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canComplete()) {
            return back()->with('error', 'Sipariş tamamlanamaz.');
        }

        $salesOrder->markCompleted();

        return back()->with('success', 'Sipariş tamamlandı.');
    }

    public function cancel(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canCancel()) {
            return back()->with('error', 'Sipariş iptal edilemez.');
        }

        $salesOrder->markCanceled();

        return back()->with('success', 'Sipariş iptal edildi.');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        $salesOrder->delete();

        return redirect()->route('sales-orders.index')
            ->with('success', 'Satış siparişi silindi.');
    }

    private function rules(): array
    {
        $statuses = array_keys(SalesOrder::statusOptions());

        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'currency' => ['required', 'string', 'max:10'],
            'order_date' => ['nullable', 'date'],
            'delivery_place' => ['nullable', 'string', 'max:255'],
            'delivery_days' => ['nullable', 'integer', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'warranty_text' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'fx_note' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'customer_id.required' => 'Müşteri seçimi zorunludur.',
            'customer_id.exists' => 'Seçilen müşteri geçersiz.',
            'vessel_id.required' => 'Tekne seçimi zorunludur.',
            'vessel_id.exists' => 'Seçilen tekne geçersiz.',
            'work_order_id.exists' => 'Seçilen iş emri geçersiz.',
            'title.required' => 'Sipariş başlığı zorunludur.',
            'title.max' => 'Sipariş başlığı en fazla 255 karakter olabilir.',
            'status.required' => 'Durum alanı zorunludur.',
            'status.in' => 'Durum seçimi geçersiz.',
            'currency.required' => 'Para birimi zorunludur.',
            'currency.max' => 'Para birimi en fazla 10 karakter olabilir.',
            'order_date.date' => 'Sipariş tarihi geçerli değil.',
            'delivery_place.max' => 'Teslim yeri en fazla 255 karakter olabilir.',
            'delivery_days.integer' => 'Teslim günü sayısal olmalıdır.',
            'delivery_days.min' => 'Teslim günü negatif olamaz.',
        ];
    }

    private function deliveryRules(): array
    {
        return [
            'delivery_place' => ['nullable', 'string', 'max:255'],
            'delivery_days' => ['nullable', 'integer', 'min:0'],
            'delivery_date' => ['nullable', 'date'],
            'title' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    private function deliveryMessages(): array
    {
        return [
            'delivery_place.max' => 'Teslim yeri en fazla 255 karakter olabilir.',
            'delivery_days.integer' => 'Teslim günü sayısal olmalıdır.',
            'delivery_days.min' => 'Teslim günü negatif olamaz.',
            'delivery_date.date' => 'Teslim tarihi geçerli değil.',
            'title.max' => 'Sipariş başlığı en fazla 255 karakter olabilir.',
        ];
    }
}
