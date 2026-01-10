<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Vessel;
use App\Models\WorkOrder;
use App\Models\Quote;
use App\Models\Contract;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
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
        $hasContract = $request->input('has_contract');

        $salesOrders = SalesOrder::query()
            ->with(['customer', 'vessel', 'contract'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('order_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($vesselId, fn ($q) => $q->where('vessel_id', $vesselId))
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->when($dateFrom, fn ($q) => $q->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('order_date', '<=', $dateTo))
            ->when($totalMin, function ($q) use ($totalMin) {
                $val = \App\Support\MoneyMath::normalizeDecimalString($totalMin);
                $q->where('grand_total', '>=', $val);
            })
            ->when($totalMax, function ($q) use ($totalMax) {
                $val = \App\Support\MoneyMath::normalizeDecimalString($totalMax);
                $q->where('grand_total', '<=', $val);
            })
            ->when($hasContract !== null, function ($q) use ($hasContract) {
                if ($hasContract) {
                    $q->has('contract');
                } else {
                    $q->doesntHave('contract');
                }
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = SalesOrder::statusOptions();
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $vessels = Vessel::with('customer')->orderBy('name')->get(['id', 'name', 'customer_id']);
        // SalesOrder model uses string currency code, but we can list active currencies from DB
        $currencies = \App\Models\Currency::where('is_active', true)->orderBy('code')->get(['code', 'name']);
        
        $savedViews = \App\Models\SavedView::allow('sales_orders')->visibleTo($request->user())->get();

        return view('sales_orders.index', compact(
            'salesOrders', 'search', 'status', 'statuses',
            'customers', 'vessels', 'currencies', 'savedViews',
            'customerId', 'vesselId', 'currency', 'dateFrom', 'dateTo',
            'totalMin', 'totalMax', 'hasContract'
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
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'vessel', 'workOrder', 'creator', 'items', 'quote', 'contract', 'openFollowUps.creator']);

        $quote = $salesOrder->quote;
        $contract = $salesOrder->contract;
        $workOrder = $salesOrder->workOrder;

        $subjects = [[SalesOrder::class, $salesOrder->id]];
        if ($quote) $subjects[] = [Quote::class, $quote->id];
        if ($contract) $subjects[] = [Contract::class, $contract->id];
        if ($workOrder) $subjects[] = [WorkOrder::class, $workOrder->id];

        $timeline = ActivityLog::query()
            ->with(['actor', 'subject'])
            ->where(function ($q) use ($subjects) {
                foreach ($subjects as [$type, $id]) {
                    $q->orWhere(function ($sub) use ($type, $id) {
                        $sub->where('subject_type', $type)->where('subject_id', $id);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('sales_orders.show', compact('salesOrder', 'quote', 'contract', 'workOrder', 'timeline'));
    }

    public function confirm(SalesOrder $salesOrder)
    {
        return $this->transitionStatus($salesOrder, 'draft', 'confirmed', 'Satış siparişi onaylandı.');
    }

    public function start(SalesOrder $salesOrder)
    {
        return $this->transitionStatus($salesOrder, 'confirmed', 'in_progress', 'Satış siparişi devam ediyor.');
    }

    public function complete(SalesOrder $salesOrder)
    {
        return $this->transitionStatus($salesOrder, 'in_progress', 'completed', 'Satış siparişi tamamlandı.');
    }

    public function cancel(SalesOrder $salesOrder)
    {
        if ($response = $this->authorizeSalesOrder('update', $salesOrder)) {
            return $response;
        }

        if (in_array($salesOrder->status, ['completed', 'cancelled', 'contracted'], true)) {
            return back()->with('warning', 'Satış siparişi zaten kapalı.');
        }

        if (! $salesOrder->transitionTo('cancelled', ['source' => 'cancel'])) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        return back()->with('success', 'Satış siparişi iptal edildi.');
    }

    public function edit(SalesOrder $salesOrder)
    {
        if ($salesOrder->isLocked()) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        }

        if ($response = $this->authorizeSalesOrder('update', $salesOrder)) {
            return $response;
        }

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
        if ($salesOrder->isLocked()) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        }

        if ($response = $this->authorizeSalesOrder('update', $salesOrder)) {
            return $response;
        }

        $validated = $request->validate($this->rules(), $this->messages());

        $nextStatus = $validated['status'];
        $payload = $validated;
        unset($payload['status']);

        if (! $salesOrder->canTransitionTo($nextStatus)) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Durum geçişine izin verilmiyor.');
        }

        if ($salesOrder->status !== $nextStatus) {
            $salesOrder->transitionTo($nextStatus, ['source' => 'update']);
        }

        $salesOrder->fill($payload)->save();

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Satış siparişi güncellendi.');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if ($salesOrder->isLocked()) {
            app(ActivityLogger::class)->log($salesOrder, 'delete_blocked', [
                'reason' => 'locked',
            ]);
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Bu siparişin bağlı sözleşmesi olduğu için silinemez.');
        }

        if ($response = $this->authorizeSalesOrder('delete', $salesOrder)) {
            return $response;
        }

        $quote = $salesOrder->quote()->first();

        \Illuminate\Support\Facades\DB::transaction(function () use ($salesOrder, $quote) {
            $salesOrder->delete();

            if ($quote && $quote->status === 'converted' && ! $quote->salesOrder()->exists()) {
                // mümkünse ActivityLog’dan restore et
                $restore = 'accepted';

                $logs = ActivityLog::query()
                    ->where('subject_type', $quote->getMorphClass())
                    ->where('subject_id', $quote->getKey())
                    ->where('action', 'status_changed')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                $lastConverted = $logs->first(fn($l) => data_get($l->meta, 'to') === 'converted');
                $restore = data_get($lastConverted?->meta, 'from') ?: 'accepted';

                $quote->forceFill(['status' => $restore])->save();

                // log
                app(ActivityLogger::class)->log($quote, 'sales_order_deleted', [
                    'restored_status' => $restore,
                    'sales_order_id' => $salesOrder->id,
                    'sales_order_no' => $salesOrder->order_no ?? null,
                ]);
            }
        });

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

    private function transitionStatus(SalesOrder $salesOrder, string $from, string $to, string $message)
    {
        if ($response = $this->authorizeSalesOrder('update', $salesOrder)) {
            return $response;
        }

        if ($salesOrder->status !== $from) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        if (! $salesOrder->transitionTo($to, ['source' => 'status_action'])) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        return back()->with('success', $message);
    }

    private function authorizeSalesOrder(string $ability, SalesOrder $salesOrder)
    {
        try {
            $this->authorize($ability, $salesOrder);
        } catch (AuthorizationException $exception) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', $exception->getMessage() ?: 'Bu işlem için yetkiniz yok.');
        }

        return null;
    }
}
