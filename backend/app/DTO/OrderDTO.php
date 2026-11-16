<?php

namespace App\DTO;

use App\Models\Order;

final class OrderDTO extends DataTransferObject
{
    /**
     * @param  array<int, OrderItemDTO>  $items
     */
    public function __construct(
        public readonly int $id,
        public readonly string $number,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly ?CustomerDTO $customer,
        public readonly ?PaymentConditionDTO $paymentCondition,
        public readonly float $subtotal,
        public readonly float $savings,
        public readonly float $surcharge,
        public readonly float $total,
        public readonly ?string $notes,
        public readonly string $createdAt,
        public readonly array $items = [],
    ) {}

    public static function fromModel(Order $order, bool $withItems = true): self
    {
        return new self(
            id: $order->id,
            number: str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            status: $order->status->value,
            statusLabel: $order->status->label(),
            customer: $order->relationLoaded('customer') && $order->customer
                ? CustomerDTO::fromModel($order->customer)
                : null,
            paymentCondition: $order->relationLoaded('paymentCondition') && $order->paymentCondition
                ? PaymentConditionDTO::fromModel($order->paymentCondition)
                : null,
            subtotal: (float) $order->subtotal,
            savings: (float) $order->savings,
            surcharge: (float) $order->surcharge,
            total: (float) $order->total,
            notes: $order->notes,
            createdAt: $order->created_at?->toIso8601String() ?? '',
            items: $withItems
                ? $order->items->map(fn ($item) => OrderItemDTO::fromModel($item))->all()
                : [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'customer' => $this->customer?->toArray(),
            'payment_condition' => $this->paymentCondition?->toArray(),
            'subtotal' => round($this->subtotal, 2),
            'savings' => round($this->savings, 2),
            'surcharge' => round($this->surcharge, 2),
            'total' => round($this->total, 2),
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'items' => array_map(fn (OrderItemDTO $item) => $item->toArray(), $this->items),
        ];
    }
}
