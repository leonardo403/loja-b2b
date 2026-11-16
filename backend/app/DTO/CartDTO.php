<?php

namespace App\DTO;

final class CartDTO extends DataTransferObject
{
    /**
     * @param  array<int, CartItemDTO>  $items
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly array $items,
        public readonly int $itemCount,
        public readonly int $totalUnits,
        public readonly float $subtotal,
        public readonly float $savings,
        public readonly float $surchargePercentage,
        public readonly float $surcharge,
        public readonly float $total,
        public readonly ?PaymentConditionDTO $paymentCondition,
        public readonly array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'items' => array_map(fn (CartItemDTO $item) => $item->toArray(), $this->items),
            'item_count' => $this->itemCount,
            'total_units' => $this->totalUnits,
            'subtotal' => round($this->subtotal, 2),
            'savings' => round($this->savings, 2),
            'surcharge_percentage' => round($this->surchargePercentage, 2),
            'surcharge' => round($this->surcharge, 2),
            'total' => round($this->total, 2),
            'payment_condition' => $this->paymentCondition?->toArray(),
            'warnings' => $this->warnings,
        ];
    }
}
