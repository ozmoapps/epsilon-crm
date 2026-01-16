<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderProgress;
use Illuminate\Http\Request;

class WorkOrderProgressController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder)
    {
        if ($request->user()->cannot('update', $workOrder)) {
            abort(403);
        }

        $validated = $request->validate([
            'progress_percent' => 'required|integer|min:0|max:100',
            'label' => 'nullable|string|max:255',
        ]);

        // We only keep one progress record per work order (for now) or update latest
        // For simplicity and to match the "Progress Bar" feel, we can either update existing row or create new.
        // Let's use updateOrCreate based on work_order_id to keep it single row for simplest logic,
        // OR allow history. The requirement says "Hakediş Kartı", let's assume single current progress but tracking history might be nice.
        // Let's create a new record to keep history of progress changes, but UI will likely show the LATEST.

        $workOrder->progress()->create([
            'progress_percent' => $validated['progress_percent'],
            'label' => $validated['label'] ?? 'İlerleme Güncellemesi',
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Hakediş/İlerleme durumu güncellendi.');
    }
}
