<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Vessel;
use Illuminate\Http\Request;

class ApiLookupController extends Controller
{
    /**
     * Get vessels for a specific customer.
     * 
     * @param Customer $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function vesselsByCustomer(Customer $customer)
    {
        // Authorization check if needed, though often internal lookups are open to auth'd users
        // $this->authorize('view', $customer); 

        $vessels = $customer->vessels()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($v) => [
                'value' => $v->id,
                'label' => $v->name
            ]);

        return response()->json($vessels);
    }

    /**
     * Get a specific vessel details including owner.
     * 
     * @param Vessel $vessel
     * @return \Illuminate\Http\JsonResponse
     */
    public function vesselDetail(Vessel $vessel)
    {
        // $this->authorize('view', $vessel);

        $vessel->load('customer:id,name');

        return response()->json([
            'id' => $vessel->id,
            'name' => $vessel->name,
            'customer_id' => $vessel->customer_id,
            'customer_name' => $vessel->customer ? $vessel->customer->name : null,
        ]);
    }

    /**
     * Search vessels globally.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchVessels(Request $request)
    {
        $query = $request->input('query');
        
        $vessels = Vessel::query()
            ->with('customer:id,name')
            ->when($query, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->orderBy('name')
            ->get()
            ->map(function ($v) {
                return [
                    'value' => $v->id,
                    'label' => $v->name,
                    'customer_id' => $v->customer_id,
                    'customer_name' => $v->customer ? $v->customer->name : null,
                ];
            });

        return response()->json($vessels);
    }
}
