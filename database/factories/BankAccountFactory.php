<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Hesabı',
            'bank_name' => fake()->company(),
            'branch_name' => fake()->city(),
            'iban' => 'TR' . fake()->numerify('########################'),
            'currency_id' => Currency::factory(),
        ];
    }
}
