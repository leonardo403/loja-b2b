<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Services\AuthService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly AuthService $auth,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customer = $this->auth->requireCustomer($request->user());

        return response()->json(
            $this->orders->listForCustomer($customer, $this->perPage($request, 10))->toArray()
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $customer = $this->auth->requireCustomer($request->user());

        $order = $this->orders->create(
            $customer,
            $request->user(),
            $request->validated('items'),
            $request->validated('payment_condition'),
            $request->validated('notes'),
        );

        return response()->json(['data' => $order->toArray()], 201);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $customer = $this->auth->requireCustomer($request->user());

        return $this->item($this->orders->findForCustomer($customer, $order));
    }
}
