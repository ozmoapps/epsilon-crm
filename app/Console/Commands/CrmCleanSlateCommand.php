<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\ContractTemplateSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CrmCleanSlateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:clean-slate
                            {--force : İşlemi onay sormadan çalıştır (Zorunlu)}
                            {--email=master@epsilon.test : Master Admin e-posta hesabı}
                            {--password=password : Master Admin şifresi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Veritabanını sıfırlar, sadece temel konfigürasyonu (Plan vb.) ve 1 adet Master Admin oluşturur.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Guard: Ortam kontrolü
        if (! App::isLocal() && ! App::runningUnitTests()) {
            $this->error('HATA: Bu komut sadece local veya testing ortamında çalıştırılabilir!');
            return self::FAILURE;
        }

        // 2. Guard: Force flag zorunluluğu
        if (! $this->option('force')) {
            $this->error('HATA: Bu işlem tüm veriyi sileceği için --force parametresi zorunludur.');
            return self::FAILURE;
        }

        $email = $this->option('email');
        $password = $this->option('password');

        $this->info('🚀 Clean Slate işlemi başlatılıyor...');

        // 3. Migrate:Fresh
        $this->info('♻️  Veritabanı sıfırlanıyor (migrate:fresh)...');
        $this->call('migrate:fresh', [
            '--force' => true,
        ]);

        // 3.1 Wipe Migration Artifacts
        // Bazı migration dosyaları (örn: 2026...create_tenants...) "Varsayılan Firma" oluşturuyor.
        // Clean Slate tamamen boş olmalı, bu yüzden bunları temizliyoruz.
        if (Schema::hasTable('tenants')) {
            Schema::disableForeignKeyConstraints();
            DB::table('tenants')->truncate();
            Schema::enableForeignKeyConstraints();
            $this->info('🧹 Migration kaynaklı varsayılan tenant temizlendi.');
        }

        // 4. Core Bootstrap Seeders
        // Sadece sistemin çalışması için zorunlu olan seedleri çalıştırıyoruz.
        // Asla demo veri (tenant, customer, quote vb.) üretmiyoruz.
        $this->info('🌱 Core Bootstrap seedleri çalıştırılıyor...');

        $bootstrapSeeders = [
            PlanSeeder::class,
            CompanyProfileSeeder::class,
            ContractTemplateSeeder::class,
        ];

        foreach ($bootstrapSeeders as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            } else {
                $this->warn("⚠️  Seeder bulunamadı, atlanıyor: {$seeder}");
            }
        }

        // 5. Master Admin Creation
        $this->info('👤 Master Admin oluşturuluyor...');

        // updateOrCreate mantığıyla (aslında fresh olduğu için create yeterli ama sağlam olsun)
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Platform Master Admin',
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->newLine();
        $this->info('✅ Clean Slate tamamlandı!');
        $this->newLine();
        $this->line('  -----------------------------------------');
        $this->line("  Master Admin: <comment>{$user->email}</comment>");
        $this->line("  Password:     <comment>{$password}</comment>");
        $this->line('  -----------------------------------------');
        $this->newLine();
        $this->info('➡️  Artık welcome ekranından yeni üyelik oluşturabilir, "tertemiz" ortamda test yapabilirsiniz.');

        return self::SUCCESS;
    }
}
