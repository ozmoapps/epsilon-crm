<?php

namespace Database\Factories;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'section' => 'Genel',
            'item_type' => 'labor',
            'description' => $this->faker->sentence,
            'qty' => 1,
            'unit' => 'adet',
            'unit_price' => 100,
            'discount_amount' => 0,
            'vat_rate' => 20,
            'is_optional' => false,
            'sort_order' => 1,
        ];
    }
}
