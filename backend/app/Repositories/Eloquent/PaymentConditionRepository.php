<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentCondition;
use App\Repositories\Contracts\PaymentConditionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentConditionRepository implements PaymentConditionRepositoryInterface
{
    public function all(): Collection
    {
        return PaymentCondition::query()->orderBy('minimum_value')->get();
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return PaymentCondition::query()->orderBy('minimum_value')->paginate($perPage);
    }

    public function find(int $id): ?PaymentCondition
    {
        return PaymentCondition::query()->find($id);
    }

    public function findByCode(string $code): ?PaymentCondition
    {
        return PaymentCondition::query()->where('code', $code)->first();
    }

    public function create(array $data): PaymentCondition
    {
        return PaymentCondition::query()->create($data);
    }

    public function update(PaymentCondition $condition, array $data): PaymentCondition
    {
        $condition->update($data);

        return $condition->refresh();
    }

    public function delete(PaymentCondition $condition): void
    {
        $condition->delete();
    }
}
