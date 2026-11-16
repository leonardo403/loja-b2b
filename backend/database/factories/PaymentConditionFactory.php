<?php

namespace Database\Factories;

use App\Models\PaymentCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentCondition>
 */
class PaymentConditionFactory extends Factory
{
    protected $model = PaymentCondition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('COND-###')),
            'description' => fake()->words(3, true),
            'minimum_value' => 0.00,
            'surcharge_percentage' => 0.00,
            'is_term' => false,
        ];
    }
}
