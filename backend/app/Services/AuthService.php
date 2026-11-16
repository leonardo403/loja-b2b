<?php

namespace App\Services;

use App\DTO\AuthTokenDTO;
use App\DTO\UserDTO;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password, string $deviceName = 'web'): AuthTokenDTO
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $user->load('customer.priceTable');
        $token = $user->createToken($deviceName)->plainTextToken;

        return new AuthTokenDTO($token, 'Bearer', UserDTO::fromModel($user));
    }

    public function me(User $user): UserDTO
    {
        $user->load('customer.priceTable');

        return UserDTO::fromModel($user);
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    public function requireCustomer(User $user): Customer
    {
        $user->loadMissing('customer.priceTable');

        if ($user->customer === null) {
            throw new BusinessException('Esta conta não está vinculada a um cliente corporativo.');
        }

        return $user->customer;
    }
}
