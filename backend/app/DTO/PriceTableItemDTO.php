<?php

namespace App\DTO;

use App\Models\PriceTableItem;

final class PriceTableItemDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $category,
        public readonly float $price,
        public readonly ?float $wholesalePrice,
        public readonly ?int $wholesaleMinQuantity,
    ) {}

    public static function fromModel(PriceTableItem $item): self
    {
        return new self(
            id: $item->id,
            productId: $item->product_id,
            sku: $item->product->sku,
            name: $item->product->name,
            category: $item->product->category,
            price: (float) $item->price,
            wholesalePrice: $item->wholesale_price !== null ? (float) $item->wholesale_price : null,
            wholesaleMinQuantity: $item->wholesale_min_quantity,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category,
            'price' => round($this->price, 2),
            'wholesale_price' => $this->wholesalePrice !== null ? round($this->wholesalePrice, 2) : null,
            'wholesale_min_quantity' => $this->wholesaleMinQuantity,
        ];
    }
}
