<?php

namespace App\Http\Controllers\Api;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    public function __construct(
        private readonly ProductService $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category', 'brand', 'sort']);

        return response()->json(
            $this->products->list($filters, $this->perPage($request, 12, 48), $this->currentUser($request))->toArray()
        );
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = max(1, min(12, (int) $request->integer('limit', 8)));

        return response()->json([
            'data' => $this->products->featured($limit, $this->currentUser($request)),
        ]);
    }

    public function filters(): JsonResponse
    {
        return response()->json(['data' => $this->products->filters()]);
    }

    public function show(Request $request, int $product): JsonResponse
    {
        return $this->item($this->products->detail($product, $this->currentUser($request)));
    }
}
