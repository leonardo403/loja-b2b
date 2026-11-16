<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\PriceTableItemRequest;
use App\Models\PriceTable;
use App\Models\Product;
use App\Services\PriceTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceTableController extends ApiController
{
    public function __construct(
        private readonly PriceTableService $priceTables,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->priceTables->tables()]);
    }

    public function items(Request $request, PriceTable $priceTable): JsonResponse
    {
        $filters = $request->only(['search']);

        return response()->json(
            $this->priceTables->items($priceTable, $filters, $this->perPage($request, 20))->toArray()
        );
    }

    public function updateItem(PriceTableItemRequest $request, PriceTable $priceTable, Product $product): JsonResponse
    {
        $item = $this->priceTables->updateItem($priceTable, $product->id, $request->validated());

        return $this->item($item);
    }
}
