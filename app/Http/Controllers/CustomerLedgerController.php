<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class CustomerLedgerController extends Controller
{
    /**
     * Display a comprehensive ledger statement for the customer.
     */
    public function index(Customer $customer, Request $request)
    {
        // Tenant Guard
        if ($customer->tenant_id !== app(\App\Services\TenantContext::class)->id()) {
            abort(404);
        }

        // Optional: validate filters (minimum)
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'vessel_id'  => ['nullable', 'integer'],
            'type'       => ['nullable', 'string', 'max:50'],
        ]);

        // Base query (filters except date-range)
        $base = LedgerEntry::query()
            ->where('customer_id', $customer->id);

        if ($request->filled('currency')) {
            $base->where('currency', $request->currency);
        }
        if ($request->filled('vessel_id')) {
            $base->where('vessel_id', (int) $request->vessel_id);
        }
        if ($request->filled('type')) {
            $base->where('type', $request->type);
        }

        // Period query = base + date filters + eager loads
        $query = (clone $base)->with(['vessel', 'source']);

        if ($request->filled('start_date')) {
            $query->whereDate('occurred_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('occurred_at', '<=', $request->end_date);
        }

        // Opening balances (before start_date) with SAME filters (currency/vessel/type)
        $openingBalances = collect();
        if ($request->filled('start_date')) {
            $openingBalances = (clone $base)
                ->where('occurred_at', '<', $request->start_date)
                ->selectRaw(
                    "currency, sum(case when direction = 'debit' then amount else -amount end) as balance"
                )
                ->groupBy('currency')
                ->pluck('balance', 'currency');
        }

        // Period entries
        $entries = $query
            ->orderBy('occurred_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Group by currency (statement style)
        $groupedEntries = $entries->groupBy('currency');

        // IMPORTANT: If there is opening balance but no period entries, still show that currency section.
        if ($openingBalances->isNotEmpty()) {
            foreach ($openingBalances as $cur => $bal) {
                if (!$groupedEntries->has($cur)) {
                    $groupedEntries->put($cur, collect());
                }
            }
        }

        // Filter dropdown helpers (optional, but nice)
        $availableCurrencies = LedgerEntry::where('customer_id', $customer->id)
            ->select('currency')
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency');

        $availableTypes = LedgerEntry::where('customer_id', $customer->id)
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $vessels = \App\Models\Vessel::where('customer_id', $customer->id)
            ->where('tenant_id', app(\App\Services\TenantContext::class)->id()) // Ensure validation
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('customers.ledger', [
            'customer'            => $customer,
            'groupedEntries'      => $groupedEntries,
            'openingBalances'     => $openingBalances,
            'request'             => $request,
            'availableCurrencies' => $availableCurrencies,
            'availableTypes'      => $availableTypes,
            'vessels'             => $vessels,
        ]);
    }
}
