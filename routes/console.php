<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\User;
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Contract;
use App\Models\WorkOrder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'demo:reset
        {--force : Onay sormadan çalıştır}
        {--wipe-only : Sadece sil, demo data üretme}
        {--n=5 : Oluşturulacak müşteri/teklif sayısı (1..50)}
        {--so=5 : Oluşturulacak satış siparişi sayısı}
        {--contracts=2 : Oluşturulacak sözleşme sayısı}
        {--work-orders=2 : Oluşturulacak iş emri sayısı}',
    function () {
        $force = (bool) $this->option('force');
        $wipeOnly = (bool) $this->option('wipe-only');

        $n = (int) ($this->option('n') ?? 5);
        $n = max(1, min(50, $n));

        $soCount = max(0, (int) ($this->option('so') ?? $n));
        $contractCount = max(0, (int) ($this->option('contracts') ?? 2));
        $workOrderCount = max(0, (int) ($this->option('work-orders') ?? 2));

        $user = User::query()->orderBy('id')->first();

        if (! $user) {
            $this->error('Sistemde kullanıcı yok. Önce en az 1 kullanıcı oluşturmalısın (users tablosu boş).');
            return self::FAILURE;
        }

        if (! $force) {
            $ok = $this->confirm('DİKKAT: Users hariç tüm veriler silinecek (truncate/delete). Devam edelim mi?', false);
            if (! $ok) {
                $this->info('İşlem iptal edildi.');
                return self::SUCCESS;
            }
        }

        // Wipe list: table varsa temizle, yoksa geç.
        // (Projeye yeni tablolar eklendikçe sorun çıkarmasın diye kontrollü)
        $tablesToWipe = [
            // Logs / follow-ups / saved views
            'activity_logs',
            'follow_ups',
            'saved_views',

            // Contracts
            'contract_attachments',
            'contract_deliveries',
            'contract_template_versions',
            'contract_templates',
            'contract_sequences',
            'contracts',

            // Sales Orders
            'sales_order_items',
            'sales_order_sequences',
            'sales_orders',

            // Quotes
            'quote_items',
            'quote_sequences',
            'quotes',

            // Work Orders + base entities
            'work_orders',
            'vessels',
            'customers',

            // Settings / masters (isteğe bağlı; sen “users hariç hepsi” dediğin için burada)
            'bank_accounts',
            'company_profiles',
            'currencies',

            // Framework tables (istersen çıkarabilirsin)
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];

        $driver = DB::connection()->getDriverName();

        $this->info('Foreign key constraints disable ediliyor...');
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tablesToWipe as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                if ($driver === 'pgsql') {
                    DB::statement('TRUNCATE TABLE "' . $table . '" RESTART IDENTITY CASCADE;');
                    continue;
                }

                if ($driver === 'sqlite') {
                    DB::table($table)->delete();
                    // autoincrement reset (sqlite_sequence varsa)
                    try {
                        DB::statement("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
                    } catch (\Throwable $e) {
                        // sqlite_sequence olmayabilir (WITHOUT ROWID vs) -> yoksa sessiz geç
                    }
                    continue;
                }

                // mysql/mariadb
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
            $this->info('Foreign key constraints tekrar aktif edildi.');
        }

        $this->info('Silme tamamlandı (users hariç).');

        if ($wipeOnly) {
            $this->info('wipe-only seçildi. Demo veri üretilmedi.');
            return self::SUCCESS;
        }

        // Yardımcı: Column varsa forceFill
        $forceFillIfColumn = function ($model, array $attrs) {
            $table = method_exists($model, 'getTable') ? $model->getTable() : null;
            if (! $table) {
                return;
            }

            $fillable = [];
            foreach ($attrs as $key => $value) {
                if (Schema::hasColumn($table, $key)) {
                    $fillable[$key] = $value;
                }
            }

            if (! empty($fillable)) {
                $model->forceFill($fillable)->save();
            }
        };

        $this->info("Demo veri üretiliyor: {$n} müşteri + {$n} tekne + {$n} teklif + {$soCount} SO + {$contractCount} sözleşme + {$workOrderCount} iş emri...");

        // Gerçekçi isimler (demo amaçlı)
        $customerSeeds = [
            ['name' => 'Ahmet Yılmaz',      'phone' => '+90 532 111 22 33', 'email' => 'ahmet.yilmaz@example.com',      'address' => 'Marmaris, Muğla', 'notes' => 'Demo müşteri.'],
            ['name' => 'Zeynep Kaya',       'phone' => '+90 533 222 33 44', 'email' => 'zeynep.kaya@example.com',       'address' => 'Göcek, Muğla',    'notes' => 'Demo müşteri.'],
            ['name' => 'Mehmet Demir',      'phone' => '+90 534 333 44 55', 'email' => 'mehmet.demir@example.com',      'address' => 'Bodrum, Muğla',   'notes' => 'Demo müşteri.'],
            ['name' => 'Johannes Müller',   'phone' => '+49 151 444 55 66', 'email' => 'johannes.mueller@example.com',  'address' => 'Hamburg, DE',     'notes' => 'Demo müşteri (yurt dışı).'],
            ['name' => 'Sofia Rossi',       'phone' => '+39 349 555 66 77', 'email' => 'sofia.rossi@example.com',       'address' => 'Genoa, IT',       'notes' => 'Demo müşteri (yurt dışı).'],
            ['name' => 'Onur Şahin',        'phone' => '+90 535 666 77 88', 'email' => 'onur.sahin@example.com',        'address' => 'Fethiye, Muğla',  'notes' => 'Demo müşteri.'],
            ['name' => 'Elif Aydın',        'phone' => '+90 536 777 88 99', 'email' => 'elif.aydin@example.com',        'address' => 'İzmir',           'notes' => 'Demo müşteri.'],
            ['name' => 'Mert Çelik',        'phone' => '+90 537 888 99 00', 'email' => 'mert.celik@example.com',        'address' => 'İstanbul',        'notes' => 'Demo müşteri.'],
            ['name' => 'Ayşe Karaca',       'phone' => '+90 538 999 00 11', 'email' => 'ayse.karaca@example.com',       'address' => 'Antalya',         'notes' => 'Demo müşteri.'],
            ['name' => 'Daniel Smith',      'phone' => '+44 7700 900 111',  'email' => 'daniel.smith@example.com',      'address' => 'London, UK',      'notes' => 'Demo müşteri (yurt dışı).'],
        ];

        $vesselSeeds = [
            ['name' => 'M/Y TROY WIZARD', 'type' => 'Motor Yacht',   'registration_number' => 'TR-MAR-2026-001', 'notes' => 'Demo tekne.'],
            ['name' => 'S/Y LUNA',        'type' => 'Sailing Yacht', 'registration_number' => 'TR-GCK-2026-002', 'notes' => 'Demo tekne.'],
            ['name' => 'M/Y SEA BREEZE',  'type' => 'Motor Yacht',   'registration_number' => 'TR-BDR-2026-003', 'notes' => 'Demo tekne.'],
            ['name' => 'M/Y ALBATROSS',   'type' => 'Motor Yacht',   'registration_number' => 'DE-HAM-2026-004', 'notes' => 'Demo tekne.'],
            ['name' => 'S/Y AURORA',      'type' => 'Sailing Yacht', 'registration_number' => 'IT-GEN-2026-005', 'notes' => 'Demo tekne.'],
            ['name' => 'M/Y PEGASUS',     'type' => 'Motor Yacht',   'registration_number' => 'TR-FTH-2026-006', 'notes' => 'Demo tekne.'],
            ['name' => 'S/Y NEPTUNE',     'type' => 'Sailing Yacht', 'registration_number' => 'TR-IZM-2026-007', 'notes' => 'Demo tekne.'],
            ['name' => 'M/Y ORION',       'type' => 'Motor Yacht',   'registration_number' => 'TR-IST-2026-008', 'notes' => 'Demo tekne.'],
            ['name' => 'S/Y BOREAS',      'type' => 'Sailing Yacht', 'registration_number' => 'TR-ANT-2026-009', 'notes' => 'Demo tekne.'],
            ['name' => 'M/Y CALYPSO',     'type' => 'Motor Yacht',   'registration_number' => 'UK-LON-2026-010', 'notes' => 'Demo tekne.'],
        ];

        $quoteTitles = [
            'Borda Boya + Vernik Yenileme',
            'Karina Antifouling Uygulaması',
            'Polisaj + Detaylı Temizlik Paketi',
            'Osmosis Kontrol + Epoksi Bariyer Opsiyonu',
            'Teak Bakım + Derz Yenileme',
            'Elektrik & Aydınlatma Revizyonu',
            'Krom Aksam Bakım + Parlatma',
            'Güverte Onarım + Jelcoat Rötuş',
            'Makine Bakımı + Sarf Malzeme',
            'Kışlama / Yaza Hazırlık Paketi',
        ];

        // Quote statüleri: ilk soCount tanesini accepted yapacağız (sonra converted)
        $fallbackStatuses = ['draft', 'sent', 'accepted', 'sent', 'cancelled'];

        $customers = [];
        $vessels = [];
        $quotes = [];

        DB::transaction(function () use (
            $n,
            $soCount,
            $contractCount,
            $workOrderCount,
            $user,
            $customerSeeds,
            $vesselSeeds,
            $quoteTitles,
            $fallbackStatuses,
            &$customers,
            &$vessels,
            &$quotes,
            $forceFillIfColumn
        ) {
            // 1) Customers + Vessels + Quotes
            for ($i = 0; $i < $n; $i++) {
                $c = $customerSeeds[$i % count($customerSeeds)];
                $v = $vesselSeeds[$i % count($vesselSeeds)];

                $customer = Customer::create([
                    'name' => $c['name'],
                    'phone' => $c['phone'] ?? null,
                    'email' => $c['email'] ?? null,
                    'address' => $c['address'] ?? null,
                    'notes' => $c['notes'] ?? null,
                ]);

                // created_by audit varsa set et
                $forceFillIfColumn($customer, ['created_by' => $user->id]);

                $vessel = Vessel::create([
                    'customer_id' => $customer->id,
                    'name' => $v['name'],
                    'type' => $v['type'] ?? null,
                    'registration_number' => $v['registration_number'] ?? null,
                    'notes' => $v['notes'] ?? null,
                ]);

                $forceFillIfColumn($vessel, ['created_by' => $user->id]);

                // İlk soCount tanesi accepted olacak, diğerleri fallback
                $status = ($i < $soCount) ? 'accepted' : $fallbackStatuses[$i % count($fallbackStatuses)];

                $quote = Quote::create([
                    'customer_id' => $customer->id,
                    'vessel_id' => $vessel->id,
                    'title' => $quoteTitles[$i % count($quoteTitles)],
                    'status' => $status,
                    'issued_at' => now()->toDateString(),
                    'location' => $customer->address,
                    'currency' => 'EUR',
                    'validity_days' => 15,
                    'estimated_duration_days' => 30,
                    'payment_terms' => 'Ödeme: %50 peşin, %50 teslimde.',
                    'warranty_text' => 'İşçilik 12 ay garantilidir. Kullanıcı hatası/çarpma hariçtir.',
                    'notes' => 'Demo teklif kaydı (ekip içi ortak kullanım).',
                    'created_by' => $user->id,
                ]);

                // 3 demo kalem
                $items = [
                    [
                        'section' => 'Hazırlık',
                        'item_type' => 'labor',
                        'description' => 'Yüzey hazırlık, maskeleme, zımpara',
                        'qty' => '12.00',
                        'unit' => 'saat',
                        'unit_price' => '45.00',
                        'discount_amount' => '0.00',
                        'vat_rate' => '20.00',
                        'is_optional' => false,
                        'sort_order' => 1,
                    ],
                    [
                        'section' => 'Uygulama',
                        'item_type' => 'material',
                        'description' => 'Astar + son kat boya sistemi (malzeme)',
                        'qty' => '1.00',
                        'unit' => 'set',
                        'unit_price' => '650.00',
                        'discount_amount' => ($i % 2 === 0) ? '50.00' : '0.00',
                        'vat_rate' => '20.00',
                        'is_optional' => false,
                        'sort_order' => 2,
                    ],
                    [
                        'section' => 'Opsiyon',
                        'item_type' => 'other',
                        'description' => 'Ek koruma / ekstra parlatma (opsiyonel)',
                        'qty' => '1.00',
                        'unit' => 'paket',
                        'unit_price' => '180.00',
                        'discount_amount' => '0.00',
                        'vat_rate' => '20.00',
                        'is_optional' => true,
                        'sort_order' => 3,
                    ],
                ];

                foreach ($items as $it) {
                    QuoteItem::create(array_merge($it, [
                        'quote_id' => $quote->id,
                    ]));
                }

                if (method_exists($quote, 'recalculateTotals')) {
                    $quote->recalculateTotals();
                }

                // statüye göre timestamp set
                if ($status === 'sent') {
                    $quote->forceFill(['sent_at' => now()->subDays(2)])->save();
                } elseif ($status === 'accepted') {
                    $quote->forceFill([
                        'sent_at' => now()->subDays(5),
                        'accepted_at' => now()->subDays(1),
                    ])->save();
                }

                $customers[] = $customer;
                $vessels[] = $vessel;
                $quotes[] = $quote;
            }

            // 2) Sales Orders (soCount)
            $soCount = min($soCount, count($quotes));

            $salesOrders = [];
            $salesOrderStatusPool = ['confirmed', 'in_progress', 'completed', 'confirmed', 'confirmed'];

            for ($i = 0; $i < $soCount; $i++) {
                $quote = $quotes[$i];
                $customer = $customers[$i];
                $vessel = $vessels[$i];

                $status = $salesOrderStatusPool[$i % count($salesOrderStatusPool)];

                $salesOrder = SalesOrder::create([
                    'customer_id' => $customer->id,
                    'vessel_id' => $vessel->id,
                    'quote_id' => $quote->id,
                    'title' => $quote->title,
                    'status' => $status,
                    'currency' => $quote->currency ?? 'EUR',
                    'order_date' => now()->toDateString(),
                    'delivery_place' => $customer->address,
                    'delivery_days' => 30,
                    'payment_terms' => $quote->payment_terms,
                    'warranty_text' => $quote->warranty_text,
                    'exclusions' => $quote->exclusions,
                    'notes' => 'Demo satış siparişi.',
                    'fx_note' => $quote->fx_note,
                    'created_by' => $user->id,
                ]);

                // Quote item’larını SO item olarak kopyala (demo uyumu)
                $quoteItems = QuoteItem::query()
                    ->where('quote_id', $quote->id)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($quoteItems as $qi) {
                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'section' => $qi->section,
                        'item_type' => $qi->item_type,
                        'description' => $qi->description,
                        'qty' => (string) $qi->qty,
                        'unit' => $qi->unit,
                        'unit_price' => (string) $qi->unit_price,
                        'discount_amount' => (string) ($qi->discount_amount ?? '0.00'),
                        'vat_rate' => (string) ($qi->vat_rate ?? '0.00'),
                        'is_optional' => (bool) $qi->is_optional,
                        'sort_order' => (int) $qi->sort_order,
                    ]);
                }

                if (method_exists($salesOrder, 'recalculateTotals')) {
                    $salesOrder->recalculateTotals();
                }

                // Quote -> converted (demo akışı)
                if (Schema::hasColumn($quote->getTable(), 'status')) {
                    $quote->forceFill(['status' => 'converted'])->save();
                }

                $salesOrders[] = $salesOrder;
            }

            // 3) Work Orders (workOrderCount) - ilk satış siparişlerine bağla
            $workOrderCount = min($workOrderCount, count($salesOrders));

            for ($i = 0; $i < $workOrderCount; $i++) {
                $salesOrder = $salesOrders[$i];

                $woStatus = ($i === 0) ? 'planned' : 'in_progress';
                $start = now()->addDays($i + 1)->setTime(9, 0);
                $end = (clone $start)->addDays(5)->setTime(18, 0);

                $workOrder = WorkOrder::create([
                    'customer_id' => $salesOrder->customer_id,
                    'vessel_id' => $salesOrder->vessel_id,
                    'title' => 'İş Emri - ' . ($salesOrder->order_no ?? ('SO#' . $salesOrder->id)),
                    'description' => 'Demo iş emri. Satış siparişinden türetildi.',
                    'status' => $woStatus,
                    'planned_start_at' => $start,
                    'planned_end_at' => $end,
                ]);

                $forceFillIfColumn($workOrder, ['created_by' => $user->id]);

                // SO’ya bağla
                $salesOrder->forceFill(['work_order_id' => $workOrder->id])->save();

                // Quote’da work_order_id alanı varsa aynı WO’ya bağla
                if ($salesOrder->quote_id) {
                    $quote = Quote::query()->find($salesOrder->quote_id);
                    if ($quote && Schema::hasColumn($quote->getTable(), 'work_order_id')) {
                        $quote->forceFill(['work_order_id' => $workOrder->id])->save();
                    }
                }
            }

            // 4) Contracts (contractCount) - ilk satış siparişlerine bağla
            $contractCount = min($contractCount, count($salesOrders));

            for ($i = 0; $i < $contractCount; $i++) {
                $salesOrder = $salesOrders[$i];
                $customer = Customer::query()->find($salesOrder->customer_id);

                // status çeşitliliği
                $cStatus = ($i === 0) ? 'sent' : 'issued';

                $contractAttrs = [
                    'sales_order_id' => $salesOrder->id,
                    'status' => $cStatus,
                    'issued_at' => now()->toDateString(),
                    'signed_at' => null,
                    'locale' => 'tr',
                    'currency' => $salesOrder->currency ?? 'EUR',
                    'customer_name' => $customer?->name ?? 'Demo Müşteri',
                    'customer_company' => null,
                    'customer_tax_no' => null,
                    'customer_address' => $customer?->address,
                    'customer_email' => $customer?->email,
                    'customer_phone' => $customer?->phone,
                    // totals (tablo isimleri farklı olabilir: tax_total vs vat_total)
                    'subtotal' => (string) ($salesOrder->subtotal ?? '0.00'),
                    'tax_total' => (string) ($salesOrder->vat_total ?? '0.00'),
                    'grand_total' => (string) ($salesOrder->grand_total ?? '0.00'),
                    'payment_terms' => $salesOrder->payment_terms,
                    'warranty_terms' => $salesOrder->warranty_text,
                    'scope_text' => 'Demo sözleşme kapsam metni.',
                    'exclusions_text' => $salesOrder->exclusions,
                    'delivery_terms' => 'Teslim: İşin tamamlanmasını takiben marina teslimi.',
                    'rendered_body' => '<h1>Sözleşme</h1><p>Bu demo sözleşme otomatik üretilmiştir.</p>',
                    'rendered_at' => now(),
                    'created_by' => $user->id,
                ];

                // Bazı projelerde revision/is_current alanları var; varsa ekle (yoksa ignore)
                if (Schema::hasTable('contracts')) {
                    if (Schema::hasColumn('contracts', 'revision_no')) {
                        $contractAttrs['revision_no'] = 1;
                    }
                    if (Schema::hasColumn('contracts', 'is_current')) {
                        $contractAttrs['is_current'] = true;
                    }
                    if (Schema::hasColumn('contracts', 'root_contract_id')) {
                        $contractAttrs['root_contract_id'] = null;
                    }
                }

                $contract = Contract::create($contractAttrs);

                // SalesOrder status -> contracted (sözleşme oluştuğu için)
                if (Schema::hasColumn($salesOrder->getTable(), 'status')) {
                    $salesOrder->forceFill(['status' => 'contracted'])->save();
                }
            }
        });

        $this->info('Demo veri üretimi tamamlandı ✅');
        $this->info("Üretilen: {$n} müşteri, {$n} teklif, {$soCount} satış siparişi, {$contractCount} sözleşme, {$workOrderCount} iş emri.");
        $this->info('Dashboard / Quotes / Sales Orders ekranlarından kontrol edebilirsin.');

        return self::SUCCESS;
    }
)->purpose('Users hariç tüm verileri temizler; demo müşteri/teklif + satış siparişi + sözleşme + iş emri üretir.');
