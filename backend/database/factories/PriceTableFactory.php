<?php

namespace Database\Factories;

use App\Models\PriceTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceTable>
 */
class PriceTableFactory extends Factory
{
    protected $model = PriceTable::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('TAB-###')),
            'description' => fake()->words(3, true),
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'code' => 'PADRAO',
            'description' => 'Tabela padrão',
        ]);
    }
}
