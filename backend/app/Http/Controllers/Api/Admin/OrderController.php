<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(
        private readonly OrderService $orders,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'customer_id', 'search']);

        return response()->json(
            $this->orders->adminList($filters, $this->perPage($request, 15))->toArray()
        );
    }

    public function show(int $order): JsonResponse
    {
        return $this->item($this->orders->find($order));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $status = OrderStatus::from($request->validated('status'));

        return $this->item($this->orders->changeStatus($order, $status));
    }
}
