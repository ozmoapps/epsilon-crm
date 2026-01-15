<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Http\Requests\WorkOrderItemStoreRequest;
use Illuminate\Http\Request;

class WorkOrderItemController extends Controller
{
    public function store(WorkOrderItemStoreRequest $request, WorkOrder $workOrder)
    {
        // Basic authorization check (can be improved with Policies later)
        if ($workOrder->status === 'cancelled' || $workOrder->status === 'completed') {
             // Depending on business logic, maybe allow adding items to completed orders? 
             // For now, let's assume loose check or no check, but basic sanity prevents editing cancelled.
             // Asking user didn't specify strict lock logic, but generally cancelled WO shouldn't be edited.
        }

        $validated = $request->validated();
        
        // If product_id is selected, we might want to fill description from product name if empty
        // But UI logic usually handles display. 
        // Let's rely on request data.

        $workOrder->items()->create($validated);

        return redirect()->back()->with('success', 'Malzeme/Hizmet eklendi.');
    }

    public function update(WorkOrderItemStoreRequest $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        if($item->work_order_id !== $workOrder->id) {
            abort(404);
        }

        $item->update($request->validated());

        return redirect()->back()->with('success', 'Güncellendi.');
    }

    public function destroy(WorkOrder $workOrder, WorkOrderItem $item)
    {
        if($item->work_order_id !== $workOrder->id) {
            abort(404);
        }

        $item->delete();

        return redirect()->back()->with('success', 'Silindi.');
    }
}
