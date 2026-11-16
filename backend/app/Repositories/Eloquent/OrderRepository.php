<?php

namespace App\Repositories\Eloquent;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    private const RELATIONS = ['customer.priceTable', 'paymentCondition', 'items'];

    public function paginateForCustomer(int $customerId, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->with(self::RELATIONS)
            ->where('customer_id', $customerId)
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateAll(array $filters, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->with(self::RELATIONS)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $like = "%{$search}%";
                $query->where(function ($query) use ($like) {
                    $query->where('id', 'like', $like)
                        ->orWhereHas('customer', fn ($query) => $query->where('trade_name', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithRelations(int $id): ?Order
    {
        return Order::query()->with(self::RELATIONS)->find($id);
    }

    public function create(array $attributes, array $items): Order
    {
        $order = Order::query()->create($attributes);
        $order->items()->createMany($items);

        return $order->load(self::RELATIONS);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['status' => $status]);

        return $order->refresh()->load(self::RELATIONS);
    }

    public function revenue(): float
    {
        return (float) Order::query()
            ->where('status', '!=', OrderStatus::Canceled->value)
            ->sum('total');
    }

    public function count(): int
    {
        return Order::query()->count();
    }

    public function countOpen(): int
    {
        return Order::query()
            ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Confirmed->value])
            ->count();
    }

    public function recent(int $limit): Collection
    {
        return Order::query()
            ->with(['customer', 'paymentCondition'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
