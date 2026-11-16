<?php

namespace App\Services;

use App\DTO\PriceDTO;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\PriceTable;
use App\Models\PriceTableItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Repositories\Contracts\PriceTableRepositoryInterface;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;

class PricingService
{
    private ?PriceTable $defaultTable = null;

    public function __construct(
        private readonly PriceTableRepositoryInterface $priceTables,
    ) {}

    public function priceForCustomer(Product $product, Customer $customer, int $quantity = 1): PriceDTO
    {
        return $this->priceFor($product, $customer->priceTable, $quantity);
    }

    public function priceFor(Product $product, PriceTable $table, int $quantity = 1): PriceDTO
    {
        $item = $this->resolveItem($product, $table);

        if ($item === null) {
            throw new BusinessException("O produto \"{$product->name}\" não está disponível na sua tabela de preços.");
        }

        $wholesaleApplied = $item->wholesale_price !== null
            && $item->wholesale_min_quantity !== null
            && $quantity >= $item->wholesale_min_quantity;

        $basePrice = $wholesaleApplied
            ? (float) $item->wholesale_price
            : (float) $item->price;

        $promotion = $this->activePromotion($product);
        $discountPercentage = $promotion ? (float) $promotion->discount_percentage : 0.0;
        $unitPrice = round($basePrice * (1 - $discountPercentage / 100), 2);

        return new PriceDTO(
            unitPrice: $unitPrice,
            listPrice: (float) $item->price,
            wholesalePrice: $item->wholesale_price !== null ? (float) $item->wholesale_price : null,
            wholesaleMinQuantity: $item->wholesale_min_quantity,
            wholesaleApplied: $wholesaleApplied,
            discountPercentage: $discountPercentage,
            onPromotion: $promotion !== null,
            promotionEndsAt: $promotion?->ends_at->toDateString(),
        );
    }

    public function defaultTable(): PriceTable
    {
        if ($this->defaultTable !== null) {
            return $this->defaultTable;
        }

        $attributes = Cache::remember(
            CacheKeys::DEFAULT_PRICE_TABLE,
            now()->addHour(),
            fn () => $this->priceTables->defaultTable()->getAttributes(),
        );

        return $this->defaultTable = (new PriceTable)->newFromBuilder($attributes);
    }

    public function activePromotion(Product $product): ?Promotion
    {
        return $product->promotions
            ->filter(fn (Promotion $promotion) => $promotion->isActive())
            ->sortByDesc('discount_percentage')
            ->first();
    }

    private function resolveItem(Product $product, PriceTable $table): ?PriceTableItem
    {
        $item = $product->priceItems->firstWhere('price_table_id', $table->id);

        if ($item !== null) {
            return $item;
        }

        $defaultId = $this->defaultTable()->id;

        return $product->priceItems->firstWhere('price_table_id', $defaultId);
    }
}
