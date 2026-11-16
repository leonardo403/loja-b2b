<?php

namespace App\Http\Controllers\Api;

use App\Services\PaymentConditionService;
use Illuminate\Http\JsonResponse;

class PaymentConditionController extends ApiController
{
    public function __construct(
        private readonly PaymentConditionService $paymentConditions,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->paymentConditions->all()]);
    }
}
