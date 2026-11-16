<?php

namespace App\Services;

use App\DTO\DashboardStatsDTO;
use App\DTO\OrderDTO;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const LOW_STOCK_THRESHOLD = 20;

    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CustomerRepositoryInterface $customers,
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function stats(): DashboardStatsDTO
    {
        $data = Cache::remember(
            CacheKeys::ADMIN_DASHBOARD_STATS,
            now()->addMinute(),
            function () {
                $lowStock = $this->products->lowStock(self::LOW_STOCK_THRESHOLD, 8);

                return (new DashboardStatsDTO(
                    totals: [
                        'products' => $this->products->countActive(),
                        'customers' => $this->customers->count(),
                        'orders' => $this->orders->count(),
                        'open_orders' => $this->orders->countOpen(),
                        'revenue' => round($this->orders->revenue(), 2),
                        'low_stock' => $this->products->countLowStock(self::LOW_STOCK_THRESHOLD),
                    ],
                    lowStock: $lowStock->map(fn (Product $product) => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'category' => $product->category,
                        'quantity' => (int) ($product->stock?->quantity ?? 0),
                    ])->all(),
                    recentOrders: $this->orders->recent(6)
                        ->map(fn (Order $order) => OrderDTO::fromModel($order, false)->toArray())
                        ->all(),
                ))->toArray();
            },
        );

        return DashboardStatsDTO::fromArray($data);
    }
}
