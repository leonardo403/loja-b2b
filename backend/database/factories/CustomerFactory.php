<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PriceTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'PR', 'SC']),
            'price_table_id' => PriceTable::factory(),
            'credit_limit' => 5000.00,
            'credit_used' => 0.00,
            'blocked' => false,
            'block_reason' => null,
        ];
    }
}
