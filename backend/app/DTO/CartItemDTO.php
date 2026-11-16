<?php

namespace App\DTO;

final class CartItemDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $brand,
        public readonly ?string $category,
        public readonly int $quantity,
        public readonly int $stockQuantity,
        public readonly float $listPrice,
        public readonly float $unitPrice,
        public readonly float $discountPercentage,
        public readonly bool $wholesaleApplied,
        public readonly float $lineSubtotal,
        public readonly float $lineListTotal,
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category,
            'quantity' => $this->quantity,
            'stock_quantity' => $this->stockQuantity,
            'list_price' => round($this->listPrice, 2),
            'unit_price' => round($this->unitPrice, 2),
            'discount_percentage' => round($this->discountPercentage, 2),
            'wholesale_applied' => $this->wholesaleApplied,
            'line_subtotal' => round($this->lineSubtotal, 2),
            'line_list_total' => round($this->lineListTotal, 2),
            'line_savings' => round($this->lineListTotal - $this->lineSubtotal, 2),
        ];
    }
}
