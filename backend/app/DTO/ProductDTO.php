<?php

namespace App\DTO;

use App\Models\Product;

final class ProductDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $sku,
        public readonly ?string $ean,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $brand,
        public readonly ?string $category,
        public readonly bool $active,
        public readonly int $stockQuantity,
        public readonly ?PriceDTO $price = null,
    ) {}

    public static function fromModel(Product $product, ?PriceDTO $price = null): self
    {
        return new self(
            id: $product->id,
            sku: $product->sku,
            ean: $product->ean,
            name: $product->name,
            description: $product->description,
            brand: $product->brand,
            category: $product->category,
            active: (bool) $product->active,
            stockQuantity: (int) ($product->stock?->quantity ?? 0),
            price: $price,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'ean' => $this->ean,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'category' => $this->category,
            'active' => $this->active,
            'stock_quantity' => $this->stockQuantity,
            'in_stock' => $this->stockQuantity > 0,
            'price' => $this->price?->toArray(),
        ];
    }
}
