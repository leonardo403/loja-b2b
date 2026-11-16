<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\Stock;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    private const RELATIONS = ['stock', 'priceItems.priceTable', 'promotions'];

    public function paginate(array $filters, int $perPage, bool $onlyActive = true): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(self::RELATIONS)
            ->when($onlyActive, fn ($query) => $query->where('active', true))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $like = "%{$search}%";
                    $query->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('ean', 'like', $like)
                        ->orWhere('brand', 'like', $like);
                });
            })
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['brand'] ?? null, fn ($query, string $brand) => $query->where('brand', $brand))
            ->when(
                isset($filters['active']) && $filters['active'] !== '',
                fn ($query) => $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN))
            );

        match ($filters['sort'] ?? 'name_asc') {
            'name_desc' => $query->orderByDesc('name'),
            'recent' => $query->latest('id'),
            default => $query->orderBy('name'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id, bool $onlyActive = false): ?Product
    {
        return Product::query()
            ->with(self::RELATIONS)
            ->when($onlyActive, fn ($query) => $query->where('active', true))
            ->find($id);
    }

    public function findManyWithRelations(array $ids): Collection
    {
        return Product::query()
            ->with(self::RELATIONS)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    public function featured(int $limit): Collection
    {
        return Product::query()
            ->with(self::RELATIONS)
            ->where('active', true)
            ->whereHas('stock', fn ($query) => $query->where('quantity', '>', 0))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function categories(): array
    {
        return Product::query()
            ->where('active', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    public function brands(): array
    {
        return Product::query()
            ->where('active', true)
            ->whereNotNull('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function countActive(): int
    {
        return Product::query()->where('active', true)->count();
    }

    public function countLowStock(int $threshold): int
    {
        return Product::query()
            ->where('active', true)
            ->whereHas('stock', fn ($query) => $query->where('quantity', '<=', $threshold))
            ->count();
    }

    public function lowStock(int $threshold, int $limit): Collection
    {
        return Product::query()
            ->with('stock')
            ->where('active', true)
            ->whereHas('stock', fn ($query) => $query->where('quantity', '<=', $threshold))
            ->orderBy(
                Stock::select('quantity')
                    ->whereColumn('stocks.product_id', 'products.id')
            )
            ->limit($limit)
            ->get();
    }
}
