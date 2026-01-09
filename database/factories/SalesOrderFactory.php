<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'vessel_id' => Vessel::factory(),
            'title' => $this->faker->sentence(3),
            'status' => 'draft',
            'currency' => 'EUR',
            'order_date' => now()->toDateString(),
            'delivery_place' => $this->faker->city,
            'delivery_days' => 7,
            'payment_terms' => 'Ödeme, teslimde yapılacaktır.',
            'warranty_text' => '12 ay garanti.',
            'exclusions' => 'Hariç işler ayrıca fiyatlandırılır.',
            'notes' => $this->faker->sentence,
            'fx_note' => 'Kur farkı ayrıca yansıtılır.',
            'subtotal' => 1000,
            'discount_total' => 0,
            'vat_total' => 180,
            'grand_total' => 1180,
            'created_by' => User::factory(),
        ];
    }
}
