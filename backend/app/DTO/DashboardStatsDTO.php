<?php

namespace App\DTO;

final class DashboardStatsDTO extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $totals
     * @param  array<int, array<string, mixed>>  $lowStock
     * @param  array<int, OrderDTO>  $recentOrders
     */
    public function __construct(
        public readonly array $totals,
        public readonly array $lowStock,
        public readonly array $recentOrders,
    ) {}

    public function toArray(): array
    {
        return [
            'totals' => $this->totals,
            'low_stock' => $this->lowStock,
            'recent_orders' => array_map(fn (OrderDTO $order) => $order->toArray(), $this->recentOrders),
        ];
    }
}
