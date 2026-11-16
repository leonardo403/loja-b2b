<?php

namespace App\Repositories\Contracts;

use App\Models\PriceTable;
use App\Models\PriceTableItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PriceTableRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?PriceTable;

    public function defaultTable(): PriceTable;

    public function paginateItems(int $priceTableId, array $filters, int $perPage): LengthAwarePaginator;

    public function upsertItem(int $priceTableId, int $productId, array $data): PriceTableItem;
}
