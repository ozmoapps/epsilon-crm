<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vessel;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $workOrders = WorkOrder::query()
            ->with(['customer', 'vessel'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('planned_start_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = WorkOrder::statusOptions();

        return view('work_orders.index', compact('workOrders', 'search', 'status', 'statuses'));
    }

    public function create()
    {
        return view('work_orders.create', [
            'workOrder' => new WorkOrder(),
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::orderBy('name')->get(),
            'statuses' => WorkOrder::statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        WorkOrder::create($validated);

        return redirect()->route('work-orders.index')
            ->with('success', 'İş emri oluşturuldu.');
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'vessel']);

        return view('work_orders.show', compact('workOrder'));
    }

    public function edit(WorkOrder $workOrder)
    {
        return view('work_orders.edit', [
            'workOrder' => $workOrder,
            'customers' => Customer::orderBy('name')->get(),
            'vessels' => Vessel::orderBy('name')->get(),
            'statuses' => WorkOrder::statusOptions(),
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $workOrder->update($validated);

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'İş emri güncellendi.');
    }

    public function destroy(WorkOrder $workOrder)
    {
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
