<?php

namespace App\Observers;

use App\Models\Vessel;
use App\Models\VesselOwnerHistory;

class VesselOwnerHistoryObserver
{
    /**
     * Handle the Vessel "updating" event.
     */
    public function updating(Vessel $vessel): void
    {
        // Only log if customer_id has actually changed
        if (!$vessel->isDirty('customer_id')) {
            return;
        }

        // Create history record
        VesselOwnerHistory::create([
            'vessel_id' => $vessel->id,
            'old_customer_id' => $vessel->getOriginal('customer_id'),
            'new_customer_id' => $vessel->customer_id,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }
}
