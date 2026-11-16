<?php

namespace App\DTO;

use App\Models\PaymentCondition;

final class PaymentConditionDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $description,
        public readonly float $minimumValue,
        public readonly float $surchargePercentage,
        public readonly bool $isTerm,
    ) {}

    public static function fromModel(PaymentCondition $condition): self
    {
        return new self(
            id: $condition->id,
            code: $condition->code,
            description: $condition->description,
            minimumValue: (float) $condition->minimum_value,
            surchargePercentage: (float) $condition->surcharge_percentage,
            isTerm: (bool) $condition->is_term,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'minimum_value' => round($this->minimumValue, 2),
            'surcharge_percentage' => round($this->surchargePercentage, 2),
            'is_term' => $this->isTerm,
        ];
    }
}
