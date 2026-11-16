<?php

namespace App\DTO;

final class PriceDTO extends DataTransferObject
{
    public function __construct(
        public readonly float $unitPrice,
        public readonly float $listPrice,
        public readonly ?float $wholesalePrice,
        public readonly ?int $wholesaleMinQuantity,
        public readonly bool $wholesaleApplied,
        public readonly float $discountPercentage,
        public readonly bool $onPromotion,
        public readonly ?string $promotionEndsAt,
    ) {}

    public function toArray(): array
    {
        return [
            'unit_price' => round($this->unitPrice, 2),
            'list_price' => round($this->listPrice, 2),
            'wholesale_price' => $this->wholesalePrice !== null ? round($this->wholesalePrice, 2) : null,
            'wholesale_min_quantity' => $this->wholesaleMinQuantity,
            'wholesale_applied' => $this->wholesaleApplied,
            'discount_percentage' => round($this->discountPercentage, 2),
            'on_promotion' => $this->onPromotion,
            'promotion_ends_at' => $this->promotionEndsAt,
        ];
    }
}
