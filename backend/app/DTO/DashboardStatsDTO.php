<?php

namespace App\DTO;

final class DashboardStatsDTO extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $totals
     * @param  array<int, array<string, mixed>>  $lowStock
     * @param  array<int, array<string, mixed>>  $recentOrders
     */
    public function __construct(
        public readonly array $totals,
        public readonly array $lowStock,
        public readonly array $recentOrders,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totals: $data['totals'],
            lowStock: $data['low_stock'],
            recentOrders: $data['recent_orders'],
        );
    }

    public function toArray(): array
    {
        return [
            'totals' => $this->totals,
            'low_stock' => $this->lowStock,
            'recent_orders' => $this->recentOrders,
        ];
    }
}
