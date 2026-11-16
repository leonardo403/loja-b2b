<?php

namespace App\Repositories\Contracts;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function paginateForCustomer(int $customerId, int $perPage): LengthAwarePaginator;

    public function paginateAll(array $filters, int $perPage): LengthAwarePaginator;

    public function findWithRelations(int $id): ?Order;

    public function create(array $attributes, array $items): Order;

    public function updateStatus(Order $order, OrderStatus $status): Order;

    public function revenue(): float;

    public function count(): int;

    public function countOpen(): int;

    public function recent(int $limit): Collection;
}
