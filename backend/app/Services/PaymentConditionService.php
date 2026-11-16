<?php

namespace App\Services;

use App\DTO\PaginatedDTO;
use App\DTO\PaymentConditionDTO;
use App\Exceptions\BusinessException;
use App\Models\PaymentCondition;
use App\Repositories\Contracts\PaymentConditionRepositoryInterface;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Cache;

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
        return Cache::remember(
            CacheKeys::CATALOG_PAYMENT_CONDITIONS,
            now()->addHour(),
            fn () => $this->paymentConditions->all()
                ->map(fn (PaymentCondition $condition) => PaymentConditionDTO::fromModel($condition)->toArray())
                ->all(),
        );
    }

    public function create(array $data): PaymentConditionDTO
    {
        $condition = $this->paymentConditions->create($data);

        Cache::forget(CacheKeys::CATALOG_PAYMENT_CONDITIONS);

        return PaymentConditionDTO::fromModel($condition);
    }

    public function update(PaymentCondition $condition, array $data): PaymentConditionDTO
    {
        $updated = $this->paymentConditions->update($condition, $data);

        Cache::forget(CacheKeys::CATALOG_PAYMENT_CONDITIONS);

        return PaymentConditionDTO::fromModel($updated);
    }

    public function delete(PaymentCondition $condition): void
    {
        if ($condition->orders()->exists()) {
            throw new BusinessException('Esta condição de pagamento possui pedidos vinculados e não pode ser excluída.');
        }

        $this->paymentConditions->delete($condition);

        Cache::forget(CacheKeys::CATALOG_PAYMENT_CONDITIONS);
    }
}
