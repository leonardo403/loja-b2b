<?php

namespace Database\Factories;

use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceTableItem>
 */
class PriceTableItemFactory extends Factory
{
    protected $model = PriceTableItem::class;

    public function definition(): array
    {
        return [
            'price_table_id' => PriceTable::factory(),
            'product_id' => Product::factory(),
            'price' => 10.00,
            'wholesale_price' => null,
            'wholesale_min_quantity' => null,
        ];
    }
}
