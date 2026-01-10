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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        $vesselId = $request->input('vessel_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $contracts = Contract::query()
            ->with(['salesOrder.customer', 'salesOrder.vessel'])
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
        $vessels = \App\Models\Vessel::with('customer')->orderBy('name')->get(['id', 'name', 'customer_id']);

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
            'previewHtml' => $this->buildPreview($contract, ContractTemplate::defaultForLocale($contract->locale)),
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
            $this->applyTemplate($contract, false, true);

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

        if (! $contract->rendered_body) {
            $this->applyTemplate($contract, true);
        } elseif (! $contract->rendered_at) {
            $contract->update(['rendered_at' => now()]);
        }

        return $this->transitionStatus($contract, 'draft', 'sent', null, 'Sözleşme gönderildi olarak işaretlendi.');
    }

    public function markSigned(Contract $contract)
    {
        $this->authorize('update', $contract);

        return $this->transitionStatus($contract, 'sent', 'signed', now(), 'Sözleşme imzalandı olarak işaretlendi.');
    }

    public function cancel(Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->status === 'cancelled') {
            return back()->with('warning', 'Sözleşme zaten iptal edildi.');
        }

        if (! $contract->transitionTo('cancelled', ['source' => 'cancel'])) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        return back()->with('success', 'Sözleşme iptal edildi.');
    }

    public function pdf(Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract->load(['salesOrder.customer', 'salesOrder.items']);

        if (! $contract->rendered_body) {
            $this->applyTemplate($contract, true);
        } elseif (! $contract->rendered_at) {
            $contract->update(['rendered_at' => now()]);
        }

        return response()
            ->view('contracts.pdf', ['contract' => $contract])
            ->header('Content-Type', 'application/pdf');
    }

    public function printView(Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract->load(['salesOrder.customer', 'salesOrder.vessel', 'salesOrder.items']);

        if (! $contract->rendered_body) {
            $this->applyTemplate($contract, true);
        } elseif (! $contract->rendered_at) {
            $contract->update(['rendered_at' => now()]);
        }

        $companyProfile = \App\Models\CompanyProfile::current();
        $bankAccounts = \App\Models\BankAccount::query()->with('currency')->orderBy('bank_name')->get();

        return view('contracts.print', compact('contract', 'companyProfile', 'bankAccounts'));
    }

    public function revise(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        if (! $contract->canCreateRevision()) {
            return back()->with('warning', 'Bu sözleşme için revizyon oluşturulamaz.');
        }

        $contract->loadMissing('salesOrder');

        $rootContract = $contract->root_contract_id
            ? Contract::query()->findOrFail($contract->root_contract_id)
            : $contract;

        $baseContractNo = $rootContract->contract_no;
        $latestRevision = Contract::query()
            ->where('root_contract_id', $rootContract->id)
            ->max('revision_no');
        $latestRevision = max($latestRevision ?? 1, $rootContract->revision_no ?? 1);
        $nextRevision = $latestRevision + 1;
        $newContractNo = sprintf('%s-R%d', $baseContractNo, $nextRevision);

        $newContract = DB::transaction(function () use ($contract, $request, $rootContract, $nextRevision, $newContractNo) {
            $current = Contract::query()
                ->where(function ($query) use ($rootContract) {
                    $query->where('id', $rootContract->id)
                        ->orWhere('root_contract_id', $rootContract->id);
                })
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            $newContract = Contract::create([
                'sales_order_id' => $contract->sales_order_id,
                'root_contract_id' => $rootContract->id,
                'revision_no' => $nextRevision,
                'contract_no' => $newContractNo,
                'status' => 'draft',
                'issued_at' => now()->toDateString(),
                'locale' => $contract->locale,
                'currency' => $contract->currency,
                'customer_name' => $contract->customer_name,
                'customer_company' => $contract->customer_company,
                'customer_tax_no' => $contract->customer_tax_no,
                'customer_address' => $contract->customer_address,
                'customer_email' => $contract->customer_email,
                'customer_phone' => $contract->customer_phone,
                'subtotal' => $contract->subtotal,
                'tax_total' => $contract->tax_total,
                'grand_total' => $contract->grand_total,
                'payment_terms' => $contract->payment_terms,
                'warranty_terms' => $contract->warranty_terms,
                'scope_text' => $contract->scope_text,
                'exclusions_text' => $contract->exclusions_text,
                'delivery_terms' => $contract->delivery_terms,
                'contract_template_id' => $contract->contract_template_id,
                'contract_template_version_id' => null,
                'rendered_body' => null,
                'rendered_at' => null,
                'created_by' => $request->user()->id,
                'is_current' => true,
            ]);

            if ($current) {
                $current->forceFill([
                    'is_current' => false,
                    'superseded_by_id' => $newContract->id,
                    'superseded_at' => now(),
                ])->save();

                $current->transitionTo('superseded', [
                    'superseded_by_id' => $newContract->id,
                    'superseded_contract_no' => $newContract->contract_no,
                ]);
            }

            return $newContract;
        });

        return redirect()->route('contracts.edit', $newContract)
            ->with('success', 'Revizyon oluşturuldu.');
    }

    private function transitionStatus(Contract $contract, string $from, string $to, $signedAt, string $message)
    {
        if ($contract->status !== $from) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        if (! $contract->transitionTo($to, ['source' => 'status_action'])) {
            return back()->with('warning', 'Bu işlem için uygun durumda değil.');
        }

        $contract->forceFill([
            'signed_at' => $signedAt,
        ])->save();

        return back()->with('success', $message);
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

    private function buildPreview(Contract $contract, ?ContractTemplate $defaultTemplate): ?string
    {
        $template = $contract->contractTemplate ?: $defaultTemplate;

        if (! $template) {
            return null;
        }

        $renderer = app(ContractTemplateRenderer::class);

        $version = $this->resolveTemplateVersion($contract, $template);

        return $renderer->render($contract, $version ?? $template);
    }

    private function applyTemplate(Contract $contract, bool $setRenderedAt = false, bool $forceCurrentVersion = false): void
    {
        $template = $contract->contractTemplate
            ?: ContractTemplate::defaultForLocale($contract->locale);

        $renderer = app(ContractTemplateRenderer::class);
        $version = $this->resolveTemplateVersion($contract, $template, $forceCurrentVersion);

        if (! $version) {
            return;
        }

        $contract->forceFill([
            'rendered_body' => $renderer->render($contract, $version),
            'rendered_at' => $setRenderedAt ? now() : $contract->rendered_at,
            'contract_template_version_id' => $version->id,
        ])->save();
    }

    private function resolveTemplateVersion(
        Contract $contract,
        ?ContractTemplate $template,
        bool $forceCurrentVersion = false
    ): ?ContractTemplateVersion
    {
        if (! $forceCurrentVersion && $contract->contract_template_version_id) {
            return ContractTemplateVersion::query()->find($contract->contract_template_version_id);
        }

        if (! $template) {
            return null;
        }

        $template->loadMissing('currentVersion');

        return $template->currentVersion ?: $template->latestVersion();
    }
}
