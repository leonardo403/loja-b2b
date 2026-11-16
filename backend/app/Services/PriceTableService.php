<?php

namespace App\Services;

use App\DTO\PaginatedDTO;
use App\DTO\PriceTableDTO;
use App\DTO\PriceTableItemDTO;
use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Repositories\Contracts\PriceTableRepositoryInterface;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;

class PriceTableService
{
    public function __construct(
        private readonly PriceTableRepositoryInterface $priceTables,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tables(): array
    {
        return Cache::remember(
            CacheKeys::ADMIN_PRICE_TABLES,
            now()->addHour(),
            fn () => $this->priceTables->all()
                ->map(fn (PriceTable $table) => PriceTableDTO::fromModel($table)->toArray())
                ->all(),
        );
    }

    public function items(PriceTable $priceTable, array $filters, int $perPage): PaginatedDTO
    {
        return PaginatedDTO::fromPaginator(
            $this->priceTables->paginateItems($priceTable->id, $filters, $perPage),
            fn (PriceTableItem $item) => PriceTableItemDTO::fromModel($item),
        );
    }

    public function updateItem(PriceTable $priceTable, int $productId, array $data): PriceTableItemDTO
    {
        $item = $this->priceTables->upsertItem($priceTable->id, $productId, [
            'price' => $data['price'],
            'wholesale_price' => $data['wholesale_price'] ?? null,
            'wholesale_min_quantity' => $data['wholesale_min_quantity'] ?? null,
        ]);

        Cache::forget(CacheKeys::ADMIN_PRICE_TABLES);

        return PriceTableItemDTO::fromModel($item);
    }
}
