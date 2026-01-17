<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\FollowUp;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Support\TenantGuard;

class FollowUpController extends Controller
{
    use TenantGuard;

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

        // Tenant Security Check: Ensure subject belongs to tenant
        $subjectClass = $validated['subject_type'];
        $exists = $subjectClass::where('id', $validated['subject_id'])
            ->where('tenant_id', app(\App\Services\TenantContext::class)->id())
            ->exists();

        if (!$exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'subject_id' => __('Seçilen kayıt bulunamadı veya erişim yetkiniz yok.'),
            ]);
        }

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
        $this->checkTenant($followUp);

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
        $this->checkTenant($followUp);

        if ($followUp->created_by !== auth()->id()) {
            abort(403, 'Sadece oluşturan silebilir.');
        }

        $followUp->delete();

        return back()->with('success', 'Takip silindi.');
    }
}
