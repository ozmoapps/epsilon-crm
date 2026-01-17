<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'key' => 'starter',
                'name_tr' => 'Başlangıç',
                'tenant_limit' => 1,
                'seat_limit' => 1,
                'extra_seat_price_cents' => 9900, // 99 TL
                'is_active' => true,
            ],
            [
                'key' => 'professional',
                'name_tr' => 'Profesyonel',
                'tenant_limit' => 4,
                'seat_limit' => 4,
                'extra_seat_price_cents' => 7900,
                'is_active' => true,
            ],
            [
                'key' => 'unlimited',
                'name_tr' => 'Sınırsız',
                'tenant_limit' => null, // null = unlimited
                'seat_limit' => null,   // null = unlimited
                'extra_seat_price_cents' => 4900,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['key' => $plan['key']],
                $plan
            );
        }
    }
}
