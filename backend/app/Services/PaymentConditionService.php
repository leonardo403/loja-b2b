<?php

namespace App\Services;

use App\DTO\PaginatedDTO;
use App\DTO\PaymentConditionDTO;
use App\Exceptions\BusinessException;
use App\Models\PaymentCondition;
use App\Repositories\Contracts\PaymentConditionRepositoryInterface;

class PaymentConditionService
{
    public function __construct(
        private readonly PaymentConditionRepositoryInterface $paymentConditions,
    ) {}

    public function list(int $perPage): PaginatedDTO
    {
        return PaginatedDTO::fromPaginator(
            $this->paymentConditions->paginate($perPage),
            fn (PaymentCondition $condition) => PaymentConditionDTO::fromModel($condition),
        );
    }

    public function all(): array
    {
        return $this->paymentConditions->all()
            ->map(fn (PaymentCondition $condition) => PaymentConditionDTO::fromModel($condition)->toArray())
            ->all();
    }

    public function create(array $data): PaymentConditionDTO
    {
        return PaymentConditionDTO::fromModel($this->paymentConditions->create($data));
    }

    public function update(PaymentCondition $condition, array $data): PaymentConditionDTO
    {
        return PaymentConditionDTO::fromModel($this->paymentConditions->update($condition, $data));
    }

    public function delete(PaymentCondition $condition): void
    {
        if ($condition->orders()->exists()) {
            throw new BusinessException('Esta condição de pagamento possui pedidos vinculados e não pode ser excluída.');
        }

        $this->paymentConditions->delete($condition);
    }
}
