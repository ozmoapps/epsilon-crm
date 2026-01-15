<?php

namespace App\Console\Commands;

use App\Models\Vessel;
use Illuminate\Console\Command;

class AuditVesselOwnership extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vessels:audit-ownership';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sahipsiz tekne kontrolü yapar (customer_id NULL olanlar)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Vessels Ownership Audit');
        $this->newLine();

        // Total vessel sayısı
        $totalVessels = Vessel::count();
        $this->line("📦 Total vessels: {$totalVessels}");

        // NULL customer_id olanlar
        $orphanedVessels = Vessel::whereNull('customer_id')->get();
        $orphanedCount = $orphanedVessels->count();

        $this->line("⚠️  NULL customer_id count: {$orphanedCount}");
        $this->newLine();

        // Eğer sahipsiz tekne varsa listele
        if ($orphanedCount > 0) {
            $this->error('❌ Sahipsiz tekneler bulundu:');
            $this->newLine();

            $this->table(
                ['ID', 'Name'],
                $orphanedVessels->map(fn($vessel) => [
                    $vessel->id,
                    $vessel->name ?? '(unnamed)'
                ])
            );

            $this->newLine();
            $this->error("✗ Audit FAILED: {$orphanedCount} vessel(s) without customer_id");

            return 1; // Exit code 1
        }

        // Her şey OK
        $this->info('✓ Audit PASSED: Tüm teknelerin customer_id değeri mevcut');

        return 0; // Exit code 0
    }
}
