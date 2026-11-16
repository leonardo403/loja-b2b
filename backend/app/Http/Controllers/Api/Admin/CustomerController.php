<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerController extends ApiController
{
    public function __construct(
        private readonly CustomerService $customers,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'blocked']);

        return response()->json(
            $this->customers->list($filters, $this->perPage($request, 15))->toArray()
        );
    }

    public function show(int $customer): JsonResponse
    {
        return $this->item($this->customers->find($customer));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->customers->create($request->validated())->toArray()], 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        return $this->item($this->customers->update($customer, $request->validated()));
    }

    public function destroy(Customer $customer): Response
    {
        $this->customers->delete($customer);

        return response()->noContent();
    }
}
