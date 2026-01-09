<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ContractTemplateRenderer
{
    public function render(Contract $contract, ContractTemplate $template): string
    {
        $contract->loadMissing(['salesOrder.customer', 'salesOrder.items']);

        $currencySymbols = config('quotes.currency_symbols', []);
        $currencySymbol = $currencySymbols[$contract->currency] ?? $contract->currency;
        $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');

        $customerName = $contract->customer_name;
        $companyDefaults = config('company', []);

        $replacements = [
            'contract.contract_no' => $contract->contract_no,
            'contract.issued_at' => $contract->issued_at?->format('d.m.Y') ?? '',
            'customer.name' => $customerName,
            'customer.company' => $contract->customer_company ?? '',
            'customer.tax_no' => $contract->customer_tax_no ?? '',
            'customer.address' => $contract->customer_address ?? '',
            'customer.email' => $contract->customer_email ?? '',
            'customer.phone' => $contract->customer_phone ?? '',
            'sales_order.no' => $contract->salesOrder?->order_no ?? '',
            'totals.subtotal' => $formatMoney($contract->subtotal),
            'totals.tax_total' => $formatMoney($contract->tax_total),
            'totals.grand_total' => $formatMoney($contract->grand_total),
            'currency' => $currencySymbol,
            'company.name' => Arr::get($companyDefaults, 'name', 'Epsilon CRM'),
            'company.address' => Arr::get($companyDefaults, 'address', ''),
            'company.phone' => Arr::get($companyDefaults, 'phone', ''),
            'company.email' => Arr::get($companyDefaults, 'email', ''),
            'company.tax_no' => Arr::get($companyDefaults, 'tax_no', ''),
            'line_items_table' => $this->lineItemsTable($contract, $currencySymbol, $formatMoney),
        ];

        if ($template->format === 'html') {
            $escaped = collect($replacements)
                ->mapWithKeys(function ($value, $key) {
                    if ($key === 'line_items_table') {
                        return [$key => $value];
                    }

                    return [$key => e($value)];
                })
                ->all();
        } else {
            $escaped = collect($replacements)
                ->mapWithKeys(function ($value, $key) {
                    if ($key === 'line_items_table') {
                        return [$key => $this->lineItemsText($value)];
                    }

                    return [$key => $value];
                })
                ->all();
        }

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_.]+)\s*}}/', function ($matches) use ($escaped) {
            $key = $matches[1];

            return $escaped[$key] ?? $matches[0];
        }, $template->content);
    }

    private function lineItemsTable(Contract $contract, string $currencySymbol, callable $formatMoney): string
    {
        $items = $contract->salesOrder?->items ?? collect();

        if ($items->isEmpty()) {
            return '<p>Kalem bulunamadı.</p>';
        }

        $rows = $items->map(function ($item) use ($currencySymbol, $formatMoney) {
            $description = e($item->description);
            $section = e($item->section ?: 'Genel');
            $qty = e($item->qty);
            $unit = e($item->unit);
            $price = $formatMoney($item->unit_price);

            return "<tr>
                <td>{$description}<br><span style=\"color:#6b7280; font-size: 10px;\">{$section}</span></td>
                <td>{$qty} {$unit}</td>
                <td>{$price} {$currencySymbol}</td>
            </tr>";
        })->implode('');

        return "<table>
            <thead>
                <tr>
                    <th>Açıklama</th>
                    <th>Miktar</th>
                    <th>Birim Fiyat</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>";
    }

    private function lineItemsText(string $tableHtml): string
    {
        return Str::of(strip_tags($tableHtml))
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }
}
