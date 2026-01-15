<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Vessel;
use App\Models\VesselContact;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\WorkOrder;
use App\Models\Contract;
use App\Models\ContractDelivery;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrmDemoSeeder extends Seeder
{
    /**
     * Demo için kullanılacak user ID
     */
    private ?int $userId = null;

    /**
     * Oluşturulan customer'lar (key => model)
     */
    private array $customers = [];

    /**
     * Oluşturulan vessel'lar (key => model)
     */
    private array $vessels = [];

    /**
     * Oluşturulan quote'lar
     */
    private array $quotes = [];

    /**
     * Oluşturulan sales order'lar
     */
    private array $salesOrders = [];

    /**
     * Oluşturulan work order'lar
     */
    private array $workOrders = [];

    /**
     * Tablo kolonları cache
     */
    private array $columnsCache = [];

    /**
     * Demo seeder - ASLA random veri üretmez, sadece DemoData YAML kullanır
     */
    public function run(): void
    {
        // Mass assignment guard'ı kaldır
        Model::unguard();

        try {
            // User ID'yi bul
            $this->userId = User::first()?->id;

            if (!$this->userId) {
                $this->command?->warn('⚠️  User bulunamadı. created_by alanları NULL olacak.');
            }

            $this->command?->info('🌱 Demo verileri oluşturuluyor...');
            $this->command?->newLine();

            // Sırayla seed et
            $this->seedCustomers();
            $this->seedVessels();
            $this->seedVesselContacts();
            $this->seedWorkOrders();
            $this->seedQuotes();
            $this->seedSalesOrders();
            $this->seedContracts();

            $this->command?->newLine();
            $this->command?->info('✅ Demo verileri başarıyla oluşturuldu!');
        } finally {
            // Guard'ı tekrar aktif et
            Model::reguard();
        }
    }

    /**
     * Tablo kolonlarını al (cache'li)
     */
    private function cols(string $table): array
    {
        if (!isset($this->columnsCache[$table])) {
            $this->columnsCache[$table] = Schema::getColumnListing($table);
        }

        return $this->columnsCache[$table];
    }

    /**
     * Verilen data'yı tablo şemasına göre filtrele
     */
    private function filterForTable(string $table, array $data): array
    {
        $cols = $this->cols($table);

        // Sadece tabloda var olan kolonları tut
        $filtered = array_intersect_key($data, array_flip($cols));

        // Timestamps ekle (eğer tablo destekliyorsa ve data'da yoksa)
        if (in_array('created_at', $cols) && !isset($filtered['created_at'])) {
            $filtered['created_at'] = now();
        }

        if (in_array('updated_at', $cols) && !isset($filtered['updated_at'])) {
            $filtered['updated_at'] = now();
        }

        return $filtered;
    }

    /**
     * Schema-aware create - sadece var olan kolonları insert eder
     */
    private function safeCreate(string $table, string $modelClass, array $data)
    {
        $filtered = $this->filterForTable($table, $data);
        return $modelClass::create($filtered);
    }

    /**
     * Müşterileri seed et
     */
    protected function seedCustomers(): void
    {
        if (!Schema::hasTable('customers')) {
            $this->command?->warn('⚠️  customers tablosu yok, atlanıyor...');
            return;
        }

        $this->command?->info('  → Customers seeding...');

        $customersData = DemoData::customers();

        foreach ($customersData as $index => $customerData) {
            $customer = $this->safeCreate('customers', Customer::class, [
                'name' => $customerData['name'],
                'email' => $this->generateEmail($customerData['name']),
                'phone' => $this->generatePhone($index + 1),
                'address' => 'Marmaris, Muğla, Turkey',
                'created_by' => $this->userId,
            ]);

            $this->customers[$customerData['key']] = $customer;
        }

        $count = count($this->customers);
        $this->command?->comment("    ✓ {$count} customers created");
    }

    /**
     * Tekneleri seed et
     */
    protected function seedVessels(): void
    {
        if (!Schema::hasTable('vessels')) {
            $this->command?->warn('⚠️  vessels tablosu yok, atlanıyor...');
            return;
        }

        $this->command?->info('  → Vessels seeding...');

        $vesselsData = DemoData::vessels();

        foreach ($vesselsData as $index => $vesselData) {
            $customer = $this->customers[$vesselData['owner']] ?? null;

            if (!$customer) {
                $this->command?->warn("    ⚠️  Customer {$vesselData['owner']} bulunamadı, vessel {$vesselData['key']} atlanıyor");
                continue;
            }

            $vessel = $this->safeCreate('vessels', Vessel::class, [
                'customer_id' => $customer->id,
                'name' => $vesselData['name'],
                'type' => $this->guessVesselType($vesselData['name']),
                'created_by' => $this->userId,
            ]);

            $this->vessels[$vesselData['key']] = $vessel;
        }

        $this->command?->comment("    ✓ " . count($this->vessels) . " vessels created");
    }

    /**
     * Vessel contact'ları seed et
     */
    protected function seedVesselContacts(): void
    {
        if (!Schema::hasTable('vessel_contacts')) {
            return; // Sessizce atla
        }

        $this->command?->info('  → Vessel contacts seeding...');

        $contactDefaults = DemoData::vesselContactsDefaults();
        $count = 0;

        foreach ($this->vessels as $key => $vessel) {
            foreach ($contactDefaults as $contactData) {
                $this->safeCreate('vessel_contacts', VesselContact::class, [
                    'vessel_id' => $vessel->id,
                    'role' => $contactData['role'],
                    'name' => "{$vessel->name} {$contactData['name']}",
                    'email' => Str::slug($vessel->name) . '-' . $contactData['role'] . '@demo.local',
                    'phone' => $this->generatePhone(1000 + $count),
                ]);
                $count++;
            }
        }

        $this->command?->comment("    ✓ {$count} vessel contacts created");
    }

    /**
     * Work order'ları seed et
     */
    protected function seedWorkOrders(): void
    {
        if (!Schema::hasTable('work_orders')) {
            $this->command?->warn('⚠️  work_orders tablosu yok, atlanıyor...');
            return;
        }

        $this->command?->info('  → Work orders seeding...');

        $plan = DemoData::documentsPlan();
        $targetCount = $plan['work_orders'] ?? 6;

        $vesselsList = array_values($this->vessels);
        
        for ($i = 0; $i < $targetCount && $i < count($vesselsList); $i++) {
            $vessel = $vesselsList[$i];

            $workOrder = $this->safeCreate('work_orders', WorkOrder::class, [
                'customer_id' => $vessel->customer_id,
                'vessel_id' => $vessel->id,
                'title' => "Work Order {$vessel->name}",
                'description' => "Demo work order for {$vessel->name}",
                'status' => 'draft',
                'planned_start_at' => now()->addDays(($i + 1) * 7),
                'planned_end_at' => now()->addDays(($i + 1) * 7 + 14),
                'created_by' => $this->userId,
            ]);

            $this->workOrders[] = $workOrder;
        }

        $this->command?->comment("    ✓ " . count($this->workOrders) . " work orders created");
    }

    /**
     * Quote'ları seed et
     */
    protected function seedQuotes(): void
    {
        if (!Schema::hasTable('quotes')) {
            $this->command?->warn('⚠️  quotes tablosu yok, atlanıyor...');
            return;
        }

        $this->command?->info('  → Quotes seeding...');

        $plan = DemoData::documentsPlan();
        $targetCount = $plan['quotes'] ?? 12;
        $currency = DemoData::currency();

        $vesselsList = array_values($this->vessels);

        for ($i = 0; $i < $targetCount; $i++) {
            $vessel = $vesselsList[$i % count($vesselsList)];
            $workOrder = $this->workOrders[$i % count($this->workOrders)] ?? null;

            $quote = $this->safeCreate('quotes', Quote::class, [
                'customer_id' => $vessel->customer_id,
                'vessel_id' => $vessel->id,
                'work_order_id' => ($i < count($this->workOrders)) ? $workOrder?->id : null,
                'title' => "Quote for {$vessel->name} - " . ($i + 1),
                'status' => $this->getDocumentStatus($i, ['draft', 'sent', 'accepted']),
                'issued_at' => now()->subDays($targetCount - $i),
                'currency' => $currency,
                'validity_days' => 15,
                'estimated_duration_days' => 10 + ($i * 2),
                'payment_terms' => 'Standard payment terms',
                'created_by' => $this->userId,
            ]);

            $this->quotes[] = $quote;

            // Quote items ekle
            $this->seedQuoteItems($quote, 3 + ($i % 4));
            
            // Totalleri recalculate et (eğer method varsa)
            if (method_exists($quote, 'recalculateTotals')) {
                $quote->recalculateTotals();
            }
        }

        $this->command?->comment("    ✓ " . count($this->quotes) . " quotes created");
    }

    /**
     * Quote item'ları seed et
     */
    protected function seedQuoteItems(Quote $quote, int $itemCount): void
    {
        if (!Schema::hasTable('quote_items')) {
            return;
        }

        $catalog = DemoData::quoteItemsCatalog();
        
        for ($i = 0; $i < $itemCount && $i < count($catalog); $i++) {
            $catalogItem = $catalog[$i % count($catalog)];

            $qty = $this->calculateQty($catalogItem['unit'], $i);

            $this->safeCreate('quote_items', QuoteItem::class, [
                'quote_id' => $quote->id,
                'item_type' => $this->guessItemType($catalogItem['code']),
                'description' => $catalogItem['title'],
                'qty' => $qty,
                'unit' => $catalogItem['unit'],
                'unit_price' => $catalogItem['unit_price'],
                'discount_amount' => 0,
                'vat_rate' => 20,
                'is_optional' => false,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Sales order'ları seed et
     */
    protected function seedSalesOrders(): void
    {
        if (!Schema::hasTable('sales_orders')) {
            $this->command?->warn('⚠️  sales_orders tablosu yok, atlanıyor...');
            return;
        }

        $this->command?->info('  → Sales orders seeding...');

        $plan = DemoData::documentsPlan();
        $targetCount = $plan['sales_orders'] ?? 8;
        $currency = DemoData::currency();

        for ($i = 0; $i < $targetCount && $i < count($this->quotes); $i++) {
            $quote = $this->quotes[$i];

            $salesOrder = $this->safeCreate('sales_orders', SalesOrder::class, [
                'customer_id' => $quote->customer_id,
                'vessel_id' => $quote->vessel_id,
                'work_order_id' => $quote->work_order_id,
                'quote_id' => $quote->id,
                'title' => "Sales Order for " . $quote->vessel->name,
                'status' => $this->getDocumentStatus($i, ['draft', 'confirmed', 'in_progress']),
                'currency' => $currency,
                'order_date' => now()->subDays($targetCount - $i - 1),
                'delivery_place' => 'Marmaris',
                'delivery_days' => 14 + ($i * 3),
                'payment_terms' => 'Standard payment terms',
                'created_by' => $this->userId,
            ]);

            $this->salesOrders[] = $salesOrder;

            // Sales order items ekle
            $this->seedSalesOrderItems($salesOrder, 3 + ($i % 3));
            
            // Totalleri recalculate et (eğer method varsa)
            if (method_exists($salesOrder, 'recalculateTotals')) {
                $salesOrder->recalculateTotals();
            }
        }

        $this->command?->comment("    ✓ " . count($this->salesOrders) . " sales orders created");
    }

    /**
     * Sales order item'ları seed et
     */
    protected function seedSalesOrderItems(SalesOrder $salesOrder, int $itemCount): void
    {
        if (!Schema::hasTable('sales_order_items')) {
            return;
        }

        $catalog = DemoData::quoteItemsCatalog();

        for ($i = 0; $i < $itemCount && $i < count($catalog); $i++) {
            $catalogItem = $catalog[($i + 3) % count($catalog)]; // Farklı itemlar

            $qty = $this->calculateQty($catalogItem['unit'], $i + 10);

            $this->safeCreate('sales_order_items', SalesOrderItem::class, [
                'sales_order_id' => $salesOrder->id,
                'item_type' => $this->guessItemType($catalogItem['code']),
                'description' => $catalogItem['title'],
                'qty' => $qty,
                'unit' => $catalogItem['unit'],
                'unit_price' => $catalogItem['unit_price'],
                'discount_amount' => 0,
                'vat_rate' => 20,
                'is_optional' => false,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Contract'ları seed et
     */
    protected function seedContracts(): void
    {
        if (!Schema::hasTable('contracts')) {
            $this->command?->warn('⚠️  contracts tablosu yok, atlanıyor...');
            return;
        }

        $this->command?->info('  → Contracts seeding...');

        $plan = DemoData::documentsPlan();
        $targetCount = $plan['contracts'] ?? 5;
        $currency = DemoData::currency();

        for ($i = 0; $i < $targetCount && $i < count($this->salesOrders); $i++) {
            $salesOrder = $this->salesOrders[$i];
            $customer = $salesOrder->customer;

            $contract = $this->safeCreate('contracts', Contract::class, [
                'sales_order_id' => $salesOrder->id,
                'status' => $this->getDocumentStatus($i, ['draft', 'issued', 'signed']),
                'issued_at' => now()->subDays($targetCount - $i - 1),
                'locale' => 'tr',
                'currency' => $currency,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'customer_address' => $customer->address,
                'subtotal' => $salesOrder->subtotal ?? 0,
                'tax_total' => $salesOrder->vat_total ?? 0,
                'grand_total' => $salesOrder->grand_total ?? 0,
                'payment_terms' => 'Standard payment terms',
                'warranty_terms' => '2 years warranty',
                'scope_text' => 'Full scope as per sales order',
                'created_by' => $this->userId,
            ]);

            // Contract delivery ekle
            $this->seedContractDelivery($contract);
        }

        $this->command?->comment("    ✓ {$targetCount} contracts created");
    }

    /**
     * Contract delivery seed et
     */
    protected function seedContractDelivery(Contract $contract): void
    {
        if (!Schema::hasTable('contract_deliveries')) {
            return;
        }

        $this->safeCreate('contract_deliveries', ContractDelivery::class, [
            'contract_id' => $contract->id,
            'channel' => 'email',
            'recipient_name' => $contract->customer_name,
            'recipient' => $contract->customer_email,
            'message' => 'Dear customer, please find attached your contract.',
            'included_pdf' => true,
            'included_attachments' => false,
            'status' => 'pending',
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Deterministik email üret
     */
    private function generateEmail(string $name): string
    {
        return Str::slug($name) . '@demo.local';
    }

    /**
     * Deterministik telefon üret
     */
    private function generatePhone(int $index): string
    {
        return sprintf('+90 555 000 %04d', $index);
    }

    /**
     * Tekne tipini tahmin et (M/Y = Motor Yacht, S/Y = Sailing Yacht)
     */
    private function guessVesselType(string $name): string
    {
        if (str_starts_with($name, 'M/Y')) {
            return 'Motor Yacht';
        }

        if (str_starts_with($name, 'S/Y')) {
            return 'Sailing Yacht';
        }

        return 'Yacht';
    }

    /**
     * Item type'ı code'dan tahmin et
     */
    private function guessItemType(string $code): string
    {
        return match (true) {
            str_starts_with($code, 'SERV-') => 'service',
            str_starts_with($code, 'LAB-') => 'labor',
            str_starts_with($code, 'MAT-') => 'material',
            default => 'other',
        };
    }

    /**
     * Deterministik qty hesapla
     */
    private function calculateQty(string $unit, int $index): float
    {
        return match ($unit) {
            'job' => 1,
            'hour' => 20 + ($index % 4) * 5, // 20, 25, 30, 35
            'lt' => 10 + ($index % 3) * 5,   // 10, 15, 20
            default => 1,
        };
    }

    /**
     * Deterministik document status getir
     */
    private function getDocumentStatus(int $index, array $statuses): string
    {
        return $statuses[$index % count($statuses)];
    }
}
