<?php

namespace App\DTO;

use App\Models\OrderItem;

final class OrderItemDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly int $quantity,
        public readonly float $listPrice,
        public readonly float $unitPrice,
        public readonly float $discountPercentage,
        public readonly bool $wholesaleApplied,
        public readonly float $subtotal,
    ) {}

    public static function fromModel(OrderItem $item): self
    {
        return new self(
            productId: $item->product_id,
            productName: $item->product_name,
            productSku: $item->product_sku,
            quantity: $item->quantity,
            listPrice: (float) $item->list_price,
            unitPrice: (float) $item->unit_price,
            discountPercentage: (float) $item->discount_percentage,
            wholesaleApplied: (bool) $item->wholesale_applied,
            subtotal: (float) $item->subtotal,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
            'list_price' => round($this->listPrice, 2),
            'unit_price' => round($this->unitPrice, 2),
            'discount_percentage' => round($this->discountPercentage, 2),
            'wholesale_applied' => $this->wholesaleApplied,
            'subtotal' => round($this->subtotal, 2),
        ];
    }
}
