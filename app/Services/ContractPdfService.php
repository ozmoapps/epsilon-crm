<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\CompanyProfile;
use App\Models\BankAccount;

class ContractPdfService
{
    public function __construct(
        protected ContractWorkflowService $workflowService
    ) {}

    public function prepareForPdf(Contract $contract): Contract
    {
        $contract->loadMissing(['salesOrder.customer', 'salesOrder.items']);

        $this->ensureRendered($contract);

        return $contract;
    }

    public function prepareForPrint(Contract $contract): array
    {
        $contract->loadMissing(['salesOrder.customer', 'salesOrder.vessel', 'salesOrder.items']);

        $this->ensureRendered($contract);

        $companyProfile = CompanyProfile::current();
        $bankAccounts = BankAccount::query()->with('currency')->orderBy('bank_name')->get();

        return compact('contract', 'companyProfile', 'bankAccounts');
    }

    protected function ensureRendered(Contract $contract): void
    {
        if (! $contract->rendered_body) {
            $this->workflowService->applyTemplate($contract, true);
        } elseif (! $contract->rendered_at) {
            $contract->update(['rendered_at' => now()]);
        }
    }
}
