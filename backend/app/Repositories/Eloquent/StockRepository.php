<?php

namespace App\Repositories\Eloquent;

use App\Models\Stock;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function lockByProductIds(array $productIds): Collection
    {
        return Stock::query()
            ->whereIn('product_id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');
    }

    public function decrement(int $productId, int $quantity): void
    {
        Stock::query()->where('product_id', $productId)->decrement('quantity', $quantity);
    }

    public function increment(int $productId, int $quantity): void
    {
        Stock::query()->where('product_id', $productId)->increment('quantity', $quantity);
    }

    public function setQuantity(int $productId, int $quantity): void
    {
        Stock::query()->updateOrCreate(
            ['product_id' => $productId],
            ['quantity' => $quantity],
        );
    }
}
