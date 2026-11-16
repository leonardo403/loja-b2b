<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends ApiController
{
    public function __construct(
        private readonly ProductService $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category', 'brand', 'active', 'sort']);

        return response()->json(
            $this->products->adminList($filters, $this->perPage($request, 15))->toArray()
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create(
            $request->safe()->except('stock_quantity'),
            $request->integer('stock_quantity'),
        );

        return response()->json(['data' => $product->toArray()], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $updated = $this->products->update(
            $product,
            $request->safe()->except('stock_quantity'),
            $request->has('stock_quantity') ? $request->integer('stock_quantity') : null,
        );

        return $this->item($updated);
    }

    public function destroy(Product $product): Response
    {
        $this->products->delete($product);

        return response()->noContent();
    }
}
