<?php

namespace App\Http\Controllers\Api;

use App\DTO\DataTransferObject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class ApiController
{
    protected function item(DataTransferObject $dto): JsonResponse
    {
        return response()->json(['data' => $dto->toArray()]);
    }

    protected function currentUser(Request $request): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user() ?? $request->user();

        return $user;
    }

    protected function perPage(Request $request, int $default = 12, int $max = 100): int
    {
        return max(1, min($max, (int) $request->integer('per_page', $default)));
    }
}
