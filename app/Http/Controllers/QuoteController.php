<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Currency;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Vessel;
use App\Models\WorkOrder;
use App\Models\CompanyProfile;
use App\Models\BankAccount;
use App\Services\ActivityLogger;
use App\Models\Contract;
use App\Models\ActivityLog;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Support\TenantGuard;

class QuoteController extends Controller
{
    use TenantGuard;

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        $vesselId = $request->input('vessel_id');
        $currency = $request->input('currency');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $totalMin = $request->input('total_min');
        $totalMax = $request->input('total_max');

        $quotes = Quote::query()
            ->with(['customer', 'vessel', 'salesOrder', 'currencyRelation'])
            ->where('tenant_id', app(\App\Services\TenantContext::class)->id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('quote_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($vesselId, fn ($q) => $q->where('vessel_id', $vesselId))
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->when($dateFrom, fn ($q) => $q->whereDate('issued_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('issued_at', '<=', $dateTo))
            ->when($totalMin, function ($q) use ($totalMin) {
                $val = \App\Support\MoneyMath::normalizeDecimalString($totalMin);
                $q->where('grand_total', '>=', $val);
            })
            ->when($totalMax, function ($q) use ($totalMax) {
                $val = \App\Support\MoneyMath::normalizeDecimalString($totalMax);
                $q->where('grand_total', '<=', $val);
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = Quote::statusOptions();
        $customers = Customer::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(['id', 'name']);
        $vessels = Vessel::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(['id', 'name', 'customer_id']);
        $currencies = Currency::where('is_active', true)->orderBy('code')->get(['code', 'name']);
        
        $savedViews = \App\Models\SavedView::allow('quotes')->visibleTo($request->user())->get();

        return view('quotes.index', compact(
            'quotes', 'search', 'status', 'statuses',
            'customers', 'vessels', 'currencies', 'savedViews',
            'customerId', 'vesselId', 'currency', 'dateFrom', 'dateTo', 'totalMin', 'totalMax'
        ));
    }

    public function create()
    {
        $defaultCurrencyId = Quote::resolveDefaultCurrencyId();
        $defaultCurrencyCode = $defaultCurrencyId
            ? Currency::query()->whereKey($defaultCurrencyId)->value('code')
            : config('quotes.default_currency');

        return view('quotes.create', [
            'quote' => new Quote([
                'status' => 'draft',
                'issued_at' => now()->toDateString(),
                'currency_id' => $defaultCurrencyId,
                'currency' => $defaultCurrencyCode,
                'validity_days' => config('quotes.default_validity_days'),
                'payment_terms' => config('quotes.default_payment_terms'),
            ]),
            'customers' => Customer::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(),
            'workOrders' => WorkOrder::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderByDesc('id')->get(),
            'statuses' => Quote::statusOptions(),
            'currencies' => $this->activeCurrencies(),
        ]);
    }

    public function edit(Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('update', $quote);

        return view('quotes.edit', [
            'quote' => $quote,
            'customers' => Customer::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(),
            'workOrders' => WorkOrder::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderByDesc('id')->get(),
            'statuses' => Quote::statusOptions(),
            'currencies' => $this->activeCurrencies(),
        ]);
    }

    public function show(Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('view', $quote);
        $quote->load(['customer', 'vessel', 'items', 'salesOrder', 'workOrder', 'currencyRelation']);

        // Fetch activity logs for the quote (subject)
        $timeline = ActivityLog::with(['actor', 'subject'])
            ->where('subject_type', Quote::class)
            ->where('subject_id', $quote->id)
            ->latest()
            ->get();
            
        $products = \App\Models\Product::where('tenant_id', app(\App\Services\TenantContext::class)->id())
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'default_sell_price', 'currency_code']);

        return view('quotes.show', compact('quote', 'timeline', 'products'));
    }

    public function pdf(Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('view', $quote);
        // Fallback to print view for now
        return $this->printView($quote);
    }

    public function preview(Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('view', $quote);
        return $this->printView($quote);
    }

    public function printView(Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('view', $quote);
        $quote->load(['customer', 'vessel', 'items', 'currencyRelation']);
        
        $companyProfile = \App\Models\CompanyProfile::current();
        $bankAccounts = \App\Models\BankAccount::where('tenant_id', app(\App\Services\TenantContext::class)->id())->where('is_active', true)->get();
        return view('quotes.print', compact('quote', 'companyProfile', 'bankAccounts'));
    }

    public function store(\App\Http\Requests\QuoteStoreRequest $request)
    {
        $validated = $request->validated();

        $currency = Currency::query()->find($validated['currency_id']);
        $validated['currency'] = $currency?->code ?? config('quotes.default_currency');
        $validated['created_by'] = $request->user()->id;

        $items = $validated['items'] ?? null;
        unset($validated['items']);

        // Model hook handles tenant_id
        $quote = Quote::create($validated);

        if ($items !== null) {
            $this->syncItems($quote, $items);
        }

        return redirect()->route('quotes.index')
            ->with('success', 'Teklif oluşturuldu.');
    }

    public function convertToSalesOrder(Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('update', $quote);

        // 1. Idempotency Check
        if ($quote->sales_order_id) {
            return redirect()->route('sales-orders.show', $quote->sales_order_id)
                ->with('info', 'Bu teklif zaten siparişe dönüştürülmüş.');
        }

        // 2. Validation
        if (!$quote->vessel_id) {
            return redirect()->back()
                ->with('error', 'Satış siparişi oluşturmak için teklifin bir tekneye bağlı olması gerekir.');
        }

        return DB::transaction(function () use ($quote) {
            // Lock Quote for concurrency
            $quote = Quote::lockForUpdate()->find($quote->id);

            // Re-check after lock
            if ($quote->sales_order_id) {
                return redirect()->route('sales-orders.show', $quote->sales_order_id);
            }

            // 3. Create Sales Order
            $salesOrder = SalesOrder::create([
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'vessel_id' => $quote->vessel_id,
                'work_order_id' => $quote->work_order_id,
                'title' => $quote->title,
                'currency' => $quote->currency,
                'order_date' => now(),
                'status' => 'draft', // Initial status
                'payment_terms' => $quote->payment_terms,
                'warranty_text' => $quote->warranty_text,
                'exclusions' => $quote->exclusions,
                'notes' => $quote->notes,
                'fx_note' => $quote->fx_note,
                'created_by' => auth()->id(),
                'tenant_id' => $quote->tenant_id, // Explicit copy
            ]);

            // 4. Create Items
            foreach ($quote->items as $qItem) {
                $salesOrder->items()->create([
                    'product_id' => $qItem->product_id, // Transfer Product Link
                    'section' => $qItem->section,
                    'item_type' => $qItem->item_type,
                    'description' => $qItem->description,
                    'qty' => $qItem->qty,
                    'unit' => $qItem->unit,
                    'unit_price' => $qItem->unit_price,
                    'discount_amount' => $qItem->discount_amount,
                    'vat_rate' => $qItem->vat_rate,
                    'is_optional' => $qItem->is_optional,
                    'sort_order' => $qItem->sort_order,
                ]);
            }

            $salesOrder->recalculateTotals();

            // 5. Update Quote
            $quote->update([
                'sales_order_id' => $salesOrder->id,
                'converted_at' => now(),
                // Status updated NOT performed per requirement
            ]);

            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('success', 'Satış siparişi başarıyla oluşturuldu.');
        });
    }

    public function update(\App\Http\Requests\QuoteUpdateRequest $request, Quote $quote)
    {
        $this->checkTenant($quote);

        $this->authorize('update', $quote);

        $validated = $request->validated();

        $currency = Currency::query()->find($validated['currency_id']);
        $validated['currency'] = $currency?->code ?? config('quotes.default_currency');
        // Do not update created_by

        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $quote->update($validated);

        if ($items !== null) {
            $this->syncItems($quote, $items);
        }

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Teklif güncellendi.');
    }
    
    public function destroy(Quote $quote)
    {
         $this->checkTenant($quote);
         $this->authorize('delete', $quote);
         $quote->delete();
         
         return redirect()->route('quotes.index')
            ->with('success', 'Teklif silindi.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:quotes,id'],
        ]);

        $count = 0;
        // Efficient bulk scope check
        $quotes = Quote::whereIn('id', $ids)
                     ->where('tenant_id', app(\App\Services\TenantContext::class)->id())
                     ->get();

        foreach ($quotes as $quote) {
            if ($request->user()->can('delete', $quote)) {
                $quote->delete();
                $count++;
            }
        }
        
        if ($count === 0) {
            return redirect()->route('quotes.index')
                ->with('error', 'Seçilen kayıtlar silinemedi veya yetkiniz yok.');
        }

        return redirect()->route('quotes.index')
            ->with('success', 'Silindi.');
    }

    private function activeCurrencies()
    {
        return Currency::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }



    private function syncItems(Quote $quote, array $items): void
    {
        $normalizedItems = collect($items)->map(function (array $item, int $index) {
            $amount = (float) $item['amount'];
            $vatRate = $item['vat_rate'] !== null && $item['vat_rate'] !== ''
                ? (float) $item['vat_rate']
                : null;

            return [
                'id' => $item['id'] ?? null,
                'payload' => [
                    'section' => $item['title'],
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => 'other',
                    'description' => $item['description'],
                    'qty' => 1,
                    'unit' => null,
                    'unit_price' => $amount,
                    'discount_amount' => 0,
                    'vat_rate' => $vatRate,
                    'is_optional' => false,
                    'sort_order' => $index,
                ],
            ];
        })->values();

        if ($normalizedItems->isEmpty()) {
            $quote->items()->delete();
            $quote->recalculateTotals();
            return;
        }

        $keptIds = [];

        $normalizedItems->each(function (array $item) use ($quote, &$keptIds) {
            if ($item['id']) {
                $existing = $quote->items()->whereKey($item['id'])->first();

                if ($existing) {
                    $existing->update($item['payload']);
                    $keptIds[] = $existing->id;
                    return;
                }
            }

            $created = $quote->items()->create($item['payload']);
            $keptIds[] = $created->id;
        });

        $quote->items()->whereNotIn('id', $keptIds)->delete();
        $quote->recalculateTotals();
    }

    private function authorizeQuote(string $ability, Quote $quote)
    {
        try {
            $this->authorize($ability, $quote);
        } catch (AuthorizationException $exception) {
            return redirect()->route('quotes.show', $quote)
                ->with('error', $exception->getMessage() ?: 'Bu işlem için yetkiniz yok.');
        }

        return null;
    }
}
