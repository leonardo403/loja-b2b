<?php

namespace Tests\Concerns;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentCondition;
use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\OrderService;

trait CreatesCommerceFixtures
{
    protected function defaultPriceTable(): PriceTable
    {
        return PriceTable::factory()->default()->create();
    }

    protected function productWithPrice(
        PriceTable $table,
        array $attributes = [],
        array $price = [],
        int $quantity = 10,
    ): Product {
        $product = Product::factory()->create($attributes);

        Stock::factory()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        $this->priceItem($product, $table, $price);

        return $product->fresh(['stock', 'priceItems', 'promotions']);
    }

    protected function priceItem(Product $product, PriceTable $table, array $price = []): PriceTableItem
    {
        return PriceTableItem::factory()->create([
            'price_table_id' => $table->id,
            'product_id' => $product->id,
            'price' => $price['price'] ?? 10.00,
            'wholesale_price' => $price['wholesale_price'] ?? null,
            'wholesale_min_quantity' => $price['wholesale_min_quantity'] ?? null,
        ]);
    }

    protected function customerFor(PriceTable $table, array $attributes = []): Customer
    {
        return Customer::factory()->create([
            'price_table_id' => $table->id,
            ...$attributes,
        ]);
    }

    protected function paymentCondition(array $attributes = []): PaymentCondition
    {
        return PaymentCondition::factory()->create($attributes);
    }

    protected function customerUser(Customer $customer, array $attributes = []): User
    {
        return User::factory()->customer($customer)->create($attributes);
    }

    protected function adminUser(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function checkout(
        Customer $customer,
        Product $product,
        int $quantity = 1,
        ?PaymentCondition $condition = null,
        ?User $user = null,
    ): Order {
        $condition ??= $this->paymentCondition();
        $user ??= $this->customerUser($customer);

        $order = app(OrderService::class)->create(
            $customer->fresh(),
            $user,
            [['product_id' => $product->id, 'quantity' => $quantity]],
            $condition->code,
        );

        return Order::query()->findOrFail($order->id);
    }
}
