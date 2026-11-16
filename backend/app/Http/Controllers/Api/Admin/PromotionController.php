<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PromotionController extends ApiController
{
    public function __construct(
        private readonly PromotionService $promotions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'product_id']);

        return response()->json(
            $this->promotions->list($filters, $this->perPage($request, 15))->toArray()
        );
    }

    public function store(PromotionRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->promotions->create($request->validated())->toArray()], 201);
    }

    public function update(PromotionRequest $request, Promotion $promotion): JsonResponse
    {
        return $this->item($this->promotions->update($promotion, $request->validated()));
    }

    public function destroy(Promotion $promotion): Response
    {
        $this->promotions->delete($promotion);

        return response()->noContent();
    }
}
