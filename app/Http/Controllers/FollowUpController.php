<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\FollowUp;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_type' => [
                'required', 
                Rule::in([
                    Quote::class,
                    SalesOrder::class,
                    Contract::class,
                    WorkOrder::class,
                ])
            ],
            'subject_id' => 'required|integer',
            'next_at' => 'required|date',
            'note' => 'nullable|string|max:800',
        ]);

        FollowUp::create([
            'subject_type' => $validated['subject_type'],
            'subject_id' => $validated['subject_id'],
            'next_at' => $validated['next_at'],
            'note' => $validated['note'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Takip oluşturuldu.');
    }

    public function complete(FollowUp $followUp)
    {
        if ($followUp->completed_at) {
            return back();
        }

        $followUp->update([
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Takip tamamlandı.');
    }

    public function destroy(FollowUp $followUp)
    {
        if ($followUp->created_by !== auth()->id()) {
            abort(403, 'Sadece oluşturan silebilir.');
        }

        $followUp->delete();

        return back()->with('success', 'Takip silindi.');
    }
}
