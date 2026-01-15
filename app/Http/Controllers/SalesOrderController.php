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

    public function store(\App\Http\Requests\SalesOrderStoreRequest $request)
    {
        $validated = $request->validated();

        $validated['created_by'] = $request->user()->id;

        $salesOrder = SalesOrder::create($validated);

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Satış siparişi oluşturuldu.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'vessel', 'workOrder', 'creator', 'quote', 'contract', 'openFollowUps.creator']);

        // Load items with aggregated shipment and return quantities
        $salesOrder->load(['items' => function ($query) {
            $query->withSum(['shipmentLines' => function ($q) {
                $q->whereHas('shipment', function ($sq) {
                    $sq->where('status', 'posted');
                });
            }], 'qty');
            
            // For returns, we need to sum return lines linked to shipment lines of this item
            // This is complex via standard relationship without deeper nested hasManyThrough.
            // Simplified approach: Load shipmentLines with their posted return lines sum.
            $query->with(['shipmentLines' => function($q) {
                $q->whereHas('shipment', fn($s) => $s->where('status', 'posted'))
                  ->withSum(['returnLines' => function($rq) {
                        $rq->whereHas('return', fn($r) => $r->where('status', 'posted'));
                  }], 'qty');
            }]);
        }]);

        // Transform items to attach "shipped_qty" and "returned_qty" directly for easier view access
        foreach ($salesOrder->items as $item) {
            $item->shipped_qty = $item->shipment_lines_sum_qty ?? 0;
            // Sum return qty from shipment lines
            $item->returned_qty = $item->shipmentLines->sum('return_lines_sum_qty');
            $item->remaining_qty = max(0, $item->qty - $item->shipped_qty);
        }

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

    public function update(\App\Http\Requests\SalesOrderUpdateRequest $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->isLocked()) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.');
        }

        if ($response = $this->authorizeSalesOrder('update', $salesOrder)) {
            return $response;
        }

        $validated = $request->validated();

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
    public function postStock(\Illuminate\Http\Request $request, SalesOrder $salesOrder, \App\Services\StockService $stockService)
    {
        if ($salesOrder->stock_posted_at) {
            return redirect()->back()->with('info', 'Stok zaten düşüldü.');
        }
        
        if ($salesOrder->shipments()->exists()) {
            return redirect()->back()->with('error', 'Bu siparişe ait sevkiyatlar (taslak veya işlenmiş) mevcut. Lütfen sevkiyat modülünü kullanın veya onları silin.');
        }

        $warehouseId = $request->input('warehouse_id');
        if (!$warehouseId) {
             return redirect()->back()->with('error', 'Depo seçimi zorunludur.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($salesOrder, $warehouseId, $request, $stockService) {
            $salesOrder->lockForUpdate();

            if ($salesOrder->stock_posted_at) {
                return; // Double check inside lock
            }

            foreach ($salesOrder->items as $item) {
                if ($item->product_id && $item->qty > 0) {
                     // Check if product tracks stock ideally, but for now assuming if product_id is there, we try/check service
                     // Actually StockService doesn't check track_stock, so we should check properties if we can or trust the user.
                     // Better: Load product and check track_stock.
                     $product = $item->product; 
                     if ($product && $product->track_stock) {
                        $stockService->createMovement(
                            warehouseId: $warehouseId,
                            productId: $item->product_id,
                            qty: $item->qty,
                            direction: 'out',
                            type: 'sale_out',
                            reference: $salesOrder,
                            note: "Sipariş #{$salesOrder->order_no}",
                            userId: $request->user()->id
                        );
                     }
                }
            }

            $salesOrder->update([
                'stock_posted_at' => now(),
                'stock_posted_warehouse_id' => $warehouseId,
                'stock_posted_by' => $request->user()->id,
            ]);
        });

        return redirect()->back()->with('success', 'Stok düşüşü gerçekleştirildi.');
    }
}
