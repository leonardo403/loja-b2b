<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function paginate(array $filters, int $perPage, bool $onlyActive = true): LengthAwarePaginator;

    public function find(int $id, bool $onlyActive = false): ?Product;

    public function findManyWithRelations(array $ids): Collection;

    public function featured(int $limit): Collection;

    public function categories(): array;

    public function brands(): array;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;

    public function countActive(): int;

    public function countLowStock(int $threshold): int;

    public function lowStock(int $threshold, int $limit): Collection;
}
