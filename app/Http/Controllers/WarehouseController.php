<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

use App\Support\TenantGuard;

class WarehouseController extends Controller
{
    use TenantGuard;

    public function index()
    {
        $warehouses = Warehouse::where('tenant_id', app(\App\Services\TenantContext::class)->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                \Illuminate\Validation\Rule::unique('warehouses')->where(fn ($query) => $query->where('tenant_id', app(\App\Services\TenantContext::class)->id()))
            ],
            'is_default' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        if ($request->boolean('is_default')) {
            Warehouse::where('tenant_id', app(\App\Services\TenantContext::class)->id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        // Model hook sets tenant_id
        Warehouse::create($validated);

        return redirect()->back()->with('success', 'Depo oluşturuldu.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $this->checkTenant($warehouse);

        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                \Illuminate\Validation\Rule::unique('warehouses')->ignore($warehouse)->where(fn ($query) => $query->where('tenant_id', app(\App\Services\TenantContext::class)->id()))
            ],
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        if ($request->boolean('is_default')) {
            Warehouse::where('tenant_id', app(\App\Services\TenantContext::class)->id())
                ->where('id', '!=', $warehouse->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            $validated['is_active'] = true; // Default warehouse must be active
        }

        $warehouse->update($validated);

        return redirect()->back()->with('success', 'Depo güncellendi.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->checkTenant($warehouse);

        if ($warehouse->stockMovements()->exists()) {
            return redirect()->back()->with('error', 'Bu depoda stok hareketleri mevcut, silinemez. Pasife alabilirsiniz.');
        }

        if ($warehouse->is_default) {
            return redirect()->back()->with('error', 'Varsayılan depo silinemez.');
        }

        $warehouse->delete();

        return redirect()->back()->with('success', 'Depo silindi.');
    }
}
