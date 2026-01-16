<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkOrderUpdateController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder)
    {
        if ($request->user()->cannot('update', $workOrder)) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => 'required|string|max:1000',
            'photo' => 'nullable|image|max:10240', // 10MB
            'happened_at' => 'nullable|date',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('work-orders/updates', 'public');
        }

        $workOrder->updates()->create([
            'note' => $validated['note'],
            'photo_path' => $photoPath,
            'happened_at' => $validated['happened_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'İlerleme kaydı eklendi.');
    }

    public function destroy(WorkOrderUpdate $update)
    {
        $workOrder = $update->workOrder;

        // Allow if user is admin OR user is the creator
        if (!request()->user()->is_admin && request()->user()->id !== $update->created_by) {
             abort(403);
        }
        
        // Also check if they can update the work order generally (doubly safe)
        if (request()->user()->cannot('update', $workOrder)) {
            abort(403);
        }

        if ($update->photo_path) {
            Storage::disk('public')->delete($update->photo_path);
        }

        $update->delete();

        return back()->with('success', 'Kayıt silindi.');
    }
}
