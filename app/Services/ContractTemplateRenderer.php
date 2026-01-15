<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class ContractTemplateRenderer
{
    public function render(Contract $contract, ContractTemplate|ContractTemplateVersion $template): string
    {
        $contract->loadMissing(['salesOrder.customer', 'salesOrder.items', 'salesOrder.vessel']);

        [$content, $format] = $this->resolveTemplate($template);

        // Prepare Data Context
        $data = $this->prepareContext($contract);

        try {
            // Render using Blade
            return Blade::render($content, $data);
        } catch (\Exception $e) {
            // Fallback or Error handling
            // For now, return the error message in the content to make it visible during preview
            return "Template Rendering Error: " . $e->getMessage();
        }
    }

    private function resolveTemplate(ContractTemplate|ContractTemplateVersion $template): array
    {
        if ($template instanceof ContractTemplateVersion) {
            return [$template->content, $template->format];
        }

        if ($template->current_version_id) {
            $version = $template->relationLoaded('currentVersion')
                ? $template->currentVersion
                : $template->currentVersion()->first();

            if ($version) {
                return [$version->content, $version->format];
            }
        }

        return [$template->content, $template->format];
    }

    private function prepareContext(Contract $contract): array
    {
        $currencySymbols = config('quotes.currency_symbols', []);
        $currencySymbol = $currencySymbols[$contract->currency] ?? $contract->currency;
        $companyProfile = CompanyProfile::current();
        $companyDefaults = $companyProfile?->toArray() ?? config('company', []);

        return [
            'contract' => $contract,
            'salesOrder' => $contract->salesOrder,
            'customer' => $contract->salesOrder->customer,
            'vessel' => $contract->salesOrder->vessel,
            'items' => $contract->salesOrder->items,
            'company' => (object) [
                'name' => Arr::get($companyDefaults, 'name', 'Epsilon CRM'),
                'address' => Arr::get($companyDefaults, 'address', ''),
                'phone' => Arr::get($companyDefaults, 'phone', ''),
                'email' => Arr::get($companyDefaults, 'email', ''),
                'tax_no' => Arr::get($companyDefaults, 'tax_no', ''),
                'footer_text' => Arr::get($companyDefaults, 'footer_text', ''),
                'logo_url' => $companyProfile?->logo_path ? \Illuminate\Support\Facades\Storage::url($companyProfile->logo_path) : null,
            ],
            'utils' => new class($currencySymbol) {
                public function __construct(public string $currencySymbol) {}
                
                public function formatMoney($value) {
                    return number_format((float) $value, 2, ',', '.');
                }

                public function formatCurrency($value) {
                    return $this->formatMoney($value) . ' ' . $this->currencySymbol;
                }
                
                public function formatDate($date) {
                    return \Carbon\Carbon::parse($date)->format('d.m.Y');
                }
            },
        ];
    }
}
