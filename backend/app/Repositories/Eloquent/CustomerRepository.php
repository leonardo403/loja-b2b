<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Customer::query()
            ->with(['priceTable', 'users'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $like = "%{$search}%";
                $query->where(function ($query) use ($like) {
                    $query->where('legal_name', 'like', $like)
                        ->orWhere('trade_name', 'like', $like)
                        ->orWhere('cnpj', 'like', $like)
                        ->orWhere('city', 'like', $like);
                });
            })
            ->when(
                isset($filters['blocked']) && $filters['blocked'] !== '',
                fn ($query) => $query->where('blocked', filter_var($filters['blocked'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('trade_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Customer
    {
        return Customer::query()->with(['priceTable', 'users'])->find($id);
    }

    public function create(array $data): Customer
    {
        return Customer::query()->create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->refresh();
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function count(): int
    {
        return Customer::query()->count();
    }

    public function hasOrders(Customer $customer): bool
    {
        return $customer->orders()->exists();
    }
}
