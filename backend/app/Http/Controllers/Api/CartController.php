<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Cart\CartPreviewRequest;
use App\Services\AuthService;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends ApiController
{
    public function __construct(
        private readonly CartService $cart,
        private readonly AuthService $auth,
    ) {}

    public function preview(CartPreviewRequest $request): JsonResponse
    {
        $customer = $this->auth->requireCustomer($request->user());

        $cart = $this->cart->preview(
            $customer,
            $request->validated('items'),
            $request->validated('payment_condition'),
        );

        return $this->item($cart);
    }
}
