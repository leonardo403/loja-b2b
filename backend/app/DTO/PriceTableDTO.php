<?php

namespace App\DTO;

use App\Models\PriceTable;

final class PriceTableDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $description,
        public readonly ?int $itemsCount = null,
    ) {}

    public static function fromModel(PriceTable $priceTable): self
    {
        return new self(
            id: $priceTable->id,
            code: $priceTable->code,
            description: $priceTable->description,
            itemsCount: $priceTable->items_count ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'items_count' => $this->itemsCount,
        ];
    }
}
