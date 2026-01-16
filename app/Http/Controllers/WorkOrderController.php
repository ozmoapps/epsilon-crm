<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vessel;
use App\Models\WorkOrder;
use App\Models\CompanyProfile;
use App\Models\BankAccount;
use App\Models\SalesOrder;
use App\Models\Quote;
use App\Models\Contract;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        $vesselId = $request->input('vessel_id');
        $plannedFrom = $request->input('planned_from');
        $plannedTo = $request->input('planned_to');

        $workOrders = WorkOrder::query()
            ->with(['customer', 'vessel'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($vesselId, fn ($q) => $q->where('vessel_id', $vesselId))
            ->when($plannedFrom, fn ($q) => $q->whereDate('planned_start_at', '>=', $plannedFrom))
            ->when($plannedTo, fn ($q) => $q->whereDate('planned_start_at', '<=', $plannedTo))
            ->orderByDesc('planned_start_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = WorkOrder::statusOptions();
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $vessels = Vessel::with('customer')->orderBy('name')->get(['id', 'name', 'customer_id']);

        $savedViews = \App\Models\SavedView::allow('work_orders')->visibleTo($request->user())->get();

        return view('work_orders.index', compact(
            'workOrders', 'search', 'status', 'statuses', 
            'customers', 'vessels', 'savedViews',
            'customerId', 'vesselId', 'plannedFrom', 'plannedTo'
        ));
    }

    public function create()
    {
        $this->authorize('create', WorkOrder::class);
        return view('work_orders.create', [
            'workOrder' => new WorkOrder(),
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::orderBy('name')->get(),
            'statuses' => WorkOrder::statusOptions(),
        ]);
    }

    public function store(\App\Http\Requests\WorkOrderStoreRequest $request)
    {
        $this->authorize('create', WorkOrder::class);
        $validated = $request->validated();

        $validated['created_by'] = $request->user()->id;
        WorkOrder::create($validated);

        return redirect()->route('work-orders.index')
            ->with('success', 'İş emri oluşturuldu.');
    }

    public function show(WorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);
        $workOrder->load(['customer', 'vessel', 'openFollowUps.creator']);

        $salesOrder = SalesOrder::query()->with(['contract', 'quote'])->where('work_order_id', $workOrder->id)->latest('id')->first();
        $quote = $salesOrder?->quote ?: Quote::query()->where('work_order_id', $workOrder->id)->latest('id')->first();
        $contract = $salesOrder?->contract;

        $subjects = [[WorkOrder::class, $workOrder->id]];
        if ($quote) $subjects[] = [Quote::class, $quote->id];
        if ($salesOrder) $subjects[] = [SalesOrder::class, $salesOrder->id];
        if ($contract) $subjects[] = [Contract::class, $contract->id];
        $workOrder->load(['customer', 'vessel', 'creator', 'items.product']);

        $timeline = $workOrder->activityLogs()
            ->with('causer')
            ->latest()
            ->get();

        $products = Product::select('id', 'name', 'type', 'sku')->orderBy('name')->get();

        // Operation Flow Logic
        $user = auth()->user();
        $isAdmin = $user->is_admin;

        $operationFlow = [
            [
                'label' => 'Teklif',
                'completed' => $quote && in_array($quote->status, ['accepted', 'converted']),
                'status_label' => $quote?->status_label ?? 'Yok',
                'status_variant' => $quote ? ($quote->status === 'draft' ? 'neutral' : 'success') : 'neutral',
                'href' => $quote && $isAdmin ? route('quotes.show', $quote) : null,
                'locked' => $quote && !$isAdmin,
            ],
            [
                'label' => 'Satış Siparişi',
                'completed' => $salesOrder && in_array($salesOrder->status, ['confirmed', 'in_progress', 'completed', 'contracted']),
                'status_label' => $salesOrder?->status_label ?? 'Yok',
                'status_variant' => $salesOrder ? ($salesOrder->status === 'draft' ? 'neutral' : 'success') : 'neutral',
                'href' => $salesOrder && $isAdmin ? route('sales-orders.show', $salesOrder) : null,
                'locked' => $salesOrder && !$isAdmin,
            ],
            [
                'label' => 'Sözleşme',
                'completed' => (bool)$contract,
                'status_label' => $contract ? 'Mevcut' : 'Yok',
                'status_variant' => $contract ? 'success' : 'neutral',
                'href' => $contract && $isAdmin ? route('contracts.show', $contract) : null,
                'locked' => $contract && !$isAdmin,
            ],
            [
                'label' => 'İş Emri',
                'completed' => in_array($workOrder->status, ['completed', 'delivered']),
                'status_label' => $workOrder->status_label,
                'status_variant' => in_array($workOrder->status, ['completed', 'delivered']) ? 'success' : 'info',
                'href' => null, // Already here
                'locked' => false,
            ],
            [
                'label' => 'Fotoğraflar',
                'completed' => $workOrder->photos()->exists(), // We could optimize this count if eager loaded count
                'status_label' => $workOrder->photos()->exists() ? 'Yüklendi' : 'Eksik',
                'status_variant' => $workOrder->photos()->exists() ? 'success' : 'warning',
                'href' => null,
                'locked' => false,
            ],
            [
                'label' => 'Teslimat',
                'completed' => in_array($workOrder->status, ['delivered']),
                'status_label' => $workOrder->status === 'delivered' ? 'Teslim Edildi' : 'Bekleniyor',
                'status_variant' => $workOrder->status === 'delivered' ? 'success' : 'neutral',
                'href' => null,
                'locked' => false,
            ]
        ];

        return view('work_orders.show', compact('workOrder', 'timeline', 'products', 'operationFlow', 'salesOrder', 'quote', 'contract'));
    }

    public function printView(WorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);
        $workOrder->load(['customer', 'vessel']);
        $companyProfile = CompanyProfile::current();
        $bankAccounts = BankAccount::query()->with('currency')->orderBy('bank_name')->get();

        return view('work_orders.print', compact('workOrder', 'companyProfile', 'bankAccounts'));
    }

    public function edit(WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        return view('work_orders.edit', [
            'workOrder' => $workOrder,
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::orderBy('name')->get(),
            'statuses' => WorkOrder::statusOptions(),
        ]);
    }

    public function update(\App\Http\Requests\WorkOrderUpdateRequest $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        $validated = $request->validated();

        $workOrder->update($validated);

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'İş emri güncellendi.');
    }

    public function destroy(WorkOrder $workOrder)
    {
        $this->authorize('delete', $workOrder);
        $workOrder->delete();

        return redirect()->route('work-orders.index')
            ->with('success', 'İş emri silindi.');
    }

    public function postStock(Request $request, WorkOrder $workOrder, \App\Services\StockService $stockService)
    {
        // Simple authorization
        // $this->authorize('update', $workOrder); 

        if ($workOrder->stock_posted_at) {
            return redirect()->back()->with('info', 'Bu iş emri için stok zaten düşülmüş.');
        }

        $warehouseId = $request->input('warehouse_id');
        if (!$warehouseId) {
            // Fallback to default warehouse if not selected
            $defaultWarehouse = \App\Models\Warehouse::where('is_default', true)->first();
            if (!$defaultWarehouse) {
                return redirect()->back()->with('error', 'Lütfen bir depo seçin (Varsayılan depo bulunamadı).');
            }
            $warehouseId = $defaultWarehouse->id;
        }

        // Process items
        $items = $workOrder->items()->with('product')->get();
        $processedCount = 0;

        foreach ($items as $item) {
            if ($item->product_id && $item->product && $item->product->track_stock) {
                $stockService->createMovement(
                    warehouseId: $warehouseId,
                    productId: $item->product_id,
                    qty: $item->qty,
                    direction: 'out',
                    type: 'workorder_consume',
                    reference: $workOrder,
                    note: "İş Emri #{$workOrder->id} - Malzeme Kullanımı",
                    userId: $request->user()->id
                );
                $processedCount++;
            }
        }

        $workOrder->update([
            'stock_posted_at' => now(),
            'stock_posted_warehouse_id' => $warehouseId,
            'stock_posted_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', "Stok düşüşü gerçekleştirildi ({$processedCount} kalem).");
    }
}
