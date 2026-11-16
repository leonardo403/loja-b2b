<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\PaymentConditionRequest;
use App\Models\PaymentCondition;
use App\Services\PaymentConditionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentConditionController extends ApiController
{
    public function __construct(
        private readonly PaymentConditionService $paymentConditions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->paymentConditions->list($this->perPage($request, 20))->toArray()
        );
    }

    public function store(PaymentConditionRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->paymentConditions->create($request->validated())->toArray()], 201);
    }

    public function update(PaymentConditionRequest $request, PaymentCondition $paymentCondition): JsonResponse
    {
        return $this->item($this->paymentConditions->update($paymentCondition, $request->validated()));
    }

    public function destroy(PaymentCondition $paymentCondition): Response
    {
        $this->paymentConditions->delete($paymentCondition);

        return response()->noContent();
    }
}
