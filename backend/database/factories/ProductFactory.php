<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-#####')),
            'ean' => fake()->unique()->numerify('############'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'brand' => fake()->randomElement(['Marca A', 'Marca B', 'Marca C']),
            'category' => fake()->randomElement(['Categoria A', 'Categoria B', 'Categoria C']),
            'active' => true,
        ];
    }
}
