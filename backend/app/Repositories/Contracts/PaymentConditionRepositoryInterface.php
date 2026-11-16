<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentCondition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentConditionRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage): LengthAwarePaginator;

    public function find(int $id): ?PaymentCondition;

    public function findByCode(string $code): ?PaymentCondition;

    public function create(array $data): PaymentCondition;

    public function update(PaymentCondition $condition, array $data): PaymentCondition;

    public function delete(PaymentCondition $condition): void;
}
