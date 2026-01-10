<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'vessel_id' => Vessel::factory(),
            'title' => $this->faker->sentence(3),
            'status' => 'draft',
            'currency' => 'EUR',
            'validity_days' => 15,
            'estimated_duration_days' => 7,
            'payment_terms' => 'Ödeme, teslimde yapılacaktır.',
            'warranty_text' => '12 ay garanti.',
            'exclusions' => 'Hariç işler ayrıca fiyatlandırılır.',
            'notes' => $this->faker->sentence,
            'fx_note' => 'Kur farkı ayrıca yansıtılır.',
            'created_by' => User::factory(),
        ];
    }
}
