<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface
{
    public function lockByProductIds(array $productIds): Collection;

    public function decrement(int $productId, int $quantity): void;

    public function increment(int $productId, int $quantity): void;

    public function setQuantity(int $productId, int $quantity): void;
}
