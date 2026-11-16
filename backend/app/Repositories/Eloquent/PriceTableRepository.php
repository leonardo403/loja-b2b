<?php

namespace App\Repositories\Eloquent;

use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Repositories\Contracts\PriceTableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PriceTableRepository implements PriceTableRepositoryInterface
{
    public function all(): Collection
    {
        return PriceTable::query()->withCount('items')->orderBy('code')->get();
    }

    public function find(int $id): ?PriceTable
    {
        return PriceTable::query()->find($id);
    }

    public function defaultTable(): PriceTable
    {
        return PriceTable::query()->where('code', 'PADRAO')->firstOrFail();
    }

    public function paginateItems(int $priceTableId, array $filters, int $perPage): LengthAwarePaginator
    {
        return PriceTableItem::query()
            ->select('price_table_items.*')
            ->join('products', 'products.id', '=', 'price_table_items.product_id')
            ->with('product')
            ->where('price_table_items.price_table_id', $priceTableId)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $like = "%{$search}%";
                $query->where(function ($query) use ($like) {
                    $query->where('products.name', 'like', $like)
                        ->orWhere('products.sku', 'like', $like)
                        ->orWhere('products.category', 'like', $like);
                });
            })
            ->orderBy('products.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function upsertItem(int $priceTableId, int $productId, array $data): PriceTableItem
    {
        return PriceTableItem::query()->updateOrCreate(
            ['price_table_id' => $priceTableId, 'product_id' => $productId],
            $data,
        )->load('product');
    }
}
