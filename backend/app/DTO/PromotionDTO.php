<?php

namespace App\DTO;

use App\Models\Promotion;

final class PromotionDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly ?string $productName,
        public readonly ?string $productSku,
        public readonly float $discountPercentage,
        public readonly string $startsAt,
        public readonly string $endsAt,
        public readonly bool $active,
    ) {}

    public static function fromModel(Promotion $promotion): self
    {
        return new self(
            id: $promotion->id,
            productId: $promotion->product_id,
            productName: $promotion->relationLoaded('product') ? $promotion->product?->name : null,
            productSku: $promotion->relationLoaded('product') ? $promotion->product?->sku : null,
            discountPercentage: (float) $promotion->discount_percentage,
            startsAt: $promotion->starts_at->toDateString(),
            endsAt: $promotion->ends_at->toDateString(),
            active: $promotion->isActive(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'discount_percentage' => round($this->discountPercentage, 2),
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'active' => $this->active,
        ];
    }
}
