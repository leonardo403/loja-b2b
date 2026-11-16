<?php

namespace App\Repositories\Contracts;

use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PromotionRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Promotion;

    public function create(array $data): Promotion;

    public function update(Promotion $promotion, array $data): Promotion;

    public function delete(Promotion $promotion): void;

    public function activeForProducts(array $productIds): Collection;
}
