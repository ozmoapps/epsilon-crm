<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::query()
            ->with(['salesOrder', 'salesOrder.customer'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        // Sprint 3.15 Filters
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->boolean('open')) {
            $query->where('status', 'issued')
                  ->where('payment_status', '!=', 'paid');
        }

        if ($request->boolean('overdue')) {
             // Overdue = Open + Due Date Passed
             $query->where('status', 'issued')
                   ->where('payment_status', '!=', 'paid');
             
             if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'due_date')) {
                 $query->whereDate('due_date', '<', now());
             }
        }

        $term = $request->input('q') ?? $request->input('search') ?? $request->input('query');
        if ($term && trim($term) !== '') {
            $term = trim($term);

            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', '%' . $term . '%')
                  ->orWhere('id', $term)
                  ->orWhere('sales_order_id', $term)
                  ->orWhereHas('salesOrder', function ($sq) use ($term) {
                      $sq->where('id', $term)
                         ->orWhere('order_no', 'like', '%' . $term . '%')
                         ->orWhereHas('customer', function ($cq) use ($term) {
                             $cq->where('name', 'like', '%' . $term . '%');
                         });
                  });
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $salesOrder = SalesOrder::with([
            'items',
            'shipments' => fn($q) => $q->where('status', 'posted'),
            'shipments.lines',
            'shipments.lines.returnLines' => fn($q) => $q->whereHas('return', fn($sq) => $sq->where('status', 'posted')),
            'invoices.lines'
        ])->findOrFail($request->get('sales_order_id'));

        $limits = $this->getInvoiceableLimits($salesOrder);

        $items = $salesOrder->items->map(function ($item) use ($salesOrder, $limits) {
            $limitData = $limits[$item->id] ?? ['limit' => 0, 'shipped' => 0, 'returned' => 0];
            $limitQty = $limitData['limit'];

            $invoicedQty = $salesOrder->invoices
                ->where('status', '!=', 'cancelled')
                ->flatMap->lines
                ->where('sales_order_item_id', $item->id)
                ->sum('quantity');

            $remainingQty = max(0, round($limitQty - $invoicedQty, 2));

            return [
                'id' => $item->id,
                'product_name' => $item->product ? $item->product->name : $item->description,
                'description' => $item->description,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->vat_rate,
                'remaining_qty' => $remainingQty,
                'limit_qty' => $limitQty,
                'invoiced_qty' => $invoicedQty,
                'shipped_qty' => $limitData['shipped'],
                'returned_qty' => $limitData['returned'],
            ];
        })->filter(fn($i) => $i['remaining_qty'] > 0)->values();

        if ($items->isEmpty()) {
            return redirect()->route('sales-orders.show', $salesOrder)->with('error', 'Faturalanacak miktar bulunamadı. Önce irsaliye oluşturmalısınız veya tüm miktar faturalanmış.');
        }

        return view('invoices.create', compact('salesOrder', 'items'));
    }

    public function store(Request $request)
    {
        if (app()->environment('local', 'testing') || config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('Invoice Store Request Payload:', $request->all());
        }

        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:sales_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        $salesOrder = SalesOrder::with([
            'items',
            'shipments' => fn($q) => $q->where('status', 'posted'),
            'shipments.lines',
            'shipments.lines.returnLines' => fn($q) => $q->whereHas('return', fn($sq) => $sq->where('status', 'posted')),
            'invoices.lines'
        ])->findOrFail($request->sales_order_id);

        $limits = $this->getInvoiceableLimits($salesOrder);

        foreach ($request->items as $line) {
            $item = $salesOrder->items->find($line['id']);
            if (!$item) continue;

            $qtyToInvoice = (float) $line['quantity'];
            if ($qtyToInvoice <= 0) continue;

            $limitData = $limits[$item->id] ?? ['limit' => 0];
            $limitQty = $limitData['limit'];

            $invoicedQty = $salesOrder->invoices
                ->where('status', '!=', 'cancelled')
                ->flatMap->lines
                ->where('sales_order_item_id', $item->id)
                ->sum('quantity');

            $remaining = round($limitQty - $invoicedQty, 2);

            if ($qtyToInvoice > $remaining + 0.00001) {
                return back()->withInput()->with('error', "Hata: {$item->description} için faturalanabilir miktar aşıldı. Kalan: {$remaining}");
            }
        }

        $invoice = DB::transaction(function () use ($request, $salesOrder) {
            $invoice = Invoice::create([
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'status' => 'draft',
                'issue_date' => $request->issue_date ?? now(),
                'due_date' => $request->due_date,
                'currency' => $salesOrder->currency,
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $line) {
                $qty = $line['quantity'];
                if ($qty <= 0) continue;

                $item = $salesOrder->items->find($line['id']);
                $lineTotal = $item->unit_price * $qty;
                $lineTax = $lineTotal * ($item->vat_rate / 100);

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'sales_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $qty,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->vat_rate ?? 0,
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Oluşturuldu.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'issued') {
            return back()->with('error', 'Resmileşmiş (Kesildi) durumundaki faturalar silinemez. İptal etmeyi deneyiniz.');
        }

        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Bu faturada tahsilat kayıtları mevcut. Önce tahsilatları silmeniz gerekmektedir.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Silindi.');
    }

    public function show(Invoice $invoice)
{
    $invoice->load(['lines.salesOrderItem.product', 'payments', 'salesOrder']);

    $bankAccounts = \App\Models\BankAccount::with('currency')
        ->where('is_active', true)
        ->orderBy('type')
        ->orderBy('name')
        ->get();

    return view('invoices.show', compact('invoice', 'bankAccounts'));
}


    public function issue(Invoice $invoice, \App\Services\LedgerService $ledgerService)
    {
        if (app()->environment('local', 'testing') || config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('UI ISSUE HIT', ['invoice_id' => $invoice->id, 'user_id' => auth()->id()]);
        }

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Sadece taslak faturalar resmileştirilebilir.');
        }

        try {
            DB::beginTransaction();

            $hasPostedShipments = $invoice->salesOrder->shipments()->where('status', 'posted')->exists();

            if (!$hasPostedShipments) {
                $existing = \App\Models\SalesOrderShipment::where('invoice_id', $invoice->id)->exists();

                if (!$existing) {
                    $warehouseId = \App\Models\Warehouse::where('is_active', true)
                        ->where('is_default', true)
                        ->value('id')
                        ?? \App\Models\Warehouse::where('is_active', true)->value('id');

                    if (!$warehouseId) {
                        throw new \Exception('Aktif depo bulunamadı. Lütfen depo tanımlayın.');
                    }

                    $shipment = \App\Models\SalesOrderShipment::create([
                        'sales_order_id' => $invoice->sales_order_id,
                        'warehouse_id' => $warehouseId,
                        'invoice_id' => $invoice->id,
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                        'note' => 'Otomatik Sevkiyat (Fatura: ' . ($invoice->invoice_no ?? 'Taslak') . ')',
                    ]);

                    foreach ($invoice->lines as $line) {
                        \App\Models\SalesOrderShipmentLine::create([
                            'sales_order_shipment_id' => $shipment->id,
                            'sales_order_item_id' => $line->sales_order_item_id,
                            'product_id' => $line->product_id,
                            'qty' => $line->quantity,
                        ]);
                    }

                    $stockService = new \App\Services\StockService();
                    if (!$stockService->postSalesOrderShipment($shipment)) {
                        throw new \Exception('Stok düşümü yapılamadı.');
                    }
                }
            }

            $maxRetries = 3;
            $invoiceNo = null;
            $issued = false;

            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $year = now()->year;
                    $prefix = 'INV-' . $year . '-';

                    $lastInvoiceNo = Invoice::where('invoice_no', 'like', $prefix . '%')
                        ->orderByDesc('invoice_no')
                        ->value('invoice_no');

                    $nextNum = 1;
                    if ($lastInvoiceNo) {
                        $parts = explode('-', $lastInvoiceNo);
                        $numericPart = (int) end($parts);
                        $nextNum = $numericPart + 1;
                    }

                    $nextNum += $i;

                    $candidateNo = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                    if (Invoice::where('invoice_no', $candidateNo)->exists()) {
                        continue;
                    }

                    $updated = Invoice::where('id', $invoice->id)
                        ->where('status', 'draft')
                        ->update([
                            'status' => 'issued',
                            'invoice_no' => $candidateNo,
                            'issue_date' => $invoice->issue_date ?: now(),
                        ]);

                    if ($updated === 0) {
                        $fresh = Invoice::find($invoice->id);
                        if ($fresh->status !== 'draft') {
                            throw new \Exception("Fatura başka bir işlem tarafından güncellendi. (Status: {$fresh->status})");
                        }
                        throw new \Exception("Fatura durumu güncellenemedi.");
                    }

                    $invoiceNo = $candidateNo;
                    $issued = true;
                    $invoice->refresh();
                    break;

                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() === '23000') {
                        continue;
                    }
                    throw $e;
                }
            }

            if (!$issued) {
                throw new \Exception("Fatura numarası üretilemedi. (Maksimum deneme aşımı)");
            }

            $ledgerService->createDebitFromInvoice($invoice);

            DB::commit();

            if (!$hasPostedShipments && isset($shipment)) {
                $shipment->update(['note' => 'Otomatik Sevkiyat (Fatura: ' . $invoiceNo . ')']);
            }

            return back()->with('success', 'Kaydedildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Invoice Issue Error: ' . $e->getMessage());
            return back()->with('error', 'İşlem başarısız. ' . $e->getMessage());
        }
    }

    private function getInvoiceableLimits(SalesOrder $salesOrder): array
    {
        $limits = [];
        $hasPostedShipments = $salesOrder->shipments()->where('status', 'posted')->exists();

        foreach ($salesOrder->items as $item) {
            if ($hasPostedShipments) {
                $shippedQty = 0;
                $returnedQty = 0;

                foreach ($salesOrder->shipments as $shipment) {
                    if ($shipment->status !== 'posted') continue;

                    foreach ($shipment->lines as $shipLine) {
                        if ($shipLine->sales_order_item_id == $item->id) {
                            $shippedQty += $shipLine->qty;

                            foreach ($shipLine->returnLines as $retLine) {
                                if ($retLine->return && $retLine->return->status === 'posted') {
                                    $returnedQty += $retLine->qty;
                                }
                            }
                        }
                    }
                }

                $limits[$item->id] = [
                    'limit' => max(0, $shippedQty - $returnedQty),
                    'shipped' => $shippedQty,
                    'returned' => $returnedQty,
                ];
            } else {
                $limits[$item->id] = [
                    'limit' => $item->qty ?? $item->quantity,
                    'shipped' => 0,
                    'returned' => 0,
                ];
            }
        }

        return $limits;
    }
}
