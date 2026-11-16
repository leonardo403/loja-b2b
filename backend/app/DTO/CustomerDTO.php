<?php

namespace App\DTO;

use App\Models\Customer;

final class CustomerDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $cnpj,
        public readonly string $legalName,
        public readonly string $tradeName,
        public readonly string $city,
        public readonly string $state,
        public readonly PriceTableDTO $priceTable,
        public readonly float $creditLimit,
        public readonly float $creditUsed,
        public readonly float $availableCredit,
        public readonly bool $blocked,
        public readonly ?string $blockReason,
        public readonly ?string $email = null,
    ) {}

    public static function fromModel(Customer $customer, ?string $email = null): self
    {
        return new self(
            id: $customer->id,
            cnpj: $customer->cnpj,
            legalName: $customer->legal_name,
            tradeName: $customer->trade_name,
            city: $customer->city,
            state: $customer->state,
            priceTable: PriceTableDTO::fromModel($customer->priceTable),
            creditLimit: (float) $customer->credit_limit,
            creditUsed: (float) $customer->credit_used,
            availableCredit: $customer->availableCredit(),
            blocked: (bool) $customer->blocked,
            blockReason: $customer->block_reason,
            email: $email,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cnpj' => $this->cnpj,
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'city' => $this->city,
            'state' => $this->state,
            'price_table' => $this->priceTable->toArray(),
            'credit_limit' => round($this->creditLimit, 2),
            'credit_used' => round($this->creditUsed, 2),
            'available_credit' => round($this->availableCredit, 2),
            'blocked' => $this->blocked,
            'block_reason' => $this->blockReason,
            'email' => $this->email,
        ];
    }
}
