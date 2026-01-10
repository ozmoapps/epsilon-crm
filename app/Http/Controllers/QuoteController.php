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
use Illuminate\Auth\Access\AuthorizationException;
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
            ->with(['customer', 'vessel', 'salesOrder', 'currencyRelation'])
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
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->orderBy('name')->get(),
            'workOrders' => WorkOrder::orderByDesc('id')->get(),
            'statuses' => Quote::statusOptions(),
            'currencies' => $this->activeCurrencies(),
        ]);
    }

    public function store(Request $request)
    {
        $this->prepareItemsForValidation($request);
        $validated = $request->validate($this->rules(), $this->messages());

        $currency = Currency::query()->find($validated['currency_id']);
        $validated['currency'] = $currency?->code ?? config('quotes.default_currency');
        $validated['created_by'] = $request->user()->id;

        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $quote = Quote::create($validated);

        if ($items !== null) {
            $this->syncItems($quote, $items);
        }

        return redirect()->route('quotes.index')
            ->with('success', 'Teklif oluşturuldu.');
    }

    public function show(Quote $quote)
    {
        $quote->load([
            'customer',
            'vessel',
            'workOrder',
            'creator',
            'items',
            'salesOrder',
            'activityLogs.actor',
            'currencyRelation',
        ]);

        return view('quotes.show', compact('quote'));
    }

    public function preview(Quote $quote)
    {
        $this->authorize('view', $quote);

        $quote->loadMissing(['customer', 'vessel', 'items', 'currencyRelation', 'workOrder']);
        $companyProfile = CompanyProfile::current();
        $bankAccounts = BankAccount::query()->with('currency')->orderBy('bank_name')->get();

        return view('quotes.preview', compact('quote', 'companyProfile', 'bankAccounts'));
    }

    public function pdf(Quote $quote)
    {
        $this->authorize('view', $quote);

        $quote->loadMissing(['customer', 'vessel', 'items', 'currencyRelation', 'workOrder']);
        $companyProfile = CompanyProfile::current();
        $bankAccounts = BankAccount::query()->with('currency')->orderBy('bank_name')->get();

        return response()
            ->view('quotes.pdf', compact('quote', 'companyProfile', 'bankAccounts'))
            ->header('Content-Type', 'application/pdf');
    }

    public function edit(Quote $quote)
    {
        if ($quote->isLocked()) {
            return redirect()->route('quotes.show', $quote)
                ->with('error', 'Bu teklif siparişe dönüştürüldüğü için düzenlenemez.');
        }

        if ($response = $this->authorizeQuote('update', $quote)) {
            return $response;
        }

        return view('quotes.edit', [
            'quote' => $quote->loadMissing('items'),
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::with('customer')->orderBy('name')->get(),
            'workOrders' => WorkOrder::orderByDesc('id')->get(),
            'statuses' => Quote::statusOptions(),
            'currencies' => $this->activeCurrencies(),
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        if ($quote->isLocked()) {
            return redirect()->route('quotes.show', $quote)
                ->with('error', 'Bu teklif siparişe dönüştürüldüğü için düzenlenemez.');
        }

        if ($response = $this->authorizeQuote('update', $quote)) {
            return $response;
        }

        $this->prepareItemsForValidation($request);
        $validated = $request->validate($this->rules(), $this->messages());
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $nextStatus = $validated['status'];
        $payload = $validated;
        unset($payload['status']);

        if (! $quote->canTransitionTo($nextStatus)) {
            return redirect()->route('quotes.show', $quote)
                ->with('error', 'Durum geçişine izin verilmiyor.');
        }

        if ($quote->status !== $nextStatus) {
            $quote->transitionTo($nextStatus, ['source' => 'update']);
        }

        $currency = Currency::query()->find($validated['currency_id']);
        $payload['currency'] = $currency?->code ?? $payload['currency'] ?? config('quotes.default_currency');

        $quote->fill($payload)->save();

        if ($request->has('items')) {
            $this->syncItems($quote, $items ?? []);
        }

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Teklif güncellendi.');
    }

    public function destroy(Quote $quote)
    {
        if ($quote->isLocked()) {
            app(ActivityLogger::class)->log($quote, 'delete_blocked', [
                'reason' => 'locked',
            ]);
            return redirect()->route('quotes.show', $quote)
                ->with('error', 'Bu teklifin bağlı siparişi olduğu için silinemez.');
        }

        if ($response = $this->authorizeQuote('delete', $quote)) {
            return $response;
        }

        $quote->delete();

        return redirect()->route('quotes.index')
            ->with('success', 'Teklif silindi.');
    }

    public function markAsSent(Quote $quote)
    {
        if (! $quote->transitionTo('sent', ['source' => 'mark_sent'])) {
            return redirect()->route('quotes.show', $quote)
                ->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        $quote->forceFill(['sent_at' => now()])->save();

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Teklif gönderildi olarak işaretlendi.');
    }

    public function markAsAccepted(Quote $quote)
    {
        if (! $quote->transitionTo('accepted', ['source' => 'mark_accepted'])) {
            return redirect()->route('quotes.show', $quote)
                ->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        $quote->forceFill(['accepted_at' => now()])->save();

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Teklif onaylandı olarak işaretlendi.');
    }

    public function convertToSalesOrder(Request $request, Quote $quote)
    {
        $quote->loadMissing(['items', 'salesOrder', 'currencyRelation']);

        if ($quote->status !== 'accepted') {
            return redirect()->back()
                ->with('error', 'Sipariş oluşturmak için teklif önce onaylanmalıdır.');
        }

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
                'currency' => $quote->currencyRelation?->code ?? $quote->currency,
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

            $quote->transitionTo('converted', [
                'sales_order_id' => $salesOrder->id,
                'sales_order_no' => $salesOrder->order_no,
            ]);

            app(ActivityLogger::class)->log($quote, 'converted_to_sales_order', [
                'sales_order_id' => $salesOrder->id,
                'sales_order_no' => $salesOrder->order_no,
            ]);

            app(ActivityLogger::class)->log($salesOrder, 'created_from_quote', [
                'quote_id' => $quote->id,
                'quote_no' => $quote->quote_no,
            ]);

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
            'issued_at' => ['required', 'date'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')->where('is_active', true)],
            'validity_days' => ['nullable', 'integer', 'min:0'],
            'estimated_duration_days' => ['nullable', 'integer', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'warranty_text' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'fx_note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:quote_items,id'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
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
            'issued_at.required' => 'Teklif tarihi zorunludur.',
            'issued_at.date' => 'Teklif tarihi geçerli değil.',
            'contact_name.max' => 'İletişim kişisi en fazla 255 karakter olabilir.',
            'contact_phone.max' => 'İletişim telefonu en fazla 255 karakter olabilir.',
            'location.max' => 'Lokasyon en fazla 255 karakter olabilir.',
            'currency_id.required' => 'Para birimi zorunludur.',
            'currency_id.exists' => 'Seçilen para birimi geçersiz.',
            'validity_days.integer' => 'Geçerlilik günü sayısal olmalıdır.',
            'validity_days.min' => 'Geçerlilik günü negatif olamaz.',
            'estimated_duration_days.integer' => 'Tahmini süre sayısal olmalıdır.',
            'estimated_duration_days.min' => 'Tahmini süre negatif olamaz.',
            'items.array' => 'Kalem listesi geçerli değil.',
            'items.*.title.required' => 'Kalem başlığı zorunludur.',
            'items.*.title.max' => 'Kalem başlığı en fazla 255 karakter olabilir.',
            'items.*.description.required' => 'Kalem açıklaması zorunludur.',
            'items.*.amount.required' => 'Kalem tutarı zorunludur.',
            'items.*.amount.numeric' => 'Kalem tutarı sayısal olmalıdır.',
            'items.*.amount.min' => 'Kalem tutarı negatif olamaz.',
            'items.*.vat_rate.numeric' => 'KDV oranı sayısal olmalıdır.',
            'items.*.vat_rate.min' => 'KDV oranı negatif olamaz.',
        ];
    }

    private function activeCurrencies()
    {
        return Currency::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function prepareItemsForValidation(Request $request): void
    {
        if (! $request->has('items')) {
            return;
        }

        $items = collect($request->input('items', []))
            ->map(function (array $item) {
                $amount = $this->normalizeDecimal($item['amount'] ?? null);
                $vatRate = $this->normalizeDecimal($item['vat_rate'] ?? null);

                return [
                    'id' => $item['id'] ?? null,
                    'title' => isset($item['title']) ? trim((string) $item['title']) : null,
                    'description' => isset($item['description']) ? trim((string) $item['description']) : null,
                    'amount' => $amount,
                    'vat_rate' => $vatRate,
                ];
            })
            ->filter(function (array $item) {
                return filled($item['title'])
                    || filled($item['description'])
                    || filled($item['amount'])
                    || filled($item['vat_rate']);
            })
            ->values()
            ->all();

        $request->merge(['items' => $items]);
    }

    private function normalizeDecimal(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);
        $value = trim($value);

        return $value === '' ? null : $value;
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
