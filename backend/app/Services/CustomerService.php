<?php

namespace App\Services;

use App\DTO\CustomerDTO;
use App\DTO\PaginatedDTO;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
    ) {}

    public function list(array $filters, int $perPage): PaginatedDTO
    {
        return PaginatedDTO::fromPaginator(
            $this->customers->paginate($filters, $perPage),
            fn (Customer $customer) => $this->toDto($customer),
        );
    }

    public function find(int $id): CustomerDTO
    {
        $customer = $this->customers->find($id);

        if ($customer === null) {
            throw new BusinessException('Cliente não encontrado.');
        }

        return $this->toDto($customer);
    }

    public function create(array $data): CustomerDTO
    {
        $credentials = $this->extractCredentials($data);

        $customer = DB::transaction(function () use ($data, $credentials) {
            $customer = $this->customers->create($this->customerFields($data));

            if ($credentials['email'] !== null && $credentials['password'] !== null) {
                $this->createUser($customer, $credentials['email'], $credentials['password']);
            }

            return $customer;
        });

        return $this->find($customer->id);
    }

    public function update(Customer $customer, array $data): CustomerDTO
    {
        $credentials = $this->extractCredentials($data);

        DB::transaction(function () use ($customer, $data, $credentials) {
            $this->customers->update($customer, $this->customerFields($data));

            $user = $customer->users()->orderBy('id')->first();

            if ($credentials['email'] !== null) {
                if ($user === null) {
                    if ($credentials['password'] === null) {
                        throw new BusinessException('Informe uma senha para criar o acesso do cliente.');
                    }

                    $this->createUser($customer, $credentials['email'], $credentials['password']);
                } else {
                    $user->update([
                        'name' => $customer->trade_name,
                        'email' => $credentials['email'],
                        ...($credentials['password'] !== null ? ['password' => $credentials['password']] : []),
                    ]);
                }
            }
        });

        return $this->find($customer->id);
    }

    public function delete(Customer $customer): void
    {
        if ($this->customers->hasOrders($customer)) {
            throw new BusinessException('Este cliente possui pedidos vinculados e não pode ser excluído.');
        }

        DB::transaction(function () use ($customer) {
            $customer->users()->delete();
            $this->customers->delete($customer);
        });
    }

    private function toDto(Customer $customer): CustomerDTO
    {
        $email = $customer->users->first()?->email;

        return CustomerDTO::fromModel($customer, $email);
    }

    /**
     * @return array{email: ?string, password: ?string}
     */
    private function extractCredentials(array &$data): array
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        unset($data['email'], $data['password']);

        return [
            'email' => $email !== null && $email !== '' ? $email : null,
            'password' => $password !== null && $password !== '' ? $password : null,
        ];
    }

    private function customerFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'cnpj',
            'legal_name',
            'trade_name',
            'city',
            'state',
            'price_table_id',
            'credit_limit',
            'credit_used',
            'blocked',
            'block_reason',
        ]));
    }

    private function createUser(Customer $customer, string $email, string $password): User
    {
        return User::query()->create([
            'name' => $customer->trade_name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Customer,
            'customer_id' => $customer->id,
        ]);
    }
}
