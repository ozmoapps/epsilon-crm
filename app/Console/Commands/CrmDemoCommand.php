<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:demo
                            {--force : Silme işlemini onaylamak için gerekli}
                            {--seed : Silme sonrası demo verisi ekle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CRM demo verilerini güvenli şekilde sıfırlar (sadece local/testing ortamında)';

    /**
     * Silinecek tablolar - doğru dependency sırasına göre (child → parent)
     *
     * @var array<string>
     */
    protected array $tablesToClean = [
        // 1. Child tables (polymorphic ve bağlı tablolar)
        'activity_logs',
        'follow_ups',
        'quote_items',
        'sales_order_items',
        'contract_attachments',
        'contract_deliveries',
        'vessel_contacts',
        'vessel_owner_histories',
        
        // 2. Contracts (sales_orders'a bağlı)
        'contracts',
        'contract_sequences',
        
        // 3. Sales Orders (quotes, work_orders, vessels, customers'a bağlı)
        'sales_orders',
        'sales_order_sequences',
        
        // 4. Quotes (work_orders, vessels, customers'a bağlı)
        'quotes',
        'quote_sequences',
        
        // 5. Work Orders (vessels, customers'a bağlı)
        'work_orders',
        
        // 6. Vessels (customers'a bağlı)
        'vessels',
        
        // 7. Customers (en üstteki parent)
        'customers',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Ortam kontrolü
        if (!$this->checkEnvironment()) {
            return self::FAILURE;
        }

        // 2. --force kontrolü ve onay
        if (!$this->confirmDeletion()) {
            return self::FAILURE;
        }

        // 3. Before counts al
        $this->info('📊 Mevcut kayıt sayıları kontrol ediliyor...');
        $beforeCounts = $this->getTableCounts();
        
        // 4. Silme işlemi
        $this->newLine();
        $this->warn('🗑️  Silme işlemi başlıyor...');
        
        try {
            // FK constraints'leri geçici olarak devre dışı bırak (transaction DIŞINDA)
            $this->disableForeignKeyChecks();
            
            DB::beginTransaction();
            
            // Tabloları temizle
            $deletedCounts = $this->cleanTables();
            
            DB::commit();
            
            // FK constraints'leri tekrar aktif et (transaction DIŞINDA)
            $this->enableForeignKeyChecks();
            
            // 5. After counts al
            $afterCounts = $this->getTableCounts();
            
            // 6. Rapor göster
            $this->displayReport($beforeCounts, $afterCounts, $deletedCounts);
            
            // 7. Seed opsiyonu kontrolü - AKTIF
            if ($this->option('seed')) {
                $this->newLine();
                $this->info('🌱 Demo verileri seed ediliyor...');
                $this->newLine();
                
                try {
                    $this->call('db:seed', [
                        '--class' => \Database\Seeders\CrmDemoSeeder::class,
                        '--force' => true,
                    ]);
                    
                    // Seed sonrası doğrulama
                    $this->newLine();
                    $this->displaySeedVerification();
                } catch (\Exception $seedError) {
                    $this->newLine();
                    $this->error('❌ Seed sırasında hata oluştu: ' . $seedError->getMessage());
                    return self::FAILURE;
                }
            }
            
            $this->newLine();
            $this->info('✅ CRM demo verileri başarıyla temizlendi!');
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hata durumunda FK'leri tekrar aktif et
            try {
                $this->enableForeignKeyChecks();
            } catch (\Exception $fkException) {
                // FK enable hatası loglansın ama ana hatayı gölgelemesin
            }
            
            $this->newLine();
            $this->error('❌ Hata oluştu: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            return self::FAILURE;
        }
    }

    /**
     * Ortam kontrolü - sadece local/testing ortamında çalışsın
     */
    protected function checkEnvironment(): bool
    {
        $env = app()->environment();
        
        if (!app()->environment(['local', 'testing'])) {
            $this->error('❌ Bu komut sadece local veya testing ortamında çalıştırılabilir!');
            $this->error("   Mevcut ortam: {$env}");
            return false;
        }
        
        $this->info("✓ Ortam kontrolü geçti: {$env}");
        return true;
    }

    /**
     * --force flag kontrolü ve kullanıcı onayı
     */
    protected function confirmDeletion(): bool
    {
        if (!$this->option('force')) {
            $this->error('❌ Bu komut --force parametresi olmadan çalıştırılamaz!');
            $this->warn('   Örnek: php artisan crm:demo --force');
            return false;
        }
        
        $this->newLine();
        $this->warn('⚠️  DİKKAT: Bu işlem aşağıdaki tüm CRM verilerini SİLECEK:');
        $this->warn('   • Müşteriler (customers)');
        $this->warn('   • Tekneler (vessels)');
        $this->warn('   • Teklifler (quotes)');
        $this->warn('   • Satış Siparişleri (sales_orders)');
        $this->warn('   • İş Emirleri (work_orders)');
        $this->warn('   • Sözleşmeler (contracts)');
        $this->warn('   • İlgili tüm bağlı kayıtlar (items, attachments, history, vb.)');
        $this->newLine();
        $this->info('ℹ️  Korunacak veriler:');
        $this->info('   • Kullanıcılar (users)');
        $this->info('   • Şirket profili (company_profiles)');
        $this->info('   • Döviz kurları (currencies)');
        $this->info('   • Banka hesapları (bank_accounts)');
        $this->info('   • Sözleşme şablonları (contract_templates)');
        $this->newLine();
        
        return $this->confirm('Devam etmek istediğinize emin misiniz?', false);
    }

    /**
     * Foreign key constraint'leri devre dışı bırak (database-specific)
     */
    protected function disableForeignKeyChecks(): void
    {
        $driver = DB::connection()->getDriverName();
        
        try {
            match($driver) {
                'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
                'pgsql' => DB::statement('SET CONSTRAINTS ALL DEFERRED'),
                'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
                default => throw new \RuntimeException("Desteklenmeyen veritabanı driver'ı: {$driver}"),
            };
            
            $this->comment("  → Foreign key checks devre dışı bırakıldı ({$driver})");
        } catch (\Exception $e) {
            $this->warn("  ⚠️  FK disable uyarısı ({$driver}): " . $e->getMessage());
        }
    }

    /**
     * Foreign key constraint'leri tekrar aktif et (database-specific)
     */
    protected function enableForeignKeyChecks(): void
    {
        $driver = DB::connection()->getDriverName();
        
        try {
            match($driver) {
                'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
                'pgsql' => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'),
                'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
                default => throw new \RuntimeException("Desteklenmeyen veritabanı driver'ı: {$driver}"),
            };
            
            $this->comment("  → Foreign key checks tekrar aktif edildi ({$driver})");
        } catch (\Exception $e) {
            $this->warn("  ⚠️  FK enable uyarısı ({$driver}): " . $e->getMessage());
        }
    }

    /**
     * Tabloları temizle ve silinen kayıt sayılarını döndür
     *
     * @return array<string, int>
     */
    protected function cleanTables(): array
    {
        $deletedCounts = [];
        $driver = DB::connection()->getDriverName();
        
        $progressBar = $this->output->createProgressBar(count($this->tablesToClean));
        $progressBar->start();
        
        foreach ($this->tablesToClean as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                
                // Driver'a göre silme yöntemi
                match($driver) {
                    'sqlite' => $this->truncateSqlite($table),
                    'mysql' => DB::statement("TRUNCATE TABLE `{$table}`"),
                    'pgsql' => DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE"),
                    default => DB::table($table)->delete(),
                };
                
                $deletedCounts[$table] = $count;
            } else {
                $deletedCounts[$table] = 0;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        return $deletedCounts;
    }

    /**
     * SQLite için güvenli truncate işlemi
     */
    protected function truncateSqlite(string $table): void
    {
        // SQLite'ta DELETE FROM kullan (truncate desteklenmez)
        DB::statement("DELETE FROM `{$table}`");
        
        // Sequence'i sıfırla (varsa)
        try {
            DB::statement("DELETE FROM sqlite_sequence WHERE name='{$table}'");
        } catch (\Exception $e) {
            // sqlite_sequence yoksa veya başka hata varsa sorun değil
        }
    }

    /**
     * Belirtilen tabloların kayıt sayılarını al
     *
     * @return array<string, int>
     */
    protected function getTableCounts(): array
    {
        $counts = [];
        
        foreach ($this->tablesToClean as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->count();
            } else {
                $counts[$table] = 0;
            }
        }
        
        return $counts;
    }

    /**
     * Silme raporunu göster
     *
     * @param array<string, int> $before
     * @param array<string, int> $after
     * @param array<string, int> $deleted
     */
    protected function displayReport(array $before, array $after, array $deleted): void
    {
        $this->newLine();
        $this->info('📋 RAPOR:');
        $this->newLine();
        
        // Tablo şeklinde göster
        $headers = ['Tablo', 'Öncesi', 'Silinen', 'Sonrası'];
        $rows = [];
        
        $totalDeleted = 0;
        
        foreach ($this->tablesToClean as $table) {
            $beforeCount = $before[$table] ?? 0;
            $deletedCount = $deleted[$table] ?? 0;
            $afterCount = $after[$table] ?? 0;
            
            // Sadece silinen veya var olan tabloları göster
            if ($beforeCount > 0 || $deletedCount > 0) {
                $rows[] = [
                    $table,
                    $beforeCount,
                    $deletedCount,
                    $afterCount,
                ];
                $totalDeleted += $deletedCount;
            }
        }
        
        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info("🎯 Toplam {$totalDeleted} kayıt silindi.");
        
        // Korunan tabloları kontrol et
        $this->newLine();
        $this->info('🛡️  Korunan tablolar kontrol ediliyor...');
        
        $protectedTables = ['users', 'company_profiles', 'currencies', 'bank_accounts'];
        $protectedExists = false;
        
        foreach ($protectedTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $this->comment("  ✓ {$table}: {$count} kayıt (korundu)");
                    $protectedExists = true;
                }
            }
        }
        
        if (!$protectedExists) {
            $this->comment('  (Korunan tablolarda kayıt bulunamadı)');
        }
    }

    /**
     * Seed sonrası doğrulama göster
     */
    protected function displaySeedVerification(): void
    {
        $this->info('📊 SEED DOĞRULAMA:');
        $this->newLine();
        
        $verificationTables = [
            'customers' => 10,
            'vessels' => 14,
            'quotes' => 12,
            'sales_orders' => 8,
            'work_orders' => 6,
            'contracts' => 5,
        ];
        
        $headers = ['Tablo', 'Beklenen', 'Gerçek', 'Durum'];
        $rows = [];
        
        foreach ($verificationTables as $table => $expected) {
            if (Schema::hasTable($table)) {
                $actual = DB::table($table)->count();
                $status = ($actual === $expected) ? '✓' : '⚠';
                $rows[] = [$table, $expected, $actual, $status];
            } else {
                $rows[] = [$table, $expected, 'N/A', '-'];
            }
        }
        
        $this->table($headers, $rows);
    }
}
