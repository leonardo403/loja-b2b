<?php

namespace App\DTO;

use App\Enums\UserRole;
use App\Models\User;

final class UserDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly bool $isAdmin,
        public readonly ?CustomerDTO $customer = null,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role->value,
            isAdmin: $user->role === UserRole::Admin,
            customer: $user->customer
                ? CustomerDTO::fromModel($user->customer, $user->email)
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_admin' => $this->isAdmin,
            'customer' => $this->customer?->toArray(),
        ];
    }
}
