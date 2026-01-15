<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;

class ContractWorkflowService
{
    public function __construct(
        protected ContractTemplateRenderer $renderer,
        protected ActivityLogger $activityLogger
    ) {}

    public function markAsSent(Contract $contract): bool
    {
        if (! $contract->rendered_body) {
            $this->applyTemplate($contract, true);
        } elseif (! $contract->rendered_at) {
            $contract->update(['rendered_at' => now()]);
        }

        return $contract->transitionTo('sent', ['source' => 'status_action']);
    }

    public function markAsSigned(Contract $contract, ?string $signedAt = null): bool
    {
        $success = $contract->transitionTo('signed', ['source' => 'status_action']);

        if ($success) {
            $contract->forceFill([
                'signed_at' => $signedAt ?? now(),
            ])->save();
        }

        return $success;
    }

    public function cancel(Contract $contract): bool
    {
        if ($contract->status === 'cancelled') {
            return false;
        }

        return $contract->transitionTo('cancelled', ['source' => 'cancel']);
    }

    public function createRevision(Contract $contract, int $userId): ?Contract
    {
        if (! $contract->canCreateRevision()) {
            return null;
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

        return DB::transaction(function () use ($contract, $rootContract, $nextRevision, $newContractNo, $userId) {
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
                'created_by' => $userId,
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
    }

    public function applyTemplate(Contract $contract, bool $setRenderedAt = false, bool $forceCurrentVersion = false): void
    {
        $template = $contract->contractTemplate
            ?: ContractTemplate::defaultForLocale($contract->locale);

        $version = $this->resolveTemplateVersion($contract, $template, $forceCurrentVersion);

        if (! $version) {
            return;
        }

        $contract->forceFill([
            'rendered_body' => $this->renderer->render($contract, $version),
            'rendered_at' => $setRenderedAt ? now() : $contract->rendered_at,
            'contract_template_version_id' => $version->id,
        ])->save();
    }

    public function renderPreview(Contract $contract, ?ContractTemplate $defaultTemplate = null): ?string
    {
        $template = $contract->contractTemplate ?: $defaultTemplate;

        if (! $template) {
            return null;
        }

        $version = $this->resolveTemplateVersion($contract, $template);

        return $this->renderer->render($contract, $version ?? $template);
    }

    protected function resolveTemplateVersion(
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
