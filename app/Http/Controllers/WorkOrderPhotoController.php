<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Support\TenantGuard;

class WorkOrderPhotoController extends Controller
{
    use TenantGuard;

    public function store(Request $request, WorkOrder $workOrder)
    {
        $this->checkTenant($workOrder);

        // Simple auth check similar to other controllers
        if ($request->user()->cannot('update', $workOrder)) {
            abort(403);
        }

        $request->validate([
            'photo' => 'required|image|max:10240', // 10MB max
            'type' => 'required|in:before,after',
        ]);

        $file = $request->file('photo');
        $path = $file->store('work-orders/photos', 'public');

        $workOrder->photos()->create([
            'type' => $request->input('type'),
            'path' => $path,
            'caption' => $request->input('caption'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Fotoğraf yüklendi.');
    }

    public function destroy(WorkOrderPhoto $photo)
    {
        $workOrder = $photo->workOrder;
        
        $this->checkTenant($workOrder); // Indirectly checks both

        if (request()->user()->cannot('update', $workOrder)) {
            abort(403);
        }

        if ($photo->path) {
            Storage::disk('public')->delete($photo->path);
        }

        $photo->delete();

        return back()->with('success', 'Fotoğraf silindi.');
    }
}
