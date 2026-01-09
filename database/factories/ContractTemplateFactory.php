<?php

namespace Database\Factories;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'Standart Şablon',
            'locale' => 'tr',
            'content' => '<h1>Örnek Şablon</h1>',
            'format' => 'html',
            'is_default' => false,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
