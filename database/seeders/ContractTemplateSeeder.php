<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        ContractTemplate::query()->where('locale', 'tr')->update(['is_default' => false]);
        ContractTemplate::query()->where('locale', 'en')->update(['is_default' => false]);

        $trTemplate = <<<'HTML'
<div class="section">
    <h1>Sözleşme</h1>
    <p class="muted">No: {{ $contract->contract_no }} · Tarih: {{ $utils->formatDate($contract->issued_at) }}</p>
</div>

<div class="section">
    <h2>Taraflar</h2>
    <table>
        <tr>
            <th>Alıcı</th>
            <th>Satıcı</th>
        </tr>
        <tr>
            <td>
                <strong>{{ $customer->name }}</strong><br>
                {{ $company->name }}<br>
                {{ $customer->address }}<br>
                {{ $customer->phone }}<br>
                {{ $customer->email }}
            </td>
            <td>
                <strong>{{ $company->name }}</strong><br>
                {{ $company->address }}<br>
                {{ $company->phone }}<br>
                {{ $company->email }}<br>
                {{ $company->tax_no }}
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>Kapsam</h2>
    <p>Bu sözleşme kapsamında satış siparişi no: {{ $salesOrder->order_no }} üzerinden belirlenen işler yapılacaktır.</p>
</div>

<div class="section">
    <h2>Kalemler</h2>
    <table>
        <thead>
            <tr>
                <th>Açıklama</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    {{ $item->description }}<br>
                    <span style="color:#6b7280; font-size: 10px;">{{ $item->section ?: 'Genel' }}</span>
                </td>
                <td>{{ $item->qty }} {{ $item->unit }}</td>
                <td>{{ $utils->formatCurrency($item->unit_price) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Toplamlar</h2>
    <table>
        <tr>
            <th>Ara Toplam</th>
            <th>Vergi Toplamı</th>
            <th>Genel Toplam</th>
        </tr>
        <tr class="totals">
            <td>{{ $utils->formatCurrency($contract->subtotal) }}</td>
            <td>{{ $utils->formatCurrency($contract->tax_total) }}</td>
            <td>{{ $utils->formatCurrency($contract->grand_total) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>Ödeme Şartları</h2>
    <p>Ödeme, sözleşme tarihinde belirtilen toplam bedel üzerinden yapılacaktır.</p>
</div>

<div class="section">
    <h2>Garanti ve Hariç Tutulanlar</h2>
    <p><strong>Garanti:</strong> İşçilik ve malzeme için sözleşme tarihinden itibaren 12 ay garanti verilir.</p>
    <p><strong>Hariç:</strong> Sözleşme kapsamı dışında kalan işler ayrıca fiyatlandırılır.</p>
</div>

<div class="section">
    <h2>Teslim Şartları</h2>
    <p>Teslim ve tamamlanma süreleri satış siparişinde belirtilen plan doğrultusunda yürütülür.</p>
</div>

<div class="signature">
    <div class="signature-box">
        <p>Alıcı</p>
        <p>İmza:</p>
    </div>
    <div class="signature-box" style="float: right;">
        <p>Satıcı</p>
        <p>İmza:</p>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="footer">
    <p>{{ $company->footer_text }}</p>
</div>
HTML;

        $enTemplate = <<<'HTML'
<div class="section">
    <h1>Contract</h1>
    <p class="muted">No: {{ $contract->contract_no }} · Date: {{ $utils->formatDate($contract->issued_at) }}</p>
</div>

<div class="section">
    <h2>Parties</h2>
    <table>
        <tr>
            <th>Buyer</th>
            <th>Seller</th>
        </tr>
        <tr>
            <td>
                <strong>{{ $customer->name }}</strong><br>
                {{ $company->name }}<br>
                {{ $customer->address }}<br>
                {{ $customer->phone }}<br>
                {{ $customer->email }}
            </td>
            <td>
                <strong>{{ $company->name }}</strong><br>
                {{ $company->address }}<br>
                {{ $company->phone }}<br>
                {{ $company->email }}<br>
                {{ $company->tax_no }}
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>Scope</h2>
    <p>Services will be delivered according to sales order no: {{ $salesOrder->order_no }}.</p>
</div>

<div class="section">
    <h2>Line Items</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    {{ $item->description }}<br>
                    <span style="color:#6b7280; font-size: 10px;">{{ $item->section ?: 'General' }}</span>
                </td>
                <td>{{ $item->qty }} {{ $item->unit }}</td>
                <td>{{ $utils->formatCurrency($item->unit_price) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Totals</h2>
    <table>
        <tr>
            <th>Subtotal</th>
            <th>Tax Total</th>
            <th>Grand Total</th>
        </tr>
        <tr class="totals">
            <td>{{ $utils->formatCurrency($contract->subtotal) }}</td>
            <td>{{ $utils->formatCurrency($contract->tax_total) }}</td>
            <td>{{ $utils->formatCurrency($contract->grand_total) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>Payment Terms</h2>
    <p>Payment will be made based on the total amount stated in the contract.</p>
</div>

<div class="section">
    <h2>Warranty & Exclusions</h2>
    <p><strong>Warranty:</strong> 12-month warranty for workmanship and materials.</p>
    <p><strong>Exclusions:</strong> Out-of-scope works will be priced separately.</p>
</div>

<div class="section">
    <h2>Delivery Terms</h2>
    <p>Delivery timeline follows the plan defined in the sales order.</p>
</div>

<div class="signature">
    <div class="signature-box">
        <p>Buyer</p>
        <p>Signature:</p>
    </div>
    <div class="signature-box" style="float: right;">
        <p>Seller</p>
        <p>Signature:</p>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="footer">
    <p>{{ $company->footer_text }}</p>
</div>
HTML;

        $trDefault = ContractTemplate::updateOrCreate(
            ['locale' => 'tr', 'name' => 'Varsayılan TR Şablonu'],
            [
                'content' => $trTemplate,
                'format' => 'html',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $enDefault = ContractTemplate::updateOrCreate(
            ['locale' => 'en', 'name' => 'Default EN Template'],
            [
                'content' => $enTemplate,
                'format' => 'html',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        foreach ([$trDefault, $enDefault] as $template) {
            $template->loadMissing('currentVersion');

            if (! $template->current_version_id) {
                $template->createVersion($template->content, $template->format);
                continue;
            }

            if ($template->currentVersion && $template->currentVersion->content !== $template->content) {
                $template->createVersion($template->content, $template->format, null, 'Varsayılan şablon güncellendi.');
            }
        }
    }
}
