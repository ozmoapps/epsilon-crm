<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (CompanyProfile::query()->exists()) {
            return;
        }

        $defaults = config('company', []);

        $footerText = trim(collect([
            Arr::get($defaults, 'name'),
            Arr::get($defaults, 'address'),
            Arr::get($defaults, 'phone'),
            Arr::get($defaults, 'email'),
            Arr::get($defaults, 'tax_no'),
        ])->filter()->implode(' · '));

        CompanyProfile::create([
            'name' => Arr::get($defaults, 'name', 'Epsilon CRM'),
            'address' => Arr::get($defaults, 'address'),
            'phone' => Arr::get($defaults, 'phone'),
            'email' => Arr::get($defaults, 'email'),
            'tax_no' => Arr::get($defaults, 'tax_no'),
            'footer_text' => $footerText,
        ]);
    }
}
