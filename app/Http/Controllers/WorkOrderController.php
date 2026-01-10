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

    public function store(Request $request)
    {
        $this->authorize('create', WorkOrder::class);
        $validated = $request->validate($this->rules(), $this->messages());

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

        return view('work_orders.show', compact('workOrder', 'quote', 'salesOrder', 'contract', 'timeline'));
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

    public function update(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        $validated = $request->validate($this->rules(), $this->messages());

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

    private function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
        ];
    }

    private function messages(): array
    {
        return [
            'customer_id.required' => 'Müşteri seçimi zorunludur.',
            'customer_id.exists' => 'Seçilen müşteri geçersiz.',
            'vessel_id.required' => 'Tekne seçimi zorunludur.',
            'vessel_id.exists' => 'Seçilen tekne geçersiz.',
            'title.required' => 'Başlık alanı zorunludur.',
            'title.max' => 'Başlık alanı en fazla 255 karakter olabilir.',
            'status.required' => 'Durum alanı zorunludur.',
            'status.max' => 'Durum alanı en fazla 50 karakter olabilir.',
            'planned_start_at.date' => 'Planlanan başlangıç tarihi geçerli değil.',
            'planned_end_at.date' => 'Planlanan bitiş tarihi geçerli değil.',
            'planned_end_at.after_or_equal' => 'Planlanan bitiş tarihi başlangıç tarihinden önce olamaz.',
        ];
    }

}
