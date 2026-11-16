<?php

namespace App\Repositories\Eloquent;

use App\Models\Promotion;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PromotionRepository implements PromotionRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Promotion::query()
            ->with('product')
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->whereHas('product', function ($query) use ($search) {
                    $like = "%{$search}%";
                    $query->where('name', 'like', $like)->orWhere('sku', 'like', $like);
                });
            })
            ->orderByDesc('starts_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Promotion
    {
        return Promotion::query()->with('product')->find($id);
    }

    public function create(array $data): Promotion
    {
        return Promotion::query()->create($data)->load('product');
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);

        return $promotion->refresh()->load('product');
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
    }

    public function activeForProducts(array $productIds): Collection
    {
        $today = now()->toDateString();

        return Promotion::query()
            ->whereIn('product_id', $productIds)
            ->where('starts_at', '<=', $today)
            ->where('ends_at', '>=', $today)
            ->get();
    }
}
