<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Factories\Factory;

class VesselFactory extends Factory
{
    protected $model = Vessel::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => $this->faker->word,
            'type' => $this->faker->randomElement(['Motor', 'Sail', 'Yacht']),
            'registration_number' => $this->faker->bothify('TR-####'),
            'notes' => $this->faker->sentence,
        ];
    }
}
