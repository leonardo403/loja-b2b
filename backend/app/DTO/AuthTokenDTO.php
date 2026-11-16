<?php

namespace App\DTO;

final class AuthTokenDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $token,
        public readonly string $tokenType,
        public readonly UserDTO $user,
    ) {}

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'token_type' => $this->tokenType,
            'user' => $this->user->toArray(),
        ];
    }
}
