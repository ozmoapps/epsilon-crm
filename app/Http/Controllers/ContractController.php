<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use App\Models\SalesOrder;
use App\Models\Quote;
use App\Models\WorkOrder;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use App\Services\ContractTemplateRenderer;
use App\Services\ContractPdfService;
use App\Services\ContractWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function __construct(
        protected ContractPdfService $pdfService,
        protected ContractWorkflowService $workflowService
    ) {}
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        $vesselId = $request->input('vessel_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $contracts = Contract::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('contract_no', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($customerId, function ($q) use ($customerId) {
                $q->whereHas('salesOrder', function ($sub) use ($customerId) {
                    $sub->where('customer_id', $customerId);
                });
            })
            ->when($vesselId, function ($q) use ($vesselId) {
                $q->whereHas('salesOrder', function ($sub) use ($vesselId) {
                    $sub->where('vessel_id', $vesselId);
                });
            })
            ->when($dateFrom, fn ($query) => $query->whereDate('issued_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('issued_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statuses = Contract::statusOptions();
        $customers = \App\Models\Customer::orderBy('name')->get(['id', 'name']);
        $vessels = \App\Models\Vessel::orderBy('name')->get(['id', 'name', 'customer_id']);

        $savedViews = \App\Models\SavedView::allow('contracts')->visibleTo($request->user())->get();

        return view('contracts.index', compact(
            'contracts', 'search', 'status', 'statuses', 
            'customers', 'vessels', 'savedViews',
            'customerId', 'vesselId', 'dateFrom', 'dateTo'
        ));
    }

    public function create(SalesOrder $salesOrder)
    {
        if ($salesOrder->contract) {
            return redirect()->route('contracts.show', $salesOrder->contract)
                ->with('warning', 'Bu satış siparişi için sözleşme zaten oluşturuldu.');
        }

        $salesOrder->load(['customer', 'items', 'vessel']);

        $contract = new Contract($this->prefillFromSalesOrder($salesOrder));

        return view('contracts.create', [
            'contract' => $contract,
            'salesOrder' => $salesOrder,
            'locales' => config('contracts.locales', []),
            'templates' => $this->availableTemplates($contract->locale),
        ]);
    }

    public function store(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->contract) {
            return redirect()->route('contracts.show', $salesOrder->contract)
                ->with('warning', 'Bu satış siparişi için sözleşme zaten oluşturuldu.');
        }

        if (! $salesOrder->canTransitionTo('contracted')) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', 'Bu satış siparişi sözleşmeye dönüştürülemez.');
        }

        $validated = $request->validate($this->rules(), $this->messages());

        $salesOrder->load('customer');

        $data = array_merge($this->prefillFromSalesOrder($salesOrder), $validated, [
            'sales_order_id' => $salesOrder->id,
            'created_by' => $request->user()->id,
        ]);

        $contract = Contract::create($data);

        $salesOrder->transitionTo('contracted', [
            'contract_id' => $contract->id,
            'contract_no' => $contract->contract_no,
        ]);

        app(ActivityLogger::class)->log($salesOrder, 'converted_to_contract', [
            'contract_id' => $contract->id,
            'contract_no' => $contract->contract_no,
        ]);

        app(ActivityLogger::class)->log($contract, 'created_from_sales_order', [
            'sales_order_id' => $salesOrder->id,
            'sales_order_no' => $salesOrder->order_no,
        ]);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Sözleşme oluşturuldu.');
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract->load([
            'salesOrder.customer',
            'salesOrder.items',
            'salesOrder.workOrder',
            'salesOrder.quote',
            'creator',
            'rootContract',
            'attachments' => fn ($query) => $query->latest(),
            'attachments.uploader',
            'deliveries' => fn ($query) => $query->latest(),
            'deliveries.creator',
            'openFollowUps.creator',
        ]);
        
        $salesOrder = $contract->salesOrder;
        $quote = $salesOrder?->quote;
        $workOrder = $salesOrder?->workOrder;

        $subjects = [[Contract::class, $contract->id]];
        if ($salesOrder) $subjects[] = [SalesOrder::class, $salesOrder->id];
        if ($quote) $subjects[] = [Quote::class, $quote->id];
        if ($workOrder) $subjects[] = [WorkOrder::class, $workOrder->id];

        $timeline = ActivityLog::query()
            ->with(['actor', 'subject'])
            ->where(function ($q) use ($subjects) {
                foreach ($subjects as [$type, $id]) {
                    $q->orWhere(function ($sub) use ($type, $id) {
                        $sub->where('subject_type', $type)->where('subject_id', $id);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $rootId = $contract->root_contract_id ?? $contract->id;
        $revisions = Contract::query()
            ->where('id', $rootId)
            ->orWhere('root_contract_id', $rootId)
            ->orderByDesc('revision_no')
            ->get();

        return view('contracts.show', compact('contract', 'revisions', 'salesOrder', 'quote', 'workOrder', 'timeline'));
    }

    public function edit(Contract $contract)
    {
        $this->authorize('update', $contract);

        if (! $contract->isEditable()) {
            return redirect()->route('contracts.show', $contract)
                ->with('warning', 'Sadece taslak sözleşmeler düzenlenebilir.');
        }

        $contract->load(['salesOrder.customer', 'salesOrder.items', 'salesOrder.vessel']);
        return view('contracts.edit', [
            'contract' => $contract,
            'salesOrder' => $contract->salesOrder,
            'locales' => config('contracts.locales', []),
            'templates' => $this->availableTemplates($contract->locale),
            'previewHtml' => $this->workflowService->renderPreview($contract, ContractTemplate::defaultForLocale($contract->locale)),
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        if (! $contract->isEditable()) {
            return redirect()->route('contracts.show', $contract)
                ->with('warning', 'Sadece taslak sözleşmeler düzenlenebilir.');
        }

        $validated = $request->validate($this->rules(), $this->messages());

        $contract->update($validated);

        if ($request->boolean('apply_template')) {
            $this->workflowService->applyTemplate($contract, false, true);

            return redirect()->route('contracts.edit', $contract)
                ->with('success', 'Şablon uygulandı ve önizleme güncellendi.');
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Sözleşme güncellendi.');
    }

    public function destroy(Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->isLocked()) {
            app(ActivityLogger::class)->log($contract, 'delete_blocked', [
                'reason' => 'locked',
            ]);
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'İmzalı sözleşmeler silinemez.');
        }

        $salesOrder = $contract->salesOrder()->first();

        DB::transaction(function () use ($contract, $salesOrder) {
            $contract->delete();

            if ($salesOrder && $salesOrder->status === 'contracted' && ! $salesOrder->contract()->exists()) {
                $logs = ActivityLog::query()
                    ->where('subject_type', $salesOrder->getMorphClass())
                    ->where('subject_id', $salesOrder->getKey())
                    ->where('action', 'status_changed')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                $lastContracted = $logs->first(fn ($l) => data_get($l->meta, 'to') === 'contracted');
                $restore = data_get($lastContracted?->meta, 'from') ?: 'confirmed';

                $salesOrder->forceFill(['status' => $restore])->save();

                app(ActivityLogger::class)->log($salesOrder, 'contract_deleted', [
                    'contract_id' => $contract->id,
                    'contract_no' => $contract->contract_no,
                    'restored_status' => $restore,
                ]);
            }
        });

        if ($salesOrder) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('success', 'Sözleşme silindi. Sipariş durumu geri alındı.');
        }

        return redirect()->route('contracts.index')
            ->with('success', 'Sözleşme silindi.');
    }

    public function markSent(Contract $contract)
    {
        $this->authorize('update', $contract);

        if (! $this->workflowService->markAsSent($contract)) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        return back()->with('success', 'Sözleşme gönderildi olarak işaretlendi.');
    }

    public function markSigned(Contract $contract)
    {
        $this->authorize('update', $contract);

        if (! $this->workflowService->markAsSigned($contract, now())) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        return back()->with('success', 'Sözleşme imzalandı olarak işaretlendi.');
    }

    public function cancel(Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->status === 'cancelled') {
            return back()->with('warning', 'Sözleşme zaten iptal edildi.');
        }

        if (! $this->workflowService->cancel($contract)) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        return back()->with('success', 'Sözleşme iptal edildi.');
    }

    public function pdf(Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract = $this->pdfService->prepareForPdf($contract);

        return response()
            ->view('contracts.pdf', ['contract' => $contract])
            ->header('Content-Type', 'application/pdf');
    }

    public function printView(Contract $contract)
    {
        $this->authorize('view', $contract);

        $data = $this->pdfService->prepareForPrint($contract);

        return view('contracts.print', $data);
    }

    public function revise(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        $newContract = $this->workflowService->createRevision($contract, $request->user()->id);

        if (! $newContract) {
            return back()->with('warning', 'Bu sözleşme için revizyon oluşturulamaz.');
        }

        return redirect()->route('contracts.edit', $newContract)
            ->with('success', 'Revizyon oluşturuldu.');
    }

    private function rules(): array
    {
        $locales = array_keys(config('contracts.locales', []));

        return [
            'issued_at' => ['required', 'date'],
            'locale' => ['required', 'string', Rule::in($locales)],
            'contract_template_id' => [
                'nullable',
                Rule::exists('contract_templates', 'id')->where('is_active', true),
            ],
            'payment_terms' => ['nullable', 'string'],
            'warranty_terms' => ['nullable', 'string'],
            'scope_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
            'delivery_terms' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'issued_at.required' => 'Düzenleme tarihi zorunludur.',
            'issued_at.date' => 'Düzenleme tarihi geçerli değil.',
            'locale.required' => 'Dil seçimi zorunludur.',
            'locale.in' => 'Dil seçimi geçersiz.',
            'contract_template_id.exists' => 'Seçilen şablon bulunamadı.',
        ];
    }

    private function prefillFromSalesOrder(SalesOrder $salesOrder): array
    {
        $defaults = config('contracts.defaults', []);
        $customer = $salesOrder->customer;

        return [
            'sales_order_id' => $salesOrder->id,
            'issued_at' => now()->toDateString(),
            'locale' => 'tr',
            'currency' => $salesOrder->currency,
            'customer_name' => $customer?->name ?? $salesOrder->title,
            'customer_company' => null,
            'customer_tax_no' => null,
            'customer_address' => $customer?->address,
            'customer_email' => $customer?->email,
            'customer_phone' => $customer?->phone,
            'subtotal' => $salesOrder->subtotal,
            'tax_total' => $salesOrder->vat_total,
            'grand_total' => $salesOrder->grand_total,
            'payment_terms' => $salesOrder->payment_terms ?: ($defaults['payment_terms'] ?? null),
            'warranty_terms' => $salesOrder->warranty_text ?: ($defaults['warranty_terms'] ?? null),
            'scope_text' => $defaults['scope_text'] ?? null,
            'exclusions_text' => $salesOrder->exclusions ?: ($defaults['exclusions_text'] ?? null),
            'delivery_terms' => $defaults['delivery_terms'] ?? null,
        ];
    }

    private function availableTemplates(string $locale)
    {
        return ContractTemplate::query()
            ->active()
            ->where('locale', $locale)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }
}
