<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Vessel;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $quotes = Quote::query()
            ->with(['customer', 'vessel'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('quote_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = Quote::statusOptions();

        return view('quotes.index', compact('quotes', 'search', 'status', 'statuses'));
    }

    public function create()
    {
        return view('quotes.create', [
            'quote' => new Quote([
                'status' => 'draft',
                'currency' => config('quotes.default_currency'),
                'validity_days' => config('quotes.default_validity_days'),
            ]),
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->orderBy('name')->get(),
            'workOrders' => WorkOrder::orderByDesc('id')->get(),
            'statuses' => Quote::statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['created_by'] = $request->user()->id;

        Quote::create($validated);

        return redirect()->route('quotes.index')
            ->with('success', 'Teklif oluşturuldu.');
    }

    public function show(Quote $quote)
    {
        $quote->load(['customer', 'vessel', 'workOrder', 'creator', 'items', 'salesOrder']);

        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        return view('quotes.edit', [
            'quote' => $quote,
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->orderBy('name')->get(),
            'workOrders' => WorkOrder::orderByDesc('id')->get(),
            'statuses' => Quote::statusOptions(),
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $quote->update($validated);

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Teklif güncellendi.');
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();

        return redirect()->route('quotes.index')
            ->with('success', 'Teklif silindi.');
    }

    public function convertToSalesOrder(Request $request, Quote $quote)
    {
        $quote->loadMissing(['items', 'salesOrder']);

        if ($quote->salesOrder) {
            return redirect()->route('sales-orders.show', $quote->salesOrder)
                ->with('success', 'Teklif zaten satış siparişine dönüştürülmüş.');
        }

        $salesOrder = DB::transaction(function () use ($request, $quote) {
            $salesOrder = SalesOrder::create([
                'customer_id' => $quote->customer_id,
                'vessel_id' => $quote->vessel_id,
                'work_order_id' => $quote->work_order_id,
                'quote_id' => $quote->id,
                'title' => $quote->title,
                'status' => 'draft',
                'currency' => $quote->currency,
                'order_date' => now()->toDateString(),
                'delivery_place' => null,
                'delivery_days' => null,
                'payment_terms' => $quote->payment_terms,
                'warranty_text' => $quote->warranty_text,
                'exclusions' => $quote->exclusions,
                'notes' => $quote->notes,
                'fx_note' => $quote->fx_note,
                'created_by' => $request->user()->id,
            ]);

            $items = $quote->items->where('is_optional', false)->map(function ($item) {
                return [
                    'section' => $item->section,
                    'item_type' => $item->item_type,
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'vat_rate' => $item->vat_rate,
                    'is_optional' => $item->is_optional,
                    'sort_order' => $item->sort_order,
                ];
            });

            if ($items->isNotEmpty()) {
                $salesOrder->items()->createMany($items->all());
            }

            $salesOrder->recalculateTotals();

            return $salesOrder;
        });

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Satış siparişi oluşturuldu.');
    }

    private function rules(): array
    {
        $statuses = array_keys(Quote::statusOptions());

        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'currency' => ['required', 'string', 'max:10'],
            'validity_days' => ['nullable', 'integer', 'min:0'],
            'estimated_duration_days' => ['nullable', 'integer', 'min:0'],
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
            'title.required' => 'Teklif konusu zorunludur.',
            'title.max' => 'Teklif konusu en fazla 255 karakter olabilir.',
            'status.required' => 'Durum alanı zorunludur.',
            'status.in' => 'Durum seçimi geçersiz.',
            'currency.required' => 'Para birimi zorunludur.',
            'currency.max' => 'Para birimi en fazla 10 karakter olabilir.',
            'validity_days.integer' => 'Geçerlilik günü sayısal olmalıdır.',
            'validity_days.min' => 'Geçerlilik günü negatif olamaz.',
            'estimated_duration_days.integer' => 'Tahmini süre sayısal olmalıdır.',
            'estimated_duration_days.min' => 'Tahmini süre negatif olamaz.',
        ];
    }
}
