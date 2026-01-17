<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\SavedView;
use App\Models\Invoice;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerLedgerIndexController extends Controller
{
    public function index(Request $request)
    {
        // Guards
        if (!Schema::hasTable('customers') || !Schema::hasTable('ledger_entries')) {
            abort(500, 'Database tables missing.');
        }

        // Available Currencies (Guard + Fallback)
        $currencies = collect(['TRY', 'USD', 'EUR', 'GBP']);
        if (Schema::hasTable('currencies')) {
            $hasActiveCol = Schema::hasColumn('currencies', 'is_active');
            $dbCurrencies = $hasActiveCol
                ? Currency::where('is_active', true)->pluck('code')
                : Currency::pluck('code');

            if ($dbCurrencies->isNotEmpty()) {
                $currencies = $dbCurrencies;
            }
        }

        // Filters
        $search = $request->input('search');
        $currency = $request->input('currency');
        $onlyNonZero = $request->boolean('only_nonzero');
        $onlyOpenAdvances = $request->boolean('only_open_advances');
        $onlyOpenInvoices = $request->boolean('only_open_invoices');
        $onlyDebtors = $request->boolean('only_debtors');
        $onlyOverdue = false;

        $dueCol = Schema::hasColumn('invoices', 'due_date') ? 'due_date' : null;

        // Quick Filters (Sprint 3.9)
        $quick = $request->input('quick');
        if ($quick === 'open_invoice') {
            $onlyOpenInvoices = true;
        } elseif ($quick === 'open_advance') {
            $onlyOpenAdvances = true;
        } elseif ($quick === 'debtor') {
            $onlyDebtors = true;
        } elseif ($quick === 'overdue' && $dueCol) {
            $onlyOverdue = true;
        }

        $query = Customer::query()->where('tenant_id', app(\App\Services\TenantContext::class)->id());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");

                if (Schema::hasColumn('customers', 'tax_number')) {
                    $q->orWhere('tax_number', 'like', "%{$search}%");
                } elseif (Schema::hasColumn('customers', 'tax_no')) {
                    $q->orWhere('tax_no', 'like', "%{$search}%");
                }
            });
        }

        // Filter by Non-Zero Balance
        if ($onlyNonZero) {
            if ($currency) {
                $query->whereRaw("
                    ABS((
                        SELECT COALESCE(SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END), 0)
                        FROM ledger_entries
                        WHERE ledger_entries.customer_id = customers.id
                        AND ledger_entries.currency = ?
                    )) > 0.001
                ", [$currency]);
            } else {
                $query->whereRaw("
                    EXISTS (
                        SELECT 1
                        FROM ledger_entries le
                        WHERE le.customer_id = customers.id
                        GROUP BY le.currency
                        HAVING ABS(SUM(CASE WHEN le.direction='debit' THEN le.amount ELSE -le.amount END)) > 0.001
                    )
                ");
            }
        }

        // Filter by Open Advances (STRICT: remaining > 0.001)
        if ($onlyOpenAdvances && Schema::hasTable('payments')) {
            $hasAllocTable = Schema::hasTable('payment_allocations');

            $query->whereExists(function ($sq) use ($currency, $hasAllocTable) {
                $sq->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.customer_id', 'customers.id')
                    ->whereNull('payments.invoice_id');

                if ($currency) {
                    $sq->where('payments.original_currency', $currency);
                }

                if ($hasAllocTable) {
                    $amountCol = Schema::hasColumn('payments', 'original_amount') 
                        ? 'COALESCE(payments.original_amount, payments.amount)' 
                        : 'payments.amount';
                    
                    // Correlated subquery to check remaining amount > 0.001
                    $sq->whereRaw("
                        ($amountCol - COALESCE((
                            SELECT SUM(payment_allocations.amount)
                            FROM payment_allocations
                            WHERE payment_allocations.payment_id = payments.id
                        ), 0)) > 0.001
                    ");
                } else {
                    $amountCol = Schema::hasColumn('payments', 'original_amount') 
                        ? 'COALESCE(payments.original_amount, payments.amount)' 
                        : 'payments.amount';
                    $sq->whereRaw("$amountCol > 0.001");
                }
            });
        }

        // Filter by Open Invoices (Sprint 3.6)
        if ($onlyOpenInvoices && Schema::hasTable('invoices')) {
            $query->whereExists(function ($sq) use ($currency) {
                $sq->select(DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('invoices.customer_id', 'customers.id')
                    ->where('invoices.status', 'issued')
                    ->where('invoices.payment_status', '!=', 'paid');

                if ($currency) {
                    $sq->where('invoices.currency', $currency);
                }

                // Legacy payments
                $legacyPaid = "COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0)";
                
                // Allocation payments
                $allocPaid = "0";
                if (Schema::hasTable('payment_allocations')) {
                    $allocPaid = "COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE pa.invoice_id = invoices.id AND p.invoice_id IS NULL), 0)";
                }

                $sq->whereRaw("(invoices.total - $legacyPaid - $allocPaid) > 0.001");
            });
        }

        // Filter by Overdue (Sprint 3.11)
        if ($onlyOverdue && $dueCol && Schema::hasTable('invoices')) {
            $today = now()->toDateString();
            $query->whereExists(function ($sq) use ($currency, $dueCol, $today) {
                $sq->select(DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('invoices.customer_id', 'customers.id')
                    ->where('invoices.status', 'issued')
                    ->where('invoices.payment_status', '!=', 'paid')
                    ->whereDate("invoices.$dueCol", '<', $today);

                if ($currency) {
                    $sq->where('invoices.currency', $currency);
                }

                $paidExpr = "COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = invoices.id), 0)";
                $allocExpr = Schema::hasTable('payment_allocations') ? "COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE pa.invoice_id = invoices.id AND p.invoice_id IS NULL), 0)" : "0";
                
                $sq->whereRaw("(invoices.total - $paidExpr - $allocExpr) > 0.001");
            });
        }

        // Filter by Debtors (Positive Balance) (Sprint 3.6)
        if ($onlyDebtors) {
            if ($currency) {
                $query->whereRaw("
                    (
                        SELECT COALESCE(SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END), 0)
                        FROM ledger_entries
                        WHERE ledger_entries.customer_id = customers.id
                        AND ledger_entries.currency = ?
                    ) > 0.001
                ", [$currency]);
            } else {
                $query->whereRaw("
                    EXISTS (
                        SELECT 1
                        FROM ledger_entries le
                        WHERE le.customer_id = customers.id
                        GROUP BY le.currency
                        HAVING SUM(CASE WHEN le.direction='debit' THEN le.amount ELSE -le.amount END) > 0.001
                    )
                ");
            }
        }

        // DB-Level Sorting (Sprint 3.13) & Scope Aggregation (Sprint 3.14)
        $sort = $request->input('sort', 'open_invoice_desc');
        $scope = $request->input('scope', 'page');

        // Helper for Open Invoice Subquery (Reusable)
        $getOpenInvoiceSub = function($isSort = false) use ($currency) {
            $legacyPaidSub = DB::table('payments')
                ->select('invoice_id', DB::raw('SUM(amount) as paid'))
                ->groupBy('invoice_id');
            
            $allocPaidSub = DB::table('payment_allocations')
                ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
                ->whereNull('payments.invoice_id')
                ->select('payment_allocations.invoice_id', DB::raw('SUM(payment_allocations.amount) as alloc'))
                ->groupBy('payment_allocations.invoice_id');

            $paidExpr = "COALESCE(p.paid, 0)";
            $allocExpr = "COALESCE(a.alloc, 0)";
            $remainingExpr = "CASE WHEN (invoices.total - {$paidExpr} - {$allocExpr}) > 0 THEN (invoices.total - {$paidExpr} - {$allocExpr}) ELSE 0 END";
            
            // Sprint 3.11 Overdue Logic Reuse
            $dueCol = Schema::hasColumn('invoices', 'due_date') ? 'due_date' : null;
            $overdueExpr = ($dueCol) 
                ? "CASE WHEN ({$remainingExpr}) > 0.001 AND invoices.{$dueCol} < DATE('now') THEN ({$remainingExpr}) ELSE 0 END"
                : "0";

            $selects = [
                'invoices.customer_id',
                DB::raw("SUM({$remainingExpr}) as total_open"),
                DB::raw("SUM({$overdueExpr}) as total_overdue"),
                DB::raw("MAX(CASE WHEN ({$remainingExpr}) > 0.001 THEN 1 ELSE 0 END) as has_open"),
                DB::raw("MAX(CASE WHEN ({$overdueExpr}) > 0.001 THEN 1 ELSE 0 END) as has_overdue"),
            ];
            
            if ($isSort) {
                 $q = DB::table('invoices')
                    ->select('invoices.customer_id', DB::raw("SUM({$remainingExpr}) as sort_total_open"))
                    ->leftJoinSub($legacyPaidSub, 'p', 'p.invoice_id', '=', 'invoices.id')
                    ->leftJoinSub($allocPaidSub, 'a', 'a.invoice_id', '=', 'invoices.id')
                    ->where('invoices.status', 'issued')
                    ->where('invoices.payment_status', '!=', 'paid')
                    ->groupBy('invoices.customer_id');
            } else {
                 $q = DB::table('invoices')
                    ->select($selects)
                    ->addSelect('invoices.currency') // Always select currency for aggregation
                    ->leftJoinSub($legacyPaidSub, 'p', 'p.invoice_id', '=', 'invoices.id')
                    ->leftJoinSub($allocPaidSub, 'a', 'a.invoice_id', '=', 'invoices.id')
                    ->where('invoices.status', 'issued')
                    ->where('invoices.payment_status', '!=', 'paid')
                    ->groupBy('invoices.customer_id', 'invoices.currency'); // Always group by currency
            }

            if ($currency) {
                $q->where('invoices.currency', $currency);
            }
            return $q;
        };

        // Helper for Advance Sort/Agg Subquery
        $getAdvanceSub = function($isSort = false) use ($currency) {
             $remainingExpr = "(payments.amount - COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE payment_id = payments.id), 0))";
             
             if ($isSort) {
                 $q = DB::table('payments')
                     ->select('payments.customer_id', DB::raw("SUM({$remainingExpr}) as sort_total_adv"))
                     ->whereNull('payments.invoice_id')
                     ->groupBy('payments.customer_id');
             } else {
                 $q = DB::table('payments')
                     ->select('payments.customer_id', DB::raw("SUM({$remainingExpr}) as total_adv"))
                     ->addSelect('payments.original_currency as currency') // Always select
                     ->whereNull('payments.invoice_id')
                     ->groupBy('payments.customer_id', 'payments.original_currency');
             }
            
            if ($currency) {
                $q->where('payments.original_currency', $currency);
            }
            return $q;
        };

        // Helper for Debt Sort/Agg Subquery
        $getDebtSub = function($isSort = false) use ($currency) {
            $expr = "SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END)";
            
            if ($isSort) {
                $q = DB::table('ledger_entries')
                    ->select('customer_id', DB::raw("{$expr} as sort_balance"))
                    ->groupBy('customer_id');
            } else {
                $q = DB::table('ledger_entries')
                    ->select('customer_id', DB::raw("{$expr} as total_balance"))
                    ->addSelect('currency') // Always select
                    ->groupBy('customer_id', 'currency');
            }
            
            if ($currency) {
                $q->where('currency', $currency);
            }
            return $q;
        };

        switch ($sort) {
            case 'open_invoice_desc':
                $query->leftJoinSub($getOpenInvoiceSub(true), 'sort_oi', 'sort_oi.customer_id', '=', 'customers.id')
                      ->orderByDesc(DB::raw('COALESCE(sort_oi.sort_total_open, 0)'))
                      ->orderBy('customers.name');
                break;
            case 'advance_desc':
                $query->leftJoinSub($getAdvanceSub(true), 'sort_adv', 'sort_adv.customer_id', '=', 'customers.id')
                      ->orderByDesc(DB::raw('COALESCE(sort_adv.sort_total_adv, 0)'))
                      ->orderBy('customers.name');
                break;
            case 'debt_desc':
                $query->leftJoinSub($getDebtSub(true), 'sort_debt', 'sort_debt.customer_id', '=', 'customers.id')
                      ->orderByDesc(DB::raw('COALESCE(sort_debt.sort_balance, 0)'))
                      ->orderBy('customers.name');
                break;
            case 'name_asc':
                $query->orderBy('name');
                break;
            default:
                $query->leftJoinSub($getOpenInvoiceSub(true), 'sort_oi', 'sort_oi.customer_id', '=', 'customers.id')
                      ->orderByDesc(DB::raw('COALESCE(sort_oi.sort_total_open, 0)'))
                      ->orderBy('customers.name');
                break;
        }

        $baseQuery = $query->clone();
        // Pagination with sort applied
        $customers = $query->paginate(20)->withQueryString();

        // Totals (for the totals row)
        $totals = [
            'balances' => [], // signed totals per currency
            'open_advances' => [], // positive totals per currency
            'open_invoices' => [], // per currency: ['amount' => x, 'count' => y]
        ];

        if ($customers->isNotEmpty()) {
            $ids = $customers->pluck('id')->toArray();

            // 1) Balances
            $balances = LedgerEntry::select(
                    'customer_id',
                    'currency',
                    DB::raw("SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END) as balance")
                )
                ->whereIn('customer_id', $ids)
                ->groupBy('customer_id', 'currency')
                ->get()
                ->groupBy('customer_id');

            // 2) Open Advances (remaining per advance payment)
            $advancesByCustomer = [];
            if (Schema::hasTable('payments')) {
                $advPaymentsQ = Payment::whereIn('customer_id', $ids)
                    ->whereNull('invoice_id')
                    ->select(['id', 'customer_id', 'amount', 'original_currency']);
                
                if (Schema::hasColumn('payments', 'original_amount')) {
                    $advPaymentsQ->addSelect('original_amount');
                }

                $advPaymentsQ->with(['allocations:id,payment_id,amount']);

                if ($currency) {
                    $advPaymentsQ->where('original_currency', $currency);
                }

                $advPayments = $advPaymentsQ->get();

                foreach ($advPayments as $p) {
                    $allocated = (float) $p->allocations->sum('amount');
                    // Check for original_amount property safely
                    $origAmt = $p->original_amount ?? null;
                    $effectiveAmount = (float) ($origAmt ?? $p->amount ?? 0);
                    $remaining = max(0, $effectiveAmount - $allocated);

                    if ($remaining > 0.001) {
                        $curr = $p->original_currency ?? ($currency ?: 'EUR');
                        $advancesByCustomer[$p->customer_id][$curr] = ($advancesByCustomer[$p->customer_id][$curr] ?? 0) + $remaining;
                    }
                }
            }

            // 3) Open Invoices Remaining Totals (Sprint 2.8)
            $openInvoiceAggByCustomer = collect();
            $openInvoiceCountsByCustomer = collect();

            if (Schema::hasTable('invoices')) {
                // legacy invoice-linked payments sum by invoice
                $legacyPaidSub = DB::table('payments')
                    ->select('invoice_id', DB::raw('SUM(amount) as paid'))
                    ->whereNotNull('invoice_id')
                    ->groupBy('invoice_id');

                $hasAllocTable = Schema::hasTable('payment_allocations');
                $allocPaidSub = null;

                if ($hasAllocTable) {
                    $allocPaidSub = DB::table('payment_allocations')
                        ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
                        ->whereNull('payments.invoice_id')
                        ->select('payment_allocations.invoice_id', DB::raw('SUM(payment_allocations.amount) as alloc'))
                        ->groupBy('payment_allocations.invoice_id');
                }

                $paidExpr = "COALESCE(p.paid, 0)";
                $allocExpr = $hasAllocTable ? "COALESCE(a.alloc, 0)" : "0";
                $remainingExpr = "CASE WHEN (invoices.total - {$paidExpr} - {$allocExpr}) > 0
                                  THEN (invoices.total - {$paidExpr} - {$allocExpr})
                                  ELSE 0 END";

                $q = Invoice::query()
                    ->select(
                        'invoices.customer_id',
                        'invoices.currency',
                        'invoices.currency',
                        DB::raw("SUM({$remainingExpr}) as remaining_total"),
                        DB::raw("SUM(CASE WHEN ({$remainingExpr}) > 0.001 THEN 1 ELSE 0 END) as count"),
                        DB::raw($dueCol ? "SUM(CASE WHEN ({$remainingExpr}) > 0.001 AND invoices.$dueCol < DATE('now') THEN {$remainingExpr} ELSE 0 END) as overdue_total" : "0 as overdue_total"),
                        DB::raw($dueCol ? "SUM(CASE WHEN ({$remainingExpr}) > 0.001 AND invoices.$dueCol < DATE('now') THEN 1 ELSE 0 END) as overdue_count" : "0 as overdue_count")
                    )
                    ->leftJoinSub($legacyPaidSub, 'p', 'p.invoice_id', '=', 'invoices.id')
                    ->whereIn('invoices.customer_id', $ids)
                    ->where('invoices.status', 'issued')
                    ->where('invoices.payment_status', '!=', 'paid')
                    ->groupBy('invoices.customer_id', 'invoices.currency');

                if ($hasAllocTable) {
                    $q->leftJoinSub($allocPaidSub, 'a', 'a.invoice_id', '=', 'invoices.id');
                }

                if ($currency) {
                    $q->where('invoices.currency', $currency);
                }

                $openRows = $q->get();

                // Group by customer_id
                $openInvoiceAggByCustomer = $openRows->groupBy('customer_id');

                // total count per customer (sum across currencies)
                $openInvoiceCountsByCustomer = $openRows
                    ->groupBy('customer_id')
                    ->map(fn ($rows) => (int) $rows->sum('count'));
            }

            // Attach to customers + compute totals
            foreach ($customers as $customer) {
                // balances collection
                $customer->balances = $balances->get($customer->id);

                // open advances map
                $customer->open_advances = $advancesByCustomer[$customer->id] ?? [];

                // open invoices (currency map + total count)
                $openInvoiceAmts = [];
                $rows = $openInvoiceAggByCustomer->get($customer->id);
                if ($rows && $rows->isNotEmpty()) {
                    foreach ($rows as $r) {
                        $curr = $r->currency;
                        $amt = (float) $r->remaining_total;
                        $cnt = (int) $r->count;
                        if ($amt > 0.001 || $cnt > 0) {
                            $openInvoiceAmts[$curr] = [
                                'amount' => $amt,
                                'count' => $cnt,
                            ];
                        }
                    }
                }
                $customer->open_invoice_amounts = $openInvoiceAmts;
                $customer->open_invoice_count = (int) ($openInvoiceCountsByCustomer->get($customer->id, 0));

                $overdueAmts = [];
                if ($rows && $rows->isNotEmpty()) {
                    foreach ($rows as $r) {
                         $odAmt = (float) $r->overdue_total;
                         $odCnt = (int) $r->overdue_count;
                         if ($odAmt > 0.001 || $odCnt > 0) {
                             $overdueAmts[$r->currency] = ['amount' => $odAmt, 'count' => $odCnt];
                         }
                    }
                }
                $customer->overdue_invoice_amounts = $overdueAmts;

                // Totals: balances
                if ($customer->balances && $customer->balances->isNotEmpty()) {
                    foreach ($customer->balances as $bal) {
                        $c = $bal->currency;
                        $v = (float) $bal->balance;
                        if (abs($v) > 0.001) {
                            $totals['balances'][$c] = ($totals['balances'][$c] ?? 0) + $v;
                        }
                    }
                }

                // Totals: open advances
                if (!empty($customer->open_advances)) {
                    foreach ($customer->open_advances as $c => $amt) {
                        $v = (float) $amt;
                        if ($v > 0.001) {
                            $totals['open_advances'][$c] = ($totals['open_advances'][$c] ?? 0) + $v;
                        }
                    }
                }

                // Totals: open invoices
                if (!empty($customer->open_invoice_amounts)) {
                    foreach ($customer->open_invoice_amounts as $c => $data) {
                        $amt = (float) ($data['amount'] ?? 0);
                        $cnt = (int) ($data['count'] ?? 0);
                        if ($amt > 0.001 || $cnt > 0) {
                            if (!isset($totals['open_invoices'][$c])) {
                                $totals['open_invoices'][$c] = ['amount' => 0, 'count' => 0];
                            }
                            $totals['open_invoices'][$c]['amount'] += $amt;
                            $totals['open_invoices'][$c]['count'] += $cnt;
                        }
                    }
                }

                // Totals: overdue invoices (Sprint 3.12)
                if (!empty($customer->overdue_invoice_amounts)) {
                    foreach ($customer->overdue_invoice_amounts as $c => $data) {
                        $amt = (float) ($data['amount'] ?? 0);
                        $cnt = (int) ($data['count'] ?? 0);
                        if ($amt > 0.001 || $cnt > 0) {
                            if (!isset($totals['overdue_invoices'][$c])) {
                                $totals['overdue_invoices'][$c] = ['amount' => 0, 'count' => 0];
                            }
                            $totals['overdue_invoices'][$c]['amount'] += $amt;
                            $totals['overdue_invoices'][$c]['count'] += $cnt;
                        }
                    }
                }
            }



            // In-Memory Sorting removed in favor of DB-Level Sorting (Sprint 3.13)
        }

        // KPI Calculation (This Page)
        $kpi = [
            'open_invoice_customers' => 0,
            'overdue_customers' => 0,
            'open_advance_customers' => 0,
            'debt_customers' => 0,
        ];

        foreach ($customers as $c) {
            // Open Invoice Customer Check
            $invTotal = 0;
            if (!empty($c->open_invoice_amounts)) {
                $invTotal = collect($c->open_invoice_amounts)->sum('amount');
            }
            if ($invTotal > 0.001) $kpi['open_invoice_customers']++;

            // Overdue Customer Check
            $odTotal = 0;
            if (!empty($c->overdue_invoice_amounts)) {
                $odTotal = collect($c->overdue_invoice_amounts)->sum('amount');
            }
            if ($odTotal > 0.001) $kpi['overdue_customers']++;

            // Open Advance Customer Check
            $advTotal = 0;
            if (!empty($c->open_advances)) {
                $advTotal = collect($c->open_advances)->sum();
            }
            if ($advTotal > 0.001) $kpi['open_advance_customers']++;

            // Debt Customer Check (Positive Balance)
            $debtTotal = 0;
            if (!empty($c->balances)) {
                $debtTotal = $c->balances->sum(fn($b) => max(0, $b->balance));
            }
            if ($debtTotal > 0.001) $kpi['debt_customers']++;
        }

        // SCOPE: Global Aggregation (Sprint 3.14)
        if ($scope === 'all') {
            // Reset KPI & Totals to Global values
            $kpi = [
                'open_invoice_customers' => 0,
                'overdue_customers' => 0,
                'open_advance_customers' => 0,
                'debt_customers' => 0,
            ];
            $totals = [
                'balances' => [], 
                'open_advances' => [], 
                'open_invoices' => [],
                'overdue_invoices' => []
            ];

            // 1. Open Invoice & Overdue Stats (Grouped by Currency)
            // We use the helper which returns a query grouped by customer_id AND currency (if false passed)
            // Wait, helper with false passes `groupBy('invoices.customer_id')` and potentially `groupBy('invoices.currency')`.
            // If currency param is set, it filters. If not, we need grouping.
            // My helper implementation for `false` (agg mode) does: `groupBy('invoices.customer_id')` and if not specific currency, adds `groupBy('invoices.currency')`.
            // So the result is (customer, currency) rows. Perfect for aggregation.
            
            $oiSub = $getOpenInvoiceSub(false);
            $oiStats = DB::query()->fromSub($oiSub, 'sub')
                ->select(
                    'sub.currency',
                    DB::raw('SUM(total_open) as grand_open'),
                    DB::raw('SUM(total_overdue) as grand_overdue')
                    // Count of customers is tricky with currency grouping if we want Total Unique Customers regardless of currency.
                    // But usually KPI count is just unique customers.
                    // Totals are per currency.
                )
                ->groupBy('sub.currency')
                ->get();

            // Populate Amounts
            foreach ($oiStats as $row) {
                // If currency was filtered, row->currency might be missing in select if I didn't add it explicitly?
                // Helper logic: if ($currency) { $q->where... } else { $q->addSelect... }
                // If $currency is set, we know the currency.
                $c = $currency ?: $row->currency; 
                
                $totals['open_invoices'][$c] = ['amount' => (float)$row->grand_open, 'count' => 0]; 
                $totals['overdue_invoices'][$c] = ['amount' => (float)$row->grand_overdue, 'count' => 0];
            }

            // Global Counts (Unique Customers) - Independent of Currency Grouping for "Customers Check"
            // We need a separate query for accurate distinct customer counts covering all currencies used (if multiple).
            // Actually, if we use the helper without currency grouping (just customer grouping), we can count.
            // But the helper adds currency grouping if !$currency.
            // Let's just run a count query on the filtered Customers Query + Exists logic.
            // Re-using helper is good for logic, but might be heavy.
            // Faster way: Use the base Logic directly for counts.
            
            // Re-use logic for counts:
             $kpi['open_invoice_customers'] = DB::table('customers')
                ->joinSub($baseQuery->select('customers.id'), 'filtered_c', 'filtered_c.id', '=', 'customers.id')
                ->joinSub($getOpenInvoiceSub(false), 'oi_det', function($join) {
                    $join->on('oi_det.customer_id', '=', 'customers.id');
                    // If currency grouping exists, we join on customer_id, it implicitly cross joins currencies if customer has multiple?
                    // Yes, but we just check `MAX(has_open)`.
                })
                ->where('oi_det.has_open', 1)
                ->count(DB::raw('DISTINCT customers.id'));

             $kpi['overdue_customers'] = DB::table('customers')
                ->joinSub($baseQuery->select('customers.id'), 'filtered_c', 'filtered_c.id', '=', 'customers.id')
                ->joinSub($getOpenInvoiceSub(false), 'oi_det', function($join) {
                     $join->on('oi_det.customer_id', '=', 'customers.id');
                })
                ->where('oi_det.has_overdue', 1)
                ->count(DB::raw('DISTINCT customers.id'));

            // 2. Advance Stats
            $advSub = $getAdvanceSub(false);
            $advStats = DB::query()->fromSub($advSub, 'sub')
                ->select('sub.currency', DB::raw('SUM(total_adv) as grand_adv'))
                ->groupBy('sub.currency')
                ->get();
            
            foreach ($advStats as $row) {
                $c = $currency ?: $row->currency;
                $totals['open_advances'][$c] = (float)$row->grand_adv;
            }

            $kpi['open_advance_customers'] = DB::table('customers')
                ->joinSub($baseQuery->select('customers.id'), 'filtered_c', 'filtered_c.id', '=', 'customers.id')
                ->joinSub($getAdvanceSub(false), 'adv_det', 'adv_det.customer_id', '=', 'customers.id')
                ->where('adv_det.total_adv', '>', 0.001)
                ->count(DB::raw('DISTINCT customers.id'));

            // 3. Debt Stats
            $debtSub = $getDebtSub(false);
            $debtStats = DB::query()->fromSub($debtSub, 'sub')
                ->select('sub.currency', DB::raw('SUM(total_balance) as grand_balance'))
                ->groupBy('sub.currency')
                ->get();

            foreach ($debtStats as $row) {
                $c = $currency ?: $row->currency; 
                $totals['balances'][$c] = (float)$row->grand_balance;
            }

            $kpi['debt_customers'] = DB::table('customers')
                ->joinSub($baseQuery->select('customers.id'), 'filtered_c', 'filtered_c.id', '=', 'customers.id')
                ->joinSub($getDebtSub(false), 'debt_det', 'debt_det.customer_id', '=', 'customers.id')
                ->where('debt_det.total_balance', '>', 0.001)
                ->count(DB::raw('DISTINCT customers.id'));
        }

        // Saved Views (Sprint 3.7)
        $savedViews = SavedView::allow('customer_ledgers')
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get();

        // Grand Totals & KPIs (Sprint 3.8)
        $grandTotals = [
            'balances' => [],
            'open_advances' => [],
            'open_invoices' => []
        ];
        $grandKpis = [
            'open_invoice_customers' => 0,
            'open_advance_customers' => 0,
            'debt_customers' => 0
        ];

        // Only calculate if there are results
        if ($customers->total() > 0) {
            $grandIdsQuery = $baseQuery->clone()->reorder()->select('customers.id');

            // 1. Grand Balances
            $gBalRows = LedgerEntry::select('currency', DB::raw("SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END) as balance"))
                ->whereIn('customer_id', $grandIdsQuery)
                ->groupBy('currency')
                ->get();
            foreach ($gBalRows as $r) {
                if (abs($r->balance) > 0.001) {
                    $grandTotals['balances'][$r->currency] = (float)$r->balance;
                }
            }

            // 2. Grand Open Advances (Payment - Allocations)
            if (Schema::hasTable('payments')) {
                // Using Payment model logic with aggregation

                
                // For accurate Grand Total, we need: SUM(GREATEST(0, amount - allocated)) GROUP BY Currency.
                // We can do this via whereRaw filter then SUM.
                
                // Let's use a cleaner query for Open Advances Total:
                $pGroup = Payment::whereIn('customer_id', $grandIdsQuery)
                    ->whereNull('invoice_id');
                if ($currency) $pGroup->where('original_currency', $currency);
                
                $hasAlloc = Schema::hasTable('payment_allocations');
                $amtCol = Schema::hasColumn('payments', 'original_amount') ? 'COALESCE(original_amount, amount)' : 'amount';
                
                $allocSub = $hasAlloc ? "COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE payment_id = payments.id), 0)" : "0";
                
                $grandAdvRows = $pGroup->select(
                    DB::raw($currency ? "'$currency' as curr" : "COALESCE(original_currency, 'EUR') as curr"),
                    DB::raw("SUM(CASE WHEN ($amtCol - $allocSub) > 0 THEN ($amtCol - $allocSub) ELSE 0 END) as remaining_total")
                )
                ->groupBy('curr')
                ->get();

                foreach ($grandAdvRows as $r) {
                    if ($r->remaining_total > 0.001) {
                        $grandTotals['open_advances'][$r->curr] = (float)$r->remaining_total;
                    }
                }
            }

            // 3. Grand Open Invoices (Remaining)
            if (Schema::hasTable('invoices')) {
                $invQ = Invoice::whereIn('customer_id', $grandIdsQuery)
                    ->where('status', 'issued')
                    ->where('payment_status', '!=', 'paid');
                if ($currency) $invQ->where('currency', $currency);
                
                // Reuse expression variables from earlier if possible, or redefine.
                // Redefining for safety scope.
                $hasAllocTable = Schema::hasTable('payment_allocations');
                $paidExpr = "COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = invoices.id), 0)";
                $allocExpr = $hasAllocTable ? "COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE pa.invoice_id = invoices.id AND p.invoice_id IS NULL), 0)" : "0";
                
                $grandInvRows = $invQ->select(
                    'currency',
                    DB::raw("SUM(CASE WHEN (total - $paidExpr - $allocExpr) > 0 THEN (total - $paidExpr - $allocExpr) ELSE 0 END) as remaining_sum")
                )
                ->groupBy('currency')
                ->get();

                foreach ($grandInvRows as $r) {
                    if ($r->remaining_sum > 0.001) {
                        $grandTotals['open_invoices'][$r->currency] = (float)$r->remaining_sum;
                    }
                }
            }

            // 4. Grand KPI Counts
            // Only calculate if filtering is NOT already specific to that type?
            // Actually, calculating count is always needed for "Total Results".
            
            // Debtor Count
            $grandKpis['debt_customers'] = $baseQuery->clone()->whereRaw("
                (SELECT COALESCE(SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END), 0)
                 FROM ledger_entries 
                 WHERE ledger_entries.customer_id = customers.id
                 " . ($currency ? "AND currency = '$currency'" : "") . "
                ) > 0.001
            ")->count();

            // Open Advance Count
            $grandKpis['open_advance_customers'] = $baseQuery->clone()->whereExists(function ($sq) use ($currency) {
                $sq->select(DB::raw(1))
                   ->from('payments')
                   ->whereColumn('payments.customer_id', 'customers.id')
                   ->whereNull('invoice_id');
                if ($currency) $sq->where('original_currency', $currency);
                
                $amtCol = Schema::hasColumn('payments', 'original_amount') ? 'COALESCE(original_amount, amount)' : 'amount';
                $allocSub = Schema::hasTable('payment_allocations') ? "COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE payment_id = payments.id), 0)" : "0";
                $sq->whereRaw("($amtCol - $allocSub) > 0.001");
            })->count();

            // Open Invoice Count
            $grandKpis['open_invoice_customers'] = $baseQuery->clone()->whereExists(function ($sq) use ($currency) {
                $sq->select(DB::raw(1))
                   ->from('invoices')
                   ->whereColumn('invoices.customer_id', 'customers.id')
                   ->where('status', 'issued')
                   ->where('payment_status', '!=', 'paid');
                if ($currency) $sq->where('currency', $currency);
                
                $paidExpr = "COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = invoices.id), 0)";
                $allocExpr = Schema::hasTable('payment_allocations') ? "COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE pa.invoice_id = invoices.id AND p.invoice_id IS NULL), 0)" : "0";
                $sq->whereRaw("(total - $paidExpr - $allocExpr) > 0.001");
            })->count();
        }

        return view('customer-ledgers.index', compact(
            'customers', 'currencies', 'request', 'totals', 'kpi', 'savedViews', 
            'grandTotals', 'grandKpis'
        ));
    }
}
